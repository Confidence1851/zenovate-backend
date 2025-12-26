@extends('admin.layout.app')
@section('content')
    <!-- Page Content -->
    <main id="main">

        <!-- Breadcrumbs-->
        <div class="bg-white border-bottom py-3 mb-5">
            <div
                class="container-fluid d-flex justify-content-between align-items-start align-items-md-center flex-column flex-md-row">
                <nav class="mb-0" aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.payments.index') }}">Payments</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Information</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-end align-items-center mt-3 mt-md-0">
                    <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i
                            class="arrow-left align-bottom"></i>Back</a>
                </div>
            </div>
        </div> <!-- / Breadcrumbs-->

        <!-- Content-->
        <section class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4 h-100">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">Payment Details</h6>
                        </div>
                        <div class="card-body">
                            <p>
                                <b>Session:</b> <a
                                    href="{{ route('dashboard.form-sessions.show', $payment->form_session_id) }}"
                                    target="_blank" rel="noopener noreferrer">{{ $payment->formSession->reference }} - View
                                    Details</a>
                            </p>
                            <p>
                                <b>Reference:</b> {{ $payment->reference ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Curency:</b> {{ strtoupper($payment->currency) ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Sub Total:</b> {{ $payment->getAmount('sub_total') ?? 'N/A' }}
                            </p>
                            @if (!empty($payment->discount_code) && !empty($payment->discount_amount))
                                <p>
                                    <b>Discount Code:</b> {{ $payment->discount_code }}
                                </p>
                                <p>
                                    <b>Discount Amount:</b> -{{ strtoupper($payment->currency) }} {{ number_format($payment->discount_amount, 2) }}
                                </p>
                            @endif
                            <p>
                                <b>Shipping Fee:</b> {{ $payment->getAmount('shipping_fee') ?? 'N/A' }}
                            </p>
                            @if (!empty($payment->tax_rate) || !empty($payment->tax_amount))
                                <p>
                                    <b>Tax:</b> {{ !empty($payment->tax_rate) ? number_format($payment->tax_rate, 2) . '%' : 'N/A' }}
                                </p>
                                <p>
                                    <b>Tax Amount:</b> {{ $payment->getAmount('tax_amount') ?? 'N/A' }}
                                </p>
                            @endif
                            <p>
                                <b>Total:</b> {{ $payment->getAmount('total') ?? 'N/A' }}
                            </p>

                            <hr>
                            <p>
                                <b>Gateway:</b> {{ $payment->gateway ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Receipt:</b>
                                @if (!empty($payment->receipt_url))
                                    <a href="{{ $payment->receipt_url }}" target="_blank"
                                        rel="noopener noreferrer">View</a>
                                @else
                                    N/A
                                @endif
                            </p>
                            <p>
                                <b>Payment Reference:</b> {{ $payment->payment_reference ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Date:</b> {{ $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : 'N/A' }}
                            </p>
                            @if ($payment->paid_at)
                                <p>
                                    <b>Paid At:</b> {{ $payment->paid_at->format('Y-m-d H:i:s') }}
                                </p>
                            @endif
                            <p>
                                <b>Status:</b> <x-status-badge :value="$payment->status" />
                            </p>

                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4 h-100">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">Product Details</h6>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($payment->paymentProducts as $paymentProduct)
                                        <tr>
                                            <td>{{ $paymentProduct->product->name ?? 'Unknown Product' }}</td>
                                            <td>{{ $paymentProduct->quantity ?? 1 }}</td>
                                            <td>{{ $paymentProduct->getPrice() }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">No products selected at the
                                                moment</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </section>
        <!-- / Content-->

    </main>
    <!-- /Page Content -->
@endsection


