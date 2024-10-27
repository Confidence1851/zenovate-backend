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
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.consultation.patient-records.index') }}">Patient Records</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-end align-items-center mt-3 mt-md-0">
                <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i class="ri-arrow-left-line align-bottom"></i> Back</a>
            </div>
        </div>
    </div> <!-- / Breadcrumbs-->

    <!-- Content-->
    <section class="container-fluid">
        @include('notifications.flash_messages')
        <div class="card mb-4 h-100">
            <div class="card-header justify-content-between align-items-center d-flex">
                <h6 class="card-title m-0">Create Patient Record</h6>
                {{-- <a href="#" class="btn btn-outline-secondary btn-sm text-body"><i
                            class="ri-download-2-line align-middle"></i> Export</a> --}}
            </div>
            <div class="card-body">
                @if (empty($patient))
                <div class="row">
                    <div class="col-md-3">
                        <a href="{{ route("dashboard.consultation.patients.create" , [
                            "redirect_url" => route("dashboard.consultation.patient-records.create" , [
                                "patient_id" => "<patient_id>"
                            ])
                        ])}}">
                            <div class="card card-body text-center">
                                <b>New Patient</b>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-9">
                        <form action="{{ url()->current() }}" class="d-flex">
                            <input type="text" class="form-control pe-2" name="search" value="{{ request()->search }}" placeholder="Search for a patient`s name or email" required>
                            <button class="btn btn-success btn-sm ml-2 text-white">Search</button>
                        </form>
                        @if (!empty($search_patients))
                        <div class="table-responsive mt-3">
                            <table class="table m-0 table-striped">
                                <thead>
                                    <tr>
                                        <th>SN</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Sex</th>
                                        <th>D.O.B</th>
                                        <th>Date</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($search_patients as $sn => $patient)
                                    <tr>
                                        <td>{{ $sn+1 }}</td>
                                        <td>{{ $patient->full_name }}</td>
                                        <td>{{ $patient->email ?? "N/A" }}</td>
                                        <td>{{ $patient->sex  ?? "N/A"}}</td>
                                        <td>{{ $patient->dob  ?? "N/A"}}</td>
                                        <td>{{ $patient->created_at }}</td>
                                        <td>
                                            <a target="_blank" href="{{ route("dashboard.consultation.patients.show" , $patient->id )}}" class="btn btn-sm btn-info">View</a>
                                            <a href="{{ route("dashboard.consultation.patient-records.create" , [
                                                    "patient_id" => $patient->id
                                                ] )}}" class="btn btn-sm btn-info text-white">Select</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
                @else
                <div class="row">
                    <div class="col-md-4">
                        @include("admin.pages.consultation.patients.fragments.cards.details")
                    </div>
                    <div class="col-md-8">
                        <form action="{{ route('dashboard.consultation.patient-records.store') }}" method="post">
                            @csrf
                            <div class="card-body">
                        <input type="hidden" name="redirect_url" value="{{ request()->redirect_url }}">
                        <input type="hidden" name="patient_id" value="{{$patient->id}}" required>
                                @foreach ($fields as $key => $field)
                                <div class="form-group row">
                                    <b class="col-md-3 mt-2" for="">{{ $field['label'] }}</b>
                                    <div class="col-md-6">
                                        @if ($key == 'sex')
                                        <select name="{{ $key }}" class="form-control" {{ $field['required'] ?? false ? 'required' : '' }}>
                                            <option value="" disabled selected>Select Option</option>
                                            @foreach ($sex_options as $option)
                                            <option value="{{ $option }}" {{ old($key) == $option ? 'selected' : '' }}>{{ $option }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @else
                                        <textarea name="{{ $key }}" class="form-control" {{ $field['required'] ?? false ? 'required' : '' }} >{{ old($key) }}</textarea>
                                        @endif
                                        @error($key)
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-success text-white">Save Record</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>
    <!-- / Content-->

</main>
<!-- /Page Content -->
@endsection
