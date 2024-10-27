@extends('admin.layout.app')
@section('content')
<!-- Page Content -->
<main id="main">

    <!-- Breadcrumbs-->
    <div class="bg-white border-bottom py-3 mb-5">
        <div class="container-fluid d-flex justify-content-between align-items-start align-items-md-center flex-column flex-md-row">
            <nav class="mb-0" aria-label="breadcrumb">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.payments.index') }}">Payments</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Information</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-end align-items-center mt-3 mt-md-0">
                <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i class="arrow-left align-bottom"></i>Back</a>
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
                            <b>Confirmation:</b> {{ $payment->reference ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Amount:</b> {{ $payment->getAmount() ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Package:</b> {{ optional($payment->package)->name ?? 'N/A' }}
                        </p>
                        <hr>
                        <p>
                            <b>Discount Code:</b> {{ $payment->discount_code ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Discount Amount:</b> {{ $payment->getDiscount() ?? 'N/A' }}
                        </p>
                        </p>
                        <hr>
                        <p>
                            <b>Gateway:</b> {{ $payment->gateway ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Curency:</b> {{ strtoupper($payment->currency) ?? 'N/A' }}
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
                        <h6 class="card-title m-0">User Details</h6>
                    </div>
                    <div class="card-body">
                        <p>
                            <b>User:</b> {{ optional($payment->user)->name ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Email:</b> {{ optional($payment->user)->email ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Phone:</b> {{ optional($payment->user)->phone ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Sex:</b> {{ optional($payment->user)->sex ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Location:</b> {{ optional($payment->user)->location ?? 'N/A' }}
                        </p>
                        <hr>
                        <p>
                            <b>Delivery Address 1:</b> {{ $payment->address_1 ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Delivery Address 2:</b> {{ $payment->address_2 ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Postal Code:</b> {{ $payment->postal_code ?? 'N/A' }}
                        </p>
                        <hr>
                        <p>
                            <b>City:</b> {{ $payment->city ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Province:</b> {{ $payment->province ?? 'N/A' }}
                        </p>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-3"></div>
            <div class="col-md-6">
                <div class="card mb-4 h-100">
                    @if (!empty($application = $payment->application))
                    <div class="card-header justify-content-between align-items-center d-flex">
                        <h6 class="card-title m-0">Application Details <b>#{{ $application->session_id }}-{{ $application->id }}</b></h6>
                        <a href="{{ route("dashboard.applications.show" , $application->id )}}" class="btn btn-outline-secondary btn-sm text-body"><i class="ri-eye-2-line align-middle"></i> View</a>
                    </div>
                    <div class="card-body">
                        <p>
                            <b>User:</b> {{ optional($application->user)->name ?? 'N/A' }}
                        </p>
                        <hr>
                        <p>
                            <b>Service:</b> {{ $application->getService() }}
                        </p>
                        <p>
                            <b>Package:</b> {{ optional($application->package)->name ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Price Per Pill:</b> {{ $application->getPricePerPill() }}
                        </p>
                        <p>
                            <b>Dose Per Pill:</b> {{ $application->dose_per_pill ?? 'N/A' }}
                        </p>
                        <p>
                            <b>No. OF Pills:</b> {{ $application->no_of_pills ?? 'N/A' }}
                        </p>
                        <p>
                            <b>Months:</b> {{ $application->months ?? 'N/A' }}
                        </p>

                        <hr>
                        <p>
                            <b>PDF:</b> <a href="{{ $application->getPdfUrl() }}" target="_blank" rel="noopener noreferrer">View</a>
                        </p>
                    </div>
                    @else
                    <div class="card-header justify-content-between align-items-center d-flex">
                        <h6 class="card-title m-0">Application Details</h6>
                    </div>
                    <div class="text-center m-3">
                        No Application data
                    </div>
                    @endif
                </div>
            </div>
            @if (!empty($meta = $payment->getMeta()))

            <div class="col-md-6">
                <div class="card mb-4 h-100">
                    <div class="card-header justify-content-between align-items-center d-flex">
                        <h6 class="card-title m-0">Payment Extra Details</h6>
                    </div>
                    <div class="card-body">
                        @foreach ($meta as $key => $value)
                        <p>
                            <b>{{ ucwords(str_replace("_" , " " , $key)) }}:</b>
                            @if ($key == "package")
                            {{ $value["name"] }}
                            @else
                            {{ $value }}
                            @endif
                        </p>
                        @endforeach



                    </div>
                </div>
            </div>
            @endif

        </div>
    </section>
    <!-- / Content-->

</main>
<!-- /Page Content -->
@endsection
