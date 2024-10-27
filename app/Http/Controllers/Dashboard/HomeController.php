<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ApplicationForm;
use App\Models\Patient;
use App\Models\PatientRecord;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $stats = [
            [
                "label" => "Users",
                "count" => User::count(),
                "link" => route("dashboard.users.index")
            ],
            [
                "label" => "Applications",
                "count" => ApplicationForm::count(),
                "link" => route("dashboard.applications.index")
            ],
            [
                "label" => "Payments",
                "count" => Payment::count(),
                "link" => route("dashboard.payments.index")
            ],
            [
                "label" => "Consultations",
                "count" => PatientRecord::count(),
                "link" => route("dashboard.consultation.patient-records.index")
            ],
        ];
        $applications = ApplicationForm::with([
            "user",
            "payment",
            "package"
        ])->limit(10)->latest()->get();
        $patients =  Patient::limit(10)->latest()->get();
        return view("admin.pages.index.index" , [
            "stats" => $stats,
            "applications" => $applications,
            "patients" => $patients
        ]);
    }
}
