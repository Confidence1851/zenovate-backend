<?php

namespace App\Http\Controllers\Dashboard;

use App\Constants\NotificationConstants;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $builder = User::role("admin")->latest();
        if (!empty($key = $request->search)) {
            $builder = $builder->search($key);
        }
        if (!empty($key = $request->sex)) {
            $builder = $builder->where("sex", $key);
        }
        $users = $builder->paginate()->appends($request->query());
        return view("admin.pages.admins.index", [
            "sn" => $users->firstItem(),
            "users" => $users
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
        $data = $request->validate([
            "name" => "required|string",
            "email" => "required|string|unique:users,email",
            "sex" => "required|string",
            "password" => "required|string",
        ]);
        $data["password"] = Hash::make($data["password"]);
        $data["role"] = "admin";
        User::create($data);
        return back()->with(NotificationConstants::SUCCESS_MSG, "Admin created successfully");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
            "name" => "required|string",
            "email" => "required|string|unique:users,email,$id",
            "sex" => "required|string",
            "password" => "nullable|string",
        ]);
        if (!empty($p = $data["password"])) {
            $data["password"] = Hash::make($p);
        }else{
            unset($data["password"]);
        }
        User::where("id", $id)->update($data);
        return back()->with(NotificationConstants::SUCCESS_MSG, "Admin updated successfully");
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
