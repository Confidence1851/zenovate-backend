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
                    <li class="breadcrumb-item active" aria-current="page">Information</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-end align-items-center mt-3 mt-md-0">
                <a class="btn btn-sm btn-primary" href="{{ url()->previous() ?? route('dashboard.consultation.patient-records.index') }}"><i class="ri-arrow-left-line align-bottom"></i> Back</a>
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
                        <h6 class="card-title m-0">Patient Record Details</h6>
                        <div class="">
                            <a href="{{ route('dashboard.consultation.patient-records.edit', $record->id) }}" class="btn btn-outline-secondary btn-sm text-body"><i class="ri-edit-line align-middle"></i>
                                Edit</a>
                            <a href="{{ route("dashboard.consultation.patient-records.print" , $record->id)}}" class="btn btn-outline-warning btn-sm text-body"><i class=" ri-printer-fill align-middle"></i> Print</a>
                        </div>
                    </div>
                    <div class="card-body">
                        @foreach ($record->toArray() as $column => $value)
                        @if (!in_array($column, ['id', 'patient' , 'patient_id','user_id', 'deleted_at']))
                        <div class="row mb-2">
                           <div class="col-md-2">
                             <b>{{ ucwords(str_replace('_', ' ', $column)) }}: </b>
                           </div>
                           <div class="col-md-8">
                             @if ($column == 'created_by')
                             {{ optional($record->createdBy)->name ?? 'N/A' }}
                             @elseif (in_array($column, ['created_at', 'updated_at']))
                             {{ $record->$column->format('Y-m-d h:i A') }}
                             @elseif (in_array($column, ['type']))
                             {{ $record->getType() }}
                             @else
                             {!! $value ?? 'N/A' !!}
                             @endif

                           </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- / Content-->

</main>
<!-- /Page Content -->
@endsection
