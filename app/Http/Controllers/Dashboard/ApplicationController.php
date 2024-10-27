<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ApplicationForm;
use App\Models\TreatmentPackage;
use App\Services\Form\DataConstants;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $applications = ApplicationForm::with([
            "user",
            "payment",
            "package"
        ])
            ->where(function ($query) use ($request) {
                if (!empty($key = $request->search)) {
                    $query->whereHas("user", function ($user) use ($key) {
                        $user->whereRaw('CONCAT(name," ", email) LIKE?', ["%$key%"]);
                    }
                    )
                        ->orWhereHas("payment", function ($payment) use ($key) {
                                $payment->where("reference", "like", "%$key%");
                    })
                        ->orWhere("session_id", "like", "%$key%");
                }
                if (!empty($key = $request->package_id)) {
                    $query->where("package_id", $key);
                }
                if (!empty($key = $request->service)) {
                    $query->where("service", $key);
                }
            })
            ->latest()->paginate()->appends($request->query());
        $packages = TreatmentPackage::active()->get();
        return view("admin.pages.applications.index", [
            "sn" => $applications->firstItem(),
            "applications" => $applications,
            "packages" => $packages,
            "services" => array_keys(DataConstants::getIndexData())
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $application = ApplicationForm::with([
            "user",
            "payment",
            "package"
        ])->findOrFail($id);
        return view("admin.pages.applications.show", [
            "application" => $application
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
        //
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
        //
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
}
