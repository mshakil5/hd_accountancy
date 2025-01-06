<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function index()
    {
        if (auth()->user()->role_id == 1) {
            $roles = Role::where('id', '!=', 1)->get();
        } else {
            $roles = Role::where('created_by', auth()->user()->id)
                ->where('id', '!=', 1)
                ->get();
        }

        $user = auth()->user();

        $permissions = null;
        if ($user->role) {
            $permissions = json_decode($user->role->permission, true);
        }

        return view("admin.role.index", compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        if (empty($request->name)) {
            return response()->json(['status' => 422, 'message' => 'Please fill in the "Name" field.']);
        }
    
        if (empty($request->permission) || !is_array($request->permission) || count($request->permission) === 0) {
            return response()->json(['status' => 422, 'message' => 'Please select at least one permission.']);
        }
    
        $role = new Role();
        $role->name = $request->name;
        $role->permission = json_encode($request->permission);
        $role->created_by = Auth::user()->id;
    
        if ($role->save()) {
            return response()->json(['status' => 200, 'message' => 'Role created successfully.']);
        }
    
        return response()->json(['status' => 500, 'message' => 'Server Error!']);
    }
    
    public function edit($id)
    {
        $data = Role::where('id', $id)->first();
        $user = auth()->user();

        $permissions = null;
        if ($user->role) {
            $permissions = json_decode($user->role->permission, true);
        }
        return view("admin.role.edit", compact('data', 'permissions'));
    }

    public function update(Request $request)
    {
        if (empty($request->name)) {
            return response()->json(['status' => 422, 'message' => 'Please fill "Name" field.']);
        }
    
        if (empty($request->permission) || !is_array($request->permission) || count($request->permission) === 0) {
            return response()->json(['status' => 422, 'message' => 'Please select at least one permission.']);
        }
    
        $role = Role::find($request->id);
        $role->name = $request->name;
        $role->permission = json_encode($request->permission);
        $role->updated_by = Auth::user()->id;

        if ($role->save()) {
            return response()->json(['status' => 200, 'message' => 'Role Updated Successfully.']);
        }
    
        return response()->json(['status' => 500, 'message' => 'Server Error!']);
    }
    
}
