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
                        <li class="breadcrumb-item"><a href="#">Consultation</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Patients</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-end align-items-center mt-3 mt-md-0">
                    <a class="btn btn-sm btn-primary" href="{{ route("dashboard.consultation.patients.create")}}"><i class="ri-add-circle-line align-bottom"></i> New Patient</a>
                </div>
            </div>
        </div> <!-- / Breadcrumbs-->

        <!-- Content-->
        <section class="container-fluid">
        @include('notifications.flash_messages')

            <div class="card mb-4 h-100">
                <div class="card-header justify-content-between align-items-center d-flex">
                    <h6 class="card-title m-0">Patients</h6>
                    {{-- <a href="#" class="btn btn-outline-secondary btn-sm text-body"><i
                            class="ri-download-2-line align-middle"></i> Export</a> --}}
                </div>
                <div class="card-body">
                    @include("admin.pages.consultation.patients.fragments.search")
                    <div class="table-responsive">
                        <table class="table m-0 table-striped">
                            <thead>
                                <tr>
                                    <th>SN</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Sex</th>
                                    <th>D.O.B</th>
                                    <th>Records</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($patients as $patient)
                                    <tr>
                                        <td>{{ $sn++ }}</td>
                                        <td>{{ $patient->full_name }}</td>
                                        <td>{{ $patient->email ?? "N/A" }}</td>
                                        <td>{{ $patient->sex  ?? "N/A"}}</td>
                                        <td>{{ $patient->dob  ?? "N/A"}}</td>
                                        <td>{{ $patient->records_count }}</td>
                                        <td>{{ $patient->created_at }}</td>
                                        <td>
                                            <a href="{{ route("dashboard.consultation.patients.show" , $patient->id )}}" class="btn btn-sm btn-info"><i class="ri-eye-line align-middle"></i></a>
                                            <a href="{{ route("dashboard.consultation.patients.edit" , $patient->id)}}" class="btn btn-outline-secondary btn-sm text-body"><i
                                                class="ri-edit-line align-middle"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {!! $patients->links("pagination::bootstrap-4") !!}
                </div>
            </div>
        </section>
        <!-- / Content-->

    </main>
    <!-- /Page Content -->
@endsection
