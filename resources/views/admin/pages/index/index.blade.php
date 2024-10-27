@extends('admin.layout.app')
@section('content')
    <main id="main">

        <!-- Breadcrumbs-->
        <div class="bg-white border-bottom py-3 mb-5">
            <div
                class="container-fluid d-flex justify-content-between align-items-start align-items-md-center flex-column flex-md-row">
                <nav class="mb-0" aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="./index.html">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                    </ol>
                </nav>
                {{-- <div class="d-flex justify-content-end align-items-center mt-3 mt-md-0">
                <a class="btn btn-sm btn-primary" href="#"><i class="ri-add-circle-line align-bottom"></i> New Project</a>
                <a class="btn btn-sm btn-primary-faded ms-2" href="#"><i class="ri-settings-3-line align-bottom"></i> Settings</a>
                <a class="btn btn-sm btn-secondary-faded ms-2 text-body" href="#"><i class="ri-question-line align-bottom"></i> Help</a>
            </div> --}}
            </div>
        </div> <!-- / Breadcrumbs-->

        <!-- Content-->
        <section class="container-fluid mb-3">
            <!-- Top Row Widgets-->
            <div class="row g-4">
                @foreach ($stats as $stat)
                    <div class="col-12 col-sm-6 col-xxl-3">
                        <a href="{{ $stat['link'] }}">
                            <div class="card h-100">
                                <div class="card-header justify-content-between align-items-center d-flex border-0 pb-0">
                                    <h6 class="card-title m-0 text-muted fs-xs text-uppercase fw-bolder tracking-wide">
                                        {{ $stat['label'] }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row gx-4 mb-3 mb-md-1">
                                        <div class="col-12 col-md-6">
                                            <h3 class="fs-3 fw-bold d-flex align-items-center">{{ $stat['count'] }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach

            </div>
            <!-- / Top Row Widgets-->

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card mb-4 h-100">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">Lastest Applications</h6>
                            <a href="{{ route('dashboard.applications.index') }}"
                                class="btn btn-outline-secondary btn-sm text-body"><i
                                    class="ri-eye-2-line align-middle"></i> See all</a>
                        </div>
                        <div class="card-body">
                            @include('admin.pages.applications.fragments.table', ['sn' => 1])

                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4 h-100">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">Lastest Patients</h6>
                            <a href="{{ route('dashboard.consultation.patient-records.index') }}"
                                class="btn btn-outline-secondary btn-sm text-body"><i
                                    class="ri-eye-2-line align-middle"></i> See all</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table m-0 table-striped">
                                    <thead>
                                        <tr>
                                            <th>SN</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Sex</th>
                                            <th>Date</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($patients as $sn => $patient)
                                            <tr>
                                                <td>{{ $sn + 1 }}</td>
                                                <td>{{ $patient->full_name }}</td>
                                                <td>{{ $patient->email ?? 'N/A' }}</td>
                                                <td>{{ $patient->sex ?? 'N/A' }}</td>
                                                <td>{{ $patient->created_at }}</td>
                                                <td>
                                                    <a href="{{ route('dashboard.consultation.patients.show', $patient->id) }}"
                                                        class="btn btn-sm btn-info"><i
                                                            class="ri-eye-line align-middle"></i></a>
                                                </td>
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
@endsection
