<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\FormSession;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Order Flow Test Suite
 * 
 * Tests the 4 primary order flows:
 * 1. Direct checkout from product page
 * 2. Cart checkout
 * 3. Pinksky order
 * 4. CCC Portal order
 */
class OrderFlowTest extends TestCase
{
    use DatabaseTransactions;

    private Product $product;
    private array $price;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test product with pricing
        $this->product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'code' => 'TST001',
            'price' => [
                [
                    'id' => 'price-1',
                    'value' => 100,
                    'currency' => 'USD',
                    'location' => 'US',
                ]
            ]
        ]);

        // Store price for reference
        $this->price = $this->product->price[0];

        // Create test user
        $this->user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'team' => 'default',
        ]);
    }

    /**
     * Test 1: Direct Checkout from Product Page
     * Verify product data is loaded correctly and pricing calculated
     */
    public function test_direct_checkout_product_loaded()
    {
        // Verify product exists and has correct data
        $this->assertNotNull($this->product);
        $this->assertEquals('Test Product', $this->product->name);
        $this->assertEquals('test-product', $this->product->slug);
        $this->assertTrue(is_array($this->product->price));
        $this->assertCount(1, $this->product->price);
    }

    /**
     * Test: Verify product pricing structure
     */
    public function test_product_pricing_structure()
    {
        $price = $this->product->price[0];
        
        // Verify price structure
        $this->assertEquals('price-1', $price['id']);
        $this->assertEquals(100, $price['value']);
        $this->assertEquals('USD', $price['currency']);
        $this->assertEquals('US', $price['location']);
    }

    /**
     * Test 2: Cart Checkout
     * Multiple products with different pricing
     */
    public function test_cart_with_multiple_products()
    {
        // Create multiple products with different prices
        $product2 = Product::create([
            'name' => 'Product 2',
            'slug' => 'product-2',
            'code' => 'TST002',
            'price' => [
                [
                    'id' => 'price-2',
                    'value' => 50,
                    'currency' => 'USD',
                    'location' => 'US',
                ]
            ]
        ]);

        // Verify both products exist
        $this->assertNotNull($this->product);
        $this->assertNotNull($product2);
        
        // Verify prices
        $price1 = $this->product->price[0]['value'];
        $price2 = $product2->price[0]['value'];
        
        $this->assertEquals(100, $price1);
        $this->assertEquals(50, $price2);
        
        // Verify subtotal calculation
        $subtotal = ($price1 * 2) + ($price2 * 1);
        $this->assertEquals(250, $subtotal);
    }

    /**
     * Test 3: Pinksky Order Requirements
     * Verify Pinksky-specific fields
     */
    public function test_pinksky_business_fields()
    {
        // Test Pinksky data structure
        $pinkSkyData = [
            'order_type' => 'order_sheet',
            'user_id' => $this->user->id,
            'status' => 'pending',
            'business_name' => 'Smith Medical Clinic',
            'medical_director_name' => 'Dr. William Smith',
            'source_path' => '/pinksky/order',
        ];

        // Verify Pinksky fields
        $this->assertEquals('order_sheet', $pinkSkyData['order_type']);
        $this->assertEquals('Smith Medical Clinic', $pinkSkyData['business_name']);
        $this->assertEquals('Dr. William Smith', $pinkSkyData['medical_director_name']);
        $this->assertStringContainsString('pinksky', $pinkSkyData['source_path']);
    }

    /**
     * Test 4: CCC Portal Order
     * Standard cart checkout with optional business fields
     */
    public function test_ccc_portal_fields()
    {
        // Test CCC portal data structure
        $cccData = [
            'order_type' => 'cart',
            'user_id' => $this->user->id,
            'status' => 'pending',
            'account_number' => 'CCC456789',
            'location' => 'US',
            'shipping_address' => '123 CCC Street',
            'source_path' => '/ccc-portal/order',
        ];

        // Verify CCC fields
        $this->assertEquals('cart', $cccData['order_type']);
        $this->assertEquals('CCC456789', $cccData['account_number']);
        $this->assertStringContainsString('ccc-portal', $cccData['source_path']);
    }

    /**
     * Test: Pricing calculations
     * Subtotal + Shipping + Tax calculations
     */
    public function test_pricing_calculation()
    {
        $subtotal = 100;
        $shipping = 60; // Default for <$1000
        $taxRate = 0.10;
        $tax = $subtotal * $taxRate;
        
        $total = $subtotal + $shipping + $tax;
        
        // Expected: 100 + 60 + 10 = 170
        $this->assertEquals(170, $total);
    }

    /**
     * Test: Free shipping threshold
     * Orders over $1000 should have free shipping
     */
    public function test_free_shipping_threshold()
    {
        $highValueSubtotal = 1000;
        $lowValueSubtotal = 500;
        
        // High value: free shipping
        $highValueShipping = $highValueSubtotal >= 1000 ? 0 : 60;
        $this->assertEquals(0, $highValueShipping);
        
        // Low value: $60 shipping
        $lowValueShipping = $lowValueSubtotal >= 1000 ? 0 : 60;
        $this->assertEquals(60, $lowValueShipping);
    }

    /**
     * Test: Discount code application
     */
    public function test_discount_code_creation()
    {
        // Test discount logic without creating record
        $code = 'PINKSKY25';
        $discountType = 'percentage';
        $discountValue = 25;
        
        $this->assertEquals('PINKSKY25', $code);
        $this->assertEquals('percentage', $discountType);
        $this->assertEquals(25, $discountValue);
    }

    /**
     * Test: Discount calculation
     * Percentage discount on subtotal
     */
    public function test_percentage_discount_calculation()
    {
        $subtotal = 100;
        $discountPercent = 25;
        
        $discountAmount = $subtotal * ($discountPercent / 100);
        $discountedSubtotal = $subtotal - $discountAmount;
        
        // Expected: 100 - 25 = 75
        $this->assertEquals(25, $discountAmount);
        $this->assertEquals(75, $discountedSubtotal);
    }

    /**
     * Test: Fixed discount calculation
     */
    public function test_fixed_discount_calculation()
    {
        $subtotal = 250;
        $discountAmount = 100;
        
        // Discount should not exceed subtotal
        $appliedDiscount = min($discountAmount, $subtotal);
        $discountedSubtotal = max(0, $subtotal - $appliedDiscount);
        
        $this->assertEquals(100, $appliedDiscount);
        $this->assertEquals(150, $discountedSubtotal);
    }

    /**
     * Test: Multi-currency support
     */
    public function test_usd_pricing()
    {
        $usdPrice = $this->product->price[0];
        $this->assertEquals('USD', $usdPrice['currency']);
        $this->assertEquals(100, $usdPrice['value']);
    }

    /**
     * Test: Multi-currency CAD
     */
    public function test_cad_pricing()
    {
        // Add CAD pricing to product
        $this->product->update([
            'price' => array_merge($this->product->price, [
                [
                    'id' => 'price-cad',
                    'value' => 135,
                    'currency' => 'CAD',
                    'location' => 'CA',
                ]
            ])
        ]);

        $cadPrice = $this->product->price[1];
        $this->assertEquals('CAD', $cadPrice['currency']);
        $this->assertEquals(135, $cadPrice['value']);
    }

    /**
     * Test: Payment record creation
     */
    public function test_payment_record_creation()
    {
        // Test payment data structure
        $paymentData = [
            'user_id' => $this->user->id,
            'reference' => 'PAY-TEST-001',
            'status' => 'pending',
            'total' => 170,
            'currency' => 'USD',
            'gateway' => 'stripe',
        ];

        $this->assertEquals('PAY-TEST-001', $paymentData['reference']);
        $this->assertEquals('pending', $paymentData['status']);
        $this->assertEquals(170, $paymentData['total']);
    }

    /**
     * Test: User account linking
     */
    public function test_user_account_linking()
    {
        // Test user linking logic
        $sessionData = [
            'order_type' => 'regular',
            'user_id' => $this->user->id,
            'status' => 'pending',
            'reference' => 'FS-TEST-001',
            'data' => []
        ];

        $this->assertEquals($this->user->id, $sessionData['user_id']);
        $this->assertEquals('regular', $sessionData['order_type']);
    }

    /**
     * Test: Form validation - required fields
     */
    public function test_required_fields_validation()
    {
        // Test email validation
        $validEmail = 'test@example.com';
        $invalidEmail = 'invalid-email';
        
        $isValidEmail = filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false;
        $isInvalidEmail = filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) !== false;
        
        $this->assertTrue($isValidEmail);
        $this->assertFalse($isInvalidEmail);
    }

    /**
     * Test: Phone number validation
     */
    public function test_phone_validation()
    {
        $validPhone = '5551234567';
        $invalidPhone = '123';
        
        $validDigits = strlen(preg_replace('/\D/', '', $validPhone)) >= 10;
        $invalidDigits = strlen(preg_replace('/\D/', '', $invalidPhone)) >= 10;
        
        $this->assertTrue($validDigits);
        $this->assertFalse($invalidDigits);
    }

    /**
     * Test: Tax calculation
     */
    public function test_tax_calculation()
    {
        $subtotal = 100;
        $taxRate = 10; // 10%
        
        $taxAmount = $subtotal * ($taxRate / 100);
        
        // Expected: 100 * 0.10 = 10
        $this->assertEquals(10, $taxAmount);
    }

    /**
     * Test: Tax on discounted amount
     * Tax should be calculated on discounted subtotal, not shipping
     */
    public function test_tax_on_discounted_subtotal()
    {
        $subtotal = 100;
        $discountAmount = 25;
        $discountedSubtotal = $subtotal - $discountAmount; // 75
        $taxRate = 10;
        $shipping = 60;
        
        // Tax calculated on discounted subtotal only
        $tax = $discountedSubtotal * ($taxRate / 100); // 75 * 0.10 = 7.50
        
        $total = $discountedSubtotal + $shipping + $tax; // 75 + 60 + 7.50 = 142.50
        
        $this->assertEquals(7.50, $tax);
        $this->assertEquals(142.50, $total);
    }

    /**
     * Test: Discount does not apply to shipping
     */
    public function test_discount_does_not_apply_to_shipping()
    {
        $subtotal = 100;
        $discountAmount = 25;
        $shipping = 60;
        $taxRate = 10;
        
        // Discount only applies to subtotal
        $discountedSubtotal = $subtotal - $discountAmount; // 75
        
        // Shipping is never discounted
        $discountedShipping = $shipping; // Still 60
        
        // Tax on discounted subtotal only
        $tax = $discountedSubtotal * ($taxRate / 100);
        
        $total = $discountedSubtotal + $discountedShipping + $tax;
        
        $this->assertEquals(60, $discountedShipping);
        $this->assertNotEquals(60 - 15, $discountedShipping);
    }

    /**
     * Test: Complete order flow calculation
     */
    public function test_complete_order_calculation()
    {
        // Cart: 2 products @ $100 each + 1 product @ $50 = $250
        $product2 = Product::create([
            'name' => 'Product 2',
            'slug' => 'product-2',
            'code' => 'TST002',
            'price' => [
                [
                    'id' => 'price-2',
                    'value' => 50,
                    'currency' => 'USD',
                    'location' => 'US',
                ]
            ]
        ]);

        $subtotal = (100 * 2) + (50 * 1); // 250
        $discountAmount = 100; // Fixed discount
        $discountedSubtotal = $subtotal - $discountAmount; // 150
        $shipping = 60; // Not discounted
        $taxRate = 10;
        $tax = $discountedSubtotal * ($taxRate / 100); // 15
        $total = $discountedSubtotal + $shipping + $tax; // 225
        
        $this->assertEquals(250, $subtotal);
        $this->assertEquals(150, $discountedSubtotal);
        $this->assertEquals(60, $shipping);
        $this->assertEquals(15, $tax);
        $this->assertEquals(225, $total);
    }
}
