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
                        <li class="breadcrumb-item active" aria-current="page">Payments</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-end align-items-center mt-3 mt-md-0">
                    {{-- <a class="btn btn-sm btn-primary" href="#"><i class="ri-add-circle-line align-bottom"></i> New
                        payment</a> --}}
                </div>
            </div>
        </div> <!-- / Breadcrumbs-->

        <!-- Content-->
        <section class="container-fluid">
            <div class="card mb-4 h-100">
                <div class="card-header justify-content-between align-items-center d-flex">
                    <h6 class="card-title m-0">Payments</h6>
                </div>
                <div class="card-body">
                    @include("admin.pages.payments.fragments.search")
                    <div class="table-responsive">
                        <table class="table m-0 table-striped">
                            <thead>
                                <tr>
                                    <th>SN</th>
                                    <th>Session Reference</th>
                                    <th>Payment Reference</th>
                                    <th>Sub Total</th>
                                    <th>Shipping Fee</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td>{{ $sn++ }}</td>
                                        <td>{{ $payment->formSession->reference ?? "N/A" }}</td>
                                        <td>{{ $payment->reference }}</td>
                                        <td>{{ $payment->getAmount("sub_total") ?? "N/A" }}</td>
                                        <td>{{ $payment->getAmount("shipping_fee") ?? "N/A" }}</td>
                                        <td>{{ $payment->getAmount("total") ?? "N/A" }}</td>
                                        <td>{{ $payment->status }}</td>
                                        <td>{{ $payment->created_at }}</td>
                                        <td>
                                            <a href="{{ route("dashboard.payments.show" , $payment->id )}}" class="btn btn-sm btn-info">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {!! $payments->links("pagination::bootstrap-4") !!}
                </div>
            </div>
        </section>
        <!-- / Content-->

    </main>
    <!-- /Page Content -->
@endsection
