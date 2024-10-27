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
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.applications.index') }}">Applications</a>
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
                <div class="col-md-4">
                    <div class="card mb-4 h-100">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">User Details <b>#{{ $application->user_id }}</b></h6>
                            {{-- <a href="#" class="btn btn-outline-secondary btn-sm text-body"><i
                                    class="ri-download-2-line align-middle"></i> Export</a> --}}
                        </div>
                        <div class="card-body">
                            <p>
                                <b>User Name:</b> {{ optional($application->user)->name ?? 'N/A' }}
                            </p>
                            <p>
                                <b>User Email:</b> {{ optional($application->user)->email ?? 'N/A' }}
                            </p>
                            <hr>
                            <p>
                                <b>Address 1:</b> {{ optional($application->payment)->address_1 ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Address 2:</b> {{ optional($application->payment)->address_2 ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Postal Code:</b> {{ optional($application->payment)->postal_code ?? 'N/A' }}
                            </p>
                            <p>
                                <b>City:</b> {{ optional($application->payment)->city ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Phone:</b> {{ optional($application->payment)->phone ?? 'N/A' }}
                            </p>


                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4 h-100">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">Application Details
                                <b>#{{ $application->session_id }}-{{ $application->id }}</b></h6>
                            {{-- <a href="#" class="btn btn-outline-secondary btn-sm text-body"><i
                                    class="ri-download-2-line align-middle"></i> Export</a> --}}
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
                                <b>PDF:</b> <a href="{{ $application->getPdfUrl() }}" target="_blank"
                                    rel="noopener noreferrer">View</a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4 h-100">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">Payment Details</h6>
                            {{-- <a href="#" class="btn btn-outline-secondary btn-sm text-body"><i
                                    class="ri-download-2-line align-middle"></i> Export</a> --}}
                        </div>
                        <div class="card-body">
                            <p>
                                <b>Confirmation:</b> {{ optional($application->payment)->reference ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Amount:</b> {{ optional($application->payment)->getAmount() ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Receipt:</b> <a href="{{ $application->getReceiptUrl() }}" target="_blank" rel="noopener noreferrer">View</a>
                            </p>
                            <hr>
                            <p>
                                <b>Gateway:</b> {{ optional($application->payment)->gateway ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Curency:</b> {{ optional($application->payment)->currency ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Date:</b> {{ optional($application->payment)->created_at ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Status:</b> {{ optional($application->payment)->status ?? 'N/A' }}
                            </p>

                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <div class="card mb-4 h-100">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">Questionnaire Details</h6>
                            {{-- <a href="#" class="btn btn-outline-secondary btn-sm text-body"><i
                                    class="ri-download-2-line align-middle"></i> Export</a> --}}
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table m-0 table-striped">
                                <thead>
                                    <tr>
                                        <th>
                                            QUESTION
                                        </th>
                                        <th>
                                            ANSWER
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($application->questions as $question)
                                    <tr>
                                        <td><b>{{ $question->question }}</b></td>
                                        <td > {{ $question->parseValue() ?? "N/A" }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- / Content-->

    </main>
    <!-- /Page Content -->
@endsection
