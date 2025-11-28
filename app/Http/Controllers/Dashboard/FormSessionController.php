<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\NotificationConstants;
use App\Helpers\StatusConstants;
use App\Http\Controllers\Controller;
use App\Models\FormSession;
use App\Models\FormSessionActivity;
use App\Services\Form\Session\DTOService;
use App\Services\Form\Session\SignService;
use Illuminate\Http\Request;

class FormSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $builder = FormSession::query()->with(['completedPayment', 'user']);
        if (!empty($key = $request->search)) {
            $builder = $builder->search($key);
        }
        if (!empty($key = $request->status)) {
            $builder = $builder->whereStatus($key);
        }

        $sessions = $builder->latest()->paginate()->appends($request->query());
        return view("admin.pages.sessions.index", [
            "sn" => $sessions->firstItem(),
            "sessions" => $sessions,
            "statuses" => StatusConstants::SESSION_OPTIONS,
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
        // $data = $request->validate([
        //     "name" => "required|string",
        //     "email" => "required|string|unique:users,email",
        //     "sex" => "required|string",
        //     "password" => "required|string",
        // ]);
        // $data["password"] = Hash::make($data["password"]);
        // $data["role"] = "admin";
        // User::create($data);
        // return back()->with(NotificationConstants::SUCCESS_MSG, "Admin created successfully");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $session = FormSession::findOrFail($id);
        $activities = FormSessionActivity::where("form_session_id", $id)->latest("id")->get();
        return view("admin.pages.sessions.show", [
            "session" => $session,
            "dto" => new DTOService($session),
            "activities" => $activities,
            "statuses" => StatusConstants::BOOL_OPTIONS,
            "can_review" => $session->status == StatusConstants::AWAITING_REVIEW
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
        $data = $request->validate([
            "status" => "required|in:Yes,No",
            "comment" => "required_if:status,No"
        ]);
        $session = FormSession::findOrFail($id);
        $process = (new SignService($session))->handleAdminReview($data);
        return back()->with(NotificationConstants::SUCCESS_MSG, $process["message"]);
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
