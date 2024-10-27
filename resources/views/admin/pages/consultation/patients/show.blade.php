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
                    <li class="breadcrumb-item"><a href="#">Consultation</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.consultation.patients.index') }}">Patients</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Information</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-end align-items-center mt-3 mt-md-0">
                <a class="btn btn-sm btn-primary me-3" href="{{ route('dashboard.consultation.patients.index') }}"><i class="ri-arrow-left-line align-bottom"></i> Back</a>
                <a target="_blank" href="{{ route("dashboard.consultation.patients.print" , $patient->id)}}" class="btn btn-outline-warning btn-sm text-body"><i class=" ri-printer-fill align-middle"></i> Print</a>

            </div>
        </div>
    </div> <!-- / Breadcrumbs-->

    <!-- Content-->
    <section class="container-fluid">
        @include('notifications.flash_messages')

        <div class="row">
            <div class="col-md-3">
                   @include("admin.pages.consultation.patients.fragments.cards.details")
                </div>

            <div class="col-md-5">
                <div class="card mb-4 h-100">
                    <div class="card-header justify-content-between align-items-center d-flex">
                        <h6 class="card-title m-0">Patient Notes</h6>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_note" class="btn btn-outline-secondary btn-sm text-body">
                            <i class="ri-add-circle-line align-middle"></i>
                            Create New Note</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table m-0 table-striped">
                                <thead>
                                    <tr>
                                        <th>SN</th>
                                        <th>Note</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($records->where("type" , "note")->all() as $sn => $record)
                                    <tr>
                                        <td>{{ $sn+1 }}</td>
                                        <td class="w-75">
                                            <div class="">
                                                {!! str_replace(["<p" , "</p>" ] , ["<div", "</div>" ] , $record->body ?? 'N/A') !!}
                                            </div>
                                            <div class="">
                                                <small>{{ $record->created_at }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route("dashboard.consultation.patient-records.show" , $record->id )}}" class="btn btn-sm btn-info"><i class="ri-eye-line align-middle"></i></a>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#{{ "edit_note_$record->id" }}" class="btn btn-outline-secondary btn-sm text-body"><i class="ri-edit-line align-middle"></i></a>
                                        </td>
                                    </tr>
                                    @include("admin.pages.consultation.patients.fragments.modals.save" , [
                                    "key" => "edit_note_$record->id",
                                    "record" => $record,
                                    "type" => "note",
                                    "header_label" => "Note"
                                    ])
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-2">Medical History</h6>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordion">
                            @foreach ($history_types as $key => $label)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading_{{$key}}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_{{$key}}" aria-expanded="false" aria-controls="collapse_{{$key}}">
                                        <div class="d-flex justify-content-between w-100">
                                            {{ $label }} ({{$records->where("type" , $key)->count()}})

                                            <a href="#" data-bs-toggle="modal" data-bs-target="#add_note_{{$key}}" class="me-3 ">
                                                <i class="ri-file-add-fill" style="font-size: 18px;color:black"></i>
                                            </a>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse_{{$key}}" class="accordion-collapse collapse" aria-labelledby="heading_{{$key}}" data-bs-parent="#accordion">
                                    <div class="accordion-body">
                                        <div class="table-responsive">
                                            <table class="table m-0 table-striped">
                                                <tbody>
                                                    @foreach ($records->where("type" , $key)->all() as $sn => $record)
                                                    <tr>
                                                        <td>{{ $sn+1 }}</td>
                                                        <td class="w-75">
                                                            <div class="">
                                                                {!! str_replace(["<p" , "</p>" ] , ["<div", "</div>" ] , $record->body ?? 'N/A') !!}
                                                            </div>
                                                            <div class="">
                                                                <small>{{ $record->created_at }}</small>
                                                            </div>
                                                        </td>
                                                        <td class="d-flex">
                                                            <a href="{{ route("dashboard.consultation.patient-records.show" , $record->id )}}" class="btn btn-sm btn-info me-1"><i class="ri-eye-line align-middle"></i></a>
                                                            <a href="#" data-bs-toggle="modal" data-bs-target="#{{ "edit_note_$record->id" }}" class="btn btn-outline-secondary btn-sm text-body"><i class="ri-edit-line align-middle"></i></a>
                                                        </td>
                                                    </tr>
                                                    @include("admin.pages.consultation.patients.fragments.modals.save" , [
                                                    "key" => "edit_note_$record->id",
                                                    "record" => $record,
                                                    "type" => $key,
                                                    "header_label" => $label
                                                    ])
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @include("admin.pages.consultation.patients.fragments.modals.save" , [
                            "key" => "add_note_$key",
                            "record" => null,
                            "type" => $key,
                            "header_label" => $label
                            ])
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- / Content-->
    @include("admin.pages.consultation.patients.fragments.modals.save" , [
    "key" => "add_note",
    "record" => null,
    "type" => "note",
    "header_label" => "Note"
    ])
</main>
<!-- /Page Content -->
@endsection
