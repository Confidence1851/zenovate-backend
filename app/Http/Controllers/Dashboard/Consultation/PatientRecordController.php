<?php

namespace App\Http\Controllers\Dashboard\Consultation;

use App\Constants\ConsultationConstants;
use App\Constants\NotificationConstants;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientRecord;
use App\Services\Consultation\PatientRecordService;
use Illuminate\Http\Request;

class PatientRecordController extends Controller
{
    public $service;
    public function __construct()
    {
        $this->service = new PatientRecordService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $builder = PatientRecord::with("patient")
        ->whereHas("patient" , function($query) use ($request){

            if(!empty($key = $request->search)){
                $query->search($key);
            }
            if(!empty($key = $request->dob)){
                $query->where("dob",$key);
            }
            if(!empty($key = $request->sex)){
                $query->where("sex",$key);
            }
        });
        if(!empty($key = $request->doctor_name)){
            $builder = $builder->where("doctor_name",$key);
        }
        if(!empty($key = $request->from)){
            $builder = $builder->whereDate("created_at",">=" , $key);
        }
        if(!empty($key = $request->to)){
            $builder = $builder->whereDate("created_at","<=" , $key);
        }
        $records = $builder->latest()->paginate()->appends($request->query());
        return view("admin.pages.consultation.patient_records.index", [
            "sn" => $records->firstItem(),
            "records" => $records
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if(!empty($key =$request->patient_id)){
            $patient = Patient::findOrFail($key);
        }

        if(!empty($key = $request->search)){
            $search_patients = Patient::search($key)->orderby("first_name")->get();
        }

        return view("admin.pages.consultation.patient_records.create", [
            "patient" => $patient ?? null,
            "search_patients" => $search_patients ?? null,
            "fields" => $this->service->getFormFields()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->service->save($request->all());
        return redirect()->back()
            ->with(NotificationConstants::SUCCESS_MSG, "Record created successfully");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $record = PatientRecord::with(["patient"])->findOrFail($id);
        return view("admin.pages.consultation.patient_records.show", [
            "record" => $record,
            "patient" => $record->patient
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $record = PatientRecord::findOrFail($id);
        return view("admin.pages.consultation.patient_records.edit", [
            "record" => $record,
            "patient" => $record->patient,
            "fields" => $this->service->getFormFields()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->service->save($request->all(), $id);
        return redirect()->back()
            ->with(NotificationConstants::SUCCESS_MSG, "Record updated successfully");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function print($id)
    {
        return $this->service->print($id);
    }
}
