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
                                <b>Session:</b> <a href="{{ route("dashboard.form-sessions.show" , $payment->form_session_id) }}" target="_blank"
                                    rel="noopener noreferrer">{{ $payment->formSession->reference }}  - View Details</a>
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
                            <p>
                                <b>Shipping Fee:</b> {{ $payment->getAmount('shipping_fee') ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Total:</b> {{ $payment->getAmount('total') ?? 'N/A' }}
                            </p>

                            <hr>
                            <p>
                                <b>Gateway:</b> {{ $payment->gateway ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Receipt:</b> <a href="{{ $payment->receipt_url }}" target="_blank"
                                    rel="noopener noreferrer">View</a>
                            </p>
                            <p>
                                <b>Date:</b> {{ $payment->created_at ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Status:</b> {{ $payment->status ?? 'N/A' }}
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
                                            <td>{{ $paymentProduct->product->name }}</td>
                                            <td>1</td>
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
