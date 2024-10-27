<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $builder = User::role("user")->latest();
        if(!empty($key = $request->search)){
            $builder = $builder->search($key);
        }
        if(!empty($key = $request->dob)){
            $builder = $builder->where("dob",$key);
        }
        if(!empty($key = $request->sex)){
            $builder = $builder->where("sex",$key);
        }
        $users = $builder->paginate()->appends($request->query());
        return view("admin.pages.users.index" , [
            "sn" => $users->firstItem(),
            "users" => $users
        ]);
    }
}
