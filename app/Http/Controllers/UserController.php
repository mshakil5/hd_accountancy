<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function usersIndex()
    {
        $users = User::where('type', 0)->orderby('id','DESC')->get();
        return view('admin.user.index', compact('users'));
    }

    public function userCreate()
    {
        $departments = Department::all();
        return view('admin.user.create', compact('departments'));   
    }

    public function userStore(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'id_number' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'required|string|email|unique:users|max:255',
            'ni_number' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'job_title' => 'nullable|string|max:255',
            'employment_status' => 'nullable|string|max:255',
            'joining_date' => 'nullable|date',
            'reporting_to' => 'nullable|string|max:255',
            'reporting_employee_id' => 'nullable|integer',
            'type' => 'nullable|integer',
            'password' => 'required|string|min:6|confirmed',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        if ($request->hasFile('image')) {
            $fileName = $request->file('image')->hashName();
            $request->file('image')->storeAs('public/images/profile', $fileName);
        } else {
            $fileName = null;
        }

        $user = new User();
        $user->first_name = $validatedData['first_name'];
        $user->last_name = $validatedData['last_name'];
        $user->id_number = $validatedData['id_number'];
        $user->phone = $validatedData['phone'];
        $user->email = $validatedData['email'];
        $user->ni_number = $validatedData['ni_number'];
        $user->date_of_birth = $validatedData['date_of_birth'];
        $user->address = $validatedData['address'];
        $user->department_id = $validatedData['department_id'];
        $user->job_title = $validatedData['job_title'];
        $user->employment_status = $validatedData['employment_status'];
        $user->joining_date = $validatedData['joining_date'];
        $user->reporting_to = $validatedData['reporting_to'];
        $user->reporting_employee_id = $validatedData['reporting_employee_id'];
        $user->type = $validatedData['type'];
        $user->password = bcrypt($validatedData['password']);
        $user->image = $fileNameToStore;
        $user->save();

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }
}
