<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        $data = User::where('type', 1)->orderBy('id', 'DESC')->get();
        return view('admin.admin.index', compact('data'));
    }

    public function store(Request $request)
    {
        $admin = new User;

        $admin->first_name = $request->input('first_name');
        $admin->last_name = $request->input('last_name');
        $admin->id_number = $request->input('id_number');
        $admin->phone = $request->input('phone');
        $admin->email = $request->input('email');
        $admin->ni_number = $request->input('ni_number');
        $admin->date_of_birth = $request->input('date_of_birth');
        $admin->address = $request->input('address');
        $admin->department_id = $request->input('department_id');
        $admin->job_title = $request->input('job_title');
        $admin->employment_status = $request->input('employment_status');
        $admin->joining_date = $request->input('joining_date');
        if(isset($request->password)){
            $data->password = Hash::make($request->password);
        }

        if ($request->image != 'null') {
            $request->validate([
                'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            ]);
            $rand = mt_rand(100000, 999999);
            $imageName = time(). $rand .'.'.$request->image->extension();
            $request->image->move(public_path('images/admin'), $imageName);
            $admin->image = $imageName;
        }
        $admin->save();

        return response()->json(['status' => 300, 'message' => 'Data saved successfully']);
    }

}
