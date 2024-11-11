<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\StatusConstants;
use App\Http\Controllers\Controller;
use App\Models\ApplicationForm;
use App\Models\FormSession;
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
                "label" => "Incomplete Form Sessions",
                "count" => FormSession::whereIn("status", [
                    StatusConstants::PROCESSING,
                ])->count(),
                "link" => route("dashboard.form-sessions.index", ["status" => StatusConstants::PROCESSING])
            ],
            [
                "label" => "Orders Awaiting Review",
                "count" => FormSession::whereIn("status", [
                    StatusConstants::AWAITING_REVIEW,
                ])->count(),
                "link" => route("dashboard.form-sessions.index", ["status" => StatusConstants::AWAITING_REVIEW])
            ],
            [
                "label" => "Completed Orders",
                "count" => FormSession::whereIn("status", [
                    StatusConstants::COMPLETED,
                ])->count(),
                "link" => route("dashboard.form-sessions.index", ["status" => StatusConstants::COMPLETED])
            ],
            [
                "label" => "Payments Collected",
                "count" => [
                    "USD" => number_format(Payment::where("status", StatusConstants::SUCCESSFUL)->where("currency", "USD")->sum("total"), 2),
                    "CAD" => number_format(Payment::where("status", StatusConstants::SUCCESSFUL)->where("currency", "CAD")->sum("total"),2),
                ],
                "link" => route("dashboard.payments.index" , ["status" => StatusConstants::SUCCESSFUL])
            ],
        ];
        $sessions = FormSession::limit(10)->latest()->get();
        return view("admin.pages.index.index", [
            "stats" => $stats,
            "sessions" => $sessions
        ]);
    }
}
