<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Summary - {{ $payment->reference }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }

        .header {
            margin-bottom: 30px;
            border-bottom: 3px solid #162C15;
            padding-bottom: 20px;
            background: linear-gradient(to bottom, #fafafa 0%, #ffffff 100%);
        }

        .header-top {
            width: 100%;
            margin-bottom: 15px;
        }

        .header-top table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            vertical-align: middle;
            width: 50%;
        }

        .header-right {
            vertical-align: middle;
            text-align: right;
            width: 50%;
        }

        .logo-text {
            font-size: 36px;
            font-weight: bold;
            color: #162C15;
            letter-spacing: 3px;
            margin: 0;
            padding: 0;
        }

        .order-info {
            font-size: 12px;
            color: #333;
            line-height: 1.8;
        }

        .order-info strong {
            color: #162C15;
            font-weight: bold;
        }

        .header h1 {
            margin: 15px 0 0 0;
            font-size: 28px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 12px;
            border-bottom: 2px solid #162C15;
            padding-bottom: 8px;
            color: #162C15;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }

        .info-value {
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th {
            background-color: #f5f5f5;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        table td {
            padding: 10px 8px;
            border: 1px solid #e5e5e5;
        }

        table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        table tbody tr:hover {
            background-color: #f5f5f5;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-table {
            margin-top: 20px;
            width: 100%;
        }

        .summary-table td {
            padding: 5px 8px;
        }

        .summary-table .label {
            font-weight: bold;
            text-align: right;
            width: 60%;
            color: #162C15;
        }

        .summary-table .amount {
            text-align: right;
            width: 40%;
        }

        .total-row {
            font-weight: bold;
            font-size: 16px;
            border-top: 3px solid #162C15;
            border-bottom: 3px solid #162C15;
            background-color: #f5f5f5;
            color: #162C15;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #162C15;
            font-size: 10px;
            color: #666;
            text-align: center;
        }

        .footer strong {
            color: #162C15;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-top">
            <table>
                <tr>
                    <td class="header-left">
                        <div class="logo-text">ZENOVATE</div>
                    </td>
                    <td class="header-right">
                        <div class="order-info">
                            <div><strong>Order Number:</strong> {{ $payment->reference }}</div>
                            <div><strong>Date:</strong> {{ $payment->created_at->format('F j, Y \a\t g:i A') }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <h1>ORDER SUMMARY</h1>
    </div>

    <div class="section">
        <div class="section-title">Customer Information</div>
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span class="info-value">{{ $customerName ?: 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $customerEmail }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Phone:</span>
            <span class="info-value">{{ $customerPhone }}</span>
        </div>
        @if ($accountNumber)
            <div class="info-row">
                <span class="info-label">Account Number:</span>
                <span class="info-value">{{ $accountNumber }}</span>
            </div>
        @endif
        @if ($location)
            <div class="info-row">
                <span class="info-label">Location:</span>
                <span class="info-value">{{ $location }}</span>
            </div>
        @endif
        @if ($shippingAddress)
            <div class="info-row">
                <span class="info-label">Shipping Address:</span>
                <span class="info-value">{{ $shippingAddress }}</span>
            </div>
        @endif
        @if ($additionalInformation)
            <div class="info-row">
                <span class="info-label">Additional Information:</span>
                <span class="info-value">{{ $additionalInformation }}</span>
            </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Order Items</div>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $product['name'] }}</td>
                        <td class="text-center">{{ $product['quantity'] }}</td>
                        <td class="text-right">{{ strtoupper($product['currency']) }}
                            {{ number_format($product['unit_price'], 2) }}</td>
                        <td class="text-right">{{ strtoupper($product['currency']) }}
                            {{ number_format($product['line_total'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No products found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Pricing Summary</div>
        <table class="summary-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="amount">{{ strtoupper($payment->currency) }}
                    {{ number_format($payment->sub_total ?? 0, 2) }}</td>
            </tr>
            @if (!empty($payment->discount_code) && ($payment->discount_amount ?? 0) > 0)
                <tr>
                    <td class="label">Discount ({{ $payment->discount_code }}):</td>
                    <td class="amount">-{{ strtoupper($payment->currency) }}
                        {{ number_format($payment->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if (($payment->shipping_fee ?? 0) > 0)
                <tr>
                    <td class="label">Shipping Fee:</td>
                    <td class="amount">{{ strtoupper($payment->currency) }}
                        {{ number_format($payment->shipping_fee, 2) }}</td>
                </tr>
            @else
                <tr>
                    <td class="label">Shipping Fee:</td>
                    <td class="amount">FREE</td>
                </tr>
            @endif
            @if (($payment->tax_amount ?? 0) > 0)
                <tr>
                    <td class="label">
                        Tax{{ $payment->tax_rate ? ' (' . number_format($payment->tax_rate, 2) . '%)' : '' }}:</td>
                    <td class="amount">{{ strtoupper($payment->currency) }}
                        {{ number_format($payment->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td class="label">Total:</td>
                <td class="amount">{{ strtoupper($payment->currency) }} {{ number_format($payment->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Payment Information</div>
        <div class="info-row">
            <span class="info-label">Payment Gateway:</span>
            <span class="info-value">{{ $payment->gateway }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">{{ ucfirst(strtolower($payment->status)) }}</span>
        </div>
        @if ($payment->paid_at)
            <div class="info-row">
                <span class="info-label">Paid At:</span>
                <span class="info-value">{{ $payment->paid_at->format('F j, Y \a\t g:i A') }}</span>
            </div>
        @endif
    </div>

    @if ($formSession)
        <div class="section">
            <div class="section-title">Order Details</div>
            <div class="info-row">
                <span class="info-label">Form Session Reference:</span>
                <span class="info-value">{{ $formSession->reference }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Order Status:</span>
                <span class="info-value">{{ ucwords(str_replace('_', ' ', $formSession->status)) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Booking Type:</span>
                <span class="info-value">{{ ucfirst($formSession->booking_type ?? 'form') }}</span>
            </div>
        </div>
    @endif

    <div class="footer">
        <p><strong>Thank you for your order!</strong></p>
        <p>For questions or concerns, please contact us at info@zenovate.health</p>
        <p>This is an automated order summary. Please keep this document for your records.</p>
    </div>
</body>

</html>
