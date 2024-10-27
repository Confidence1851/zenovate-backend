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
                        <li class="breadcrumb-item"><a
                                href="{{ route('dashboard.consultation.patients.index') }}">Patients</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Information</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-end align-items-center mt-3 mt-md-0">
                    <a class="btn btn-sm btn-primary" href="{{ route('dashboard.consultation.patients.index') }}"><i
                            class="ri-arrow-left-line align-bottom"></i> Back</a>
                </div>
            </div>
        </div> <!-- / Breadcrumbs-->

        <!-- Content-->
        <section class="container-fluid">
            @include('notifications.flash_messages')

            <div class="row">
                <div class="col-md-4">
                   @include("admin.pages.consultation.patients.fragments.cards.details")
                </div>
                <div class="col-md-8">
                    <div class="card mb-4 h-100">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">Patient Record History</h6>
                            <a href="{{ route('dashboard.consultation.patient-records.create', [
                                "patient_id" => $patient->id
                            ]) }}"
                                class="btn btn-outline-secondary btn-sm text-body"><i class="ri-add-circle-line align-middle"></i>
                                Create New Record</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table m-0 table-striped">
                                    <thead>
                                        <tr>
                                            <th>SN</th>
                                            <th>Created By</th>
                                            <th>Plan</th>
                                            <th>Comment</th>
                                            <th>Date</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($patient->records as $sn => $record)
                                            <tr>
                                                <td>{{ $sn+1 }}</td>
                                                <td>{{ optional($record->createdBy)->name }}</td>
                                                <td>{{ $record->plan ?? 'N/A' }}</td>
                                                <td>{{ $record->comment ?? 'N/A' }}</td>
                                                <td>{{ $record->created_at }}</td>
                                                <td>
                                                    <a href="{{ route("dashboard.consultation.patient-records.show" , $record->id )}}" class="btn btn-sm btn-info">View</a>
                                                    <a href="{{ route("dashboard.consultation.patient-records.edit" , $record->id)}}" class="btn btn-outline-secondary btn-sm text-body"><i
                                                        class="ri-edit-line align-middle"></i> Edit</a>
                                                        <a href="{{ route("dashboard.consultation.patient-records.print" , $record->id)}}" class="btn btn-outline-warning btn-sm text-body"><i
                                                            class=" ri-printer-fill align-middle"></i> Print</a>
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
    <!-- /Page Content -->
@endsection
