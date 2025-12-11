<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\NotificationConstants;
use App\Helpers\StatusConstants;
use App\Http\Controllers\Controller;
use App\Models\FormSession;
use App\Models\FormSessionActivity;
use App\Notifications\OrderSheet\Customer\OrderCompletedNotification;
use App\Notifications\OrderSheet\Customer\OrderRefundedNotification;
use App\Notifications\OrderSheet\Customer\OrderUnfulfilledNotification;
use App\Services\Form\Session\DTOService;
use App\Services\Form\Session\SignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

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
     * Mark direct order as completed
     *
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markCompleted($id)
    {
        $session = FormSession::findOrFail($id);

        // Only allow marking direct checkout orders as completed
        if (!$session->isDirectCheckout()) {
            return back()->with(NotificationConstants::ERROR_MSG, 'This action is only available for direct checkout orders.');
        }

        // Check if already completed
        if ($session->status === StatusConstants::COMPLETED) {
            return back()->with(NotificationConstants::ERROR_MSG, 'This order is already marked as completed.');
        }

        // Check if payment is successful
        if (!$session->completedPayment) {
            return back()->with(NotificationConstants::ERROR_MSG, 'Cannot mark order as completed. Payment not successful.');
        }

        $user = auth()->user();
        if (!$user) {
            return back()->with(NotificationConstants::ERROR_MSG, 'User not authenticated.');
        }

        // Update status to completed
        $session->update([
            'status' => StatusConstants::COMPLETED,
        ]);

        // Create activity
        FormSessionActivity::create([
            'form_session_id' => $session->id,
            'user_id' => $user->id,
            'activity' => \App\Helpers\AppConstants::ACIVITY_CONFIRMED,
            'message' => "Order marked as completed by {$user->first_name} {$user->last_name}.",
        ]);

        // Send email to customer
        $metadata = $session->metadata['raw'] ?? [];
        $customerEmail = $metadata['email'] ?? null;
        if ($customerEmail) {
            try {
                Notification::route('mail', $customerEmail)
                    ->notify(new OrderCompletedNotification($session));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send completion email', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with(NotificationConstants::SUCCESS_MSG, 'Order marked as completed successfully!');
    }

    /**
     * Mark direct order as unfulfilled (cancels order)
     *
     * @param  string  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markUnfulfilled($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        $session = FormSession::findOrFail($id);

        // Only allow marking direct checkout orders
        if (!$session->isDirectCheckout()) {
            return back()->with(NotificationConstants::ERROR_MSG, 'This action is only available for direct checkout orders.');
        }

        // Check if already cancelled/refunded
        if (in_array($session->status, [StatusConstants::CANCELLED, StatusConstants::REFUNDED, StatusConstants::COMPLETED])) {
            return back()->with(NotificationConstants::ERROR_MSG, 'This order cannot be marked as unfulfilled in its current status.');
        }

        $user = auth()->user();
        if (!$user) {
            return back()->with(NotificationConstants::ERROR_MSG, 'User not authenticated.');
        }

        // Update status to cancelled
        $session->update([
            'status' => StatusConstants::CANCELLED,
        ]);

        // Create activity
        FormSessionActivity::create([
            'form_session_id' => $session->id,
            'user_id' => $user->id,
            'activity' => 'Unfulfilled',
            'message' => "Order marked as unfulfilled by {$user->first_name} {$user->last_name}. Reason: {$request->reason}",
        ]);

        // Send email to customer
        $metadata = $session->metadata['raw'] ?? [];
        $customerEmail = $metadata['email'] ?? null;
        if ($customerEmail) {
            try {
                Notification::route('mail', $customerEmail)
                    ->notify(new OrderUnfulfilledNotification($session, $request->reason));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send unfulfilled email', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with(NotificationConstants::SUCCESS_MSG, 'Order marked as unfulfilled successfully!');
    }

    /**
     * Mark direct order as refunded (cancels order and processes refund)
     *
     * @param  string  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markRefunded($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        $session = FormSession::findOrFail($id);

        // Only allow marking direct checkout orders
        if (!$session->isDirectCheckout()) {
            return back()->with(NotificationConstants::ERROR_MSG, 'This action is only available for direct checkout orders.');
        }

        // Check if already cancelled/refunded
        if (in_array($session->status, [StatusConstants::CANCELLED, StatusConstants::REFUNDED, StatusConstants::COMPLETED])) {
            return back()->with(NotificationConstants::ERROR_MSG, 'This order cannot be refunded in its current status.');
        }

        // Check if payment exists
        $payment = $session->completedPayment;
        if (!$payment) {
            return back()->with(NotificationConstants::ERROR_MSG, 'Cannot refund order. Payment not found.');
        }

        $user = auth()->user();
        if (!$user) {
            return back()->with(NotificationConstants::ERROR_MSG, 'User not authenticated.');
        }

        // Process refund through Stripe if payment reference exists
        if ($payment->payment_reference) {
            try {
                $stripeService = new \App\Services\Form\Payment\StripeService();
                $stripeService->setPayment($payment);
                $stripeService->refund(); // This also updates payment status to REFUNDED
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to process refund', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                return back()->with(NotificationConstants::ERROR_MSG, 'Failed to process refund: ' . $e->getMessage());
            }
        } else {
            // If no payment reference, just update status manually
            $payment->update([
                'status' => StatusConstants::REFUNDED,
            ]);
        }

        // Update session status to refunded
        $session->update([
            'status' => StatusConstants::REFUNDED,
        ]);

        // Create activity
        FormSessionActivity::create([
            'form_session_id' => $session->id,
            'user_id' => $user->id,
            'activity' => 'Refunded',
            'message' => "Order refunded by {$user->first_name} {$user->last_name}. Reason: {$request->reason}",
        ]);

        // Send email to customer
        $metadata = $session->metadata['raw'] ?? [];
        $customerEmail = $metadata['email'] ?? null;
        if ($customerEmail) {
            try {
                Notification::route('mail', $customerEmail)
                    ->notify(new OrderRefundedNotification($session, $request->reason));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send refund email', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with(NotificationConstants::SUCCESS_MSG, 'Order refunded successfully!');
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
