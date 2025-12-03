<?php

namespace Database\Seeders;

use App\Models\DiscountCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiscountCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $codes = [
            [
                'code' => '20BF',
                'type' => 'percentage',
                'value' => 20.00,
                'status' => 'Active',
                'usage_limit' => 0, // Unlimited
            ],
            [
                'code' => '50CP',
                'type' => 'percentage',
                'value' => 50.00,
                'status' => 'Active',
                'usage_limit' => 0, // Unlimited
            ],
            [
                'code' => '30FF',
                'type' => 'percentage',
                'value' => 30.00,
                'status' => 'Active',
                'usage_limit' => 0, // Unlimited
            ],
            [
                'code' => 'Jens20',
                'type' => 'percentage',
                'value' => 20.00,
                'status' => 'Active',
                'start_date' => null,
                'end_date' => null, // No expiry
                'usage_limit' => 0, // Unlimited
            ],
            [
                'code' => '20NF',
                'type' => 'percentage',
                'value' => 20.00,
                'status' => 'Active',
                'start_date' => null,
                'end_date' => null, // No expiry
                'usage_limit' => 0, // Unlimited
            ],
        ];

        foreach ($codes as $codeData) {
            DiscountCode::updateOrCreate(
                ['code' => $codeData['code']],
                $codeData
            );
            $this->command->info("Created/Updated discount code: {$codeData['code']}");
        }

        $this->command->info("\nDiscount codes seeded successfully!");
    }
}
