<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\TreatmentPackage;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $payments = Payment::with([
            "user",
            "package"
        ])->where(function ($query) use ($request) {
            if (!empty($key = $request->search)) {
                $query->whereHas("user", function ($user) use ($key) {
                    $user->whereRaw('CONCAT(name," ", email , " " , discount_code , " ", session_id , " ", reference) LIKE?', ["%$key%"]);
                })
                    ->orWhere("reference", "like", "%$key%")
                    ->orWhere("session_id", "like", "%$key%");
            }
            if (!empty($key = $request->package_id)) {
                $query->where("package_id", $key);
            }
        })
            ->latest()->paginate()->appends($request->query());
        $packages = TreatmentPackage::active()->get();
        return view("admin.pages.payments.index", [
            "sn" => $payments->firstItem(),
            "payments" => $payments,
            "packages" => $packages
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
        $payment = Payment::with([
            "user",
            "package"
        ])->findOrFail($id);
        return view("admin.pages.payments.show", [
            "payment" => $payment,
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
