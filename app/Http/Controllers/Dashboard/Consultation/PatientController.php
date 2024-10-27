<?php

namespace App\Http\Controllers\Dashboard\Consultation;

use App\Constants\ConsultationConstants;
use App\Constants\NotificationConstants;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Consultation\PatientService;
use App\Services\General\UrlService;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public $service;
    public function __construct()
    {
        $this->service = new PatientService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $builder = Patient::withCount("records");
        if (!empty($key = $request->search)) {
            $builder = $builder->search($key);
        }
        if (!empty($key = $request->dob)) {
            $builder = $builder->where("dob", $key);
        }
        if (!empty($key = $request->sex)) {
            $builder = $builder->where("sex", $key);
        }
        $patients = $builder->latest()->paginate()->appends($request->query());
        return view("admin.pages.consultation.patients.index", [
            "sn" => $patients->firstItem(),
            "patients" => $patients
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        return view("admin.pages.consultation.patients.create", [
            "sex_options" => ConsultationConstants::SEX,
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
        $patient = $this->service->save($request->all());
        $redirect_url = $request->redirect_url ?? route("dashboard.consultation.patients.index");
        $redirect_url = UrlService::parse($redirect_url, [
            "<patient_id>" => $patient->id
        ]);
        return redirect($redirect_url)->with(NotificationConstants::SUCCESS_MSG, "Patient created successfully");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $patient = Patient::with(["records", "createdBy"])->findOrFail($id);
        return view("admin.pages.consultation.patients.show", [
            "patient" => $patient,
            "history_types" => ConsultationConstants::HISTORY_TYPES,
            "records" => $patient->records
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
        $patient = Patient::findOrFail($id);
        return view("admin.pages.consultation.patients.edit", [
            "patient" => $patient,
            "sex_options" => ConsultationConstants::SEX,
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
        return redirect()->route("dashboard.consultation.patients.show", $id)
            ->with(NotificationConstants::SUCCESS_MSG, "Patient updated successfully");
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
