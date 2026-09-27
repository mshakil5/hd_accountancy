<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * All valid permission IDs.
     * 1-20 legacy modules, 21-27 Accounting submenu (one module = one ID),
     * 28 Client Credentials.
     */
    public const VALID_PERMISSIONS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28];

    public function index()
    {
        // if (auth()->user()->role_id == 1) {
        //     $roles = Role::where('id', '!=', 1)->get();
        // } else {
        //     $roles = Role::where('created_by', auth()->user()->id)
        //         ->where('id', '!=', 1)
        //         ->get();
        // }

        // Role managers (permission 17) see every role; others only their own.
        $query = Role::query();
        if (!in_array(17, $this->ownPermissions(), true)) {
            $query->where('created_by', auth()->user()->id);
        }
        $roles = $query->get();

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

        if (Role::where('name', $request->name)->exists()) {
            return response()->json(['status' => 422, 'message' => 'A role with this name already exists.']);
        }
    
        if (empty($request->permission) || !is_array($request->permission) || count($request->permission) === 0) {
            return response()->json(['status' => 422, 'message' => 'Please select at least one permission.']);
        }

        foreach ($request->permission as $perm) {
            if (!in_array((int) $perm, self::VALID_PERMISSIONS, true)) {
                return response()->json(['status' => 422, 'message' => 'Invalid permission selected.']);
            }
        }

        // A creator can only grant permissions they hold themselves (no escalation).
        $ownPermissions = $this->ownPermissions();
        foreach ($request->permission as $perm) {
            if (!in_array((int) $perm, $ownPermissions, true) && !in_array($perm, $ownPermissions)) {
                return response()->json(['status' => 422, 'message' => 'You cannot grant a permission you do not hold.']);
            }
        }
    
        $role = new Role();
        $role->name = $request->name;
        $role->permission = json_encode(array_values(array_unique($request->permission)));
        $role->created_by = Auth::user()->id;
    
        if ($role->save()) {
            return response()->json(['status' => 200, 'message' => 'Role created successfully.']);
        }
    
        return response()->json(['status' => 500, 'message' => 'Server Error!']);
    }
    
    public function edit($id)
    {
        $data = $this->editableRole($id, false);
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

        if (Role::where('name', $request->name)->where('id', '!=', $request->id)->exists()) {
            return response()->json(['status' => 422, 'message' => 'A role with this name already exists.']);
        }
    
        if (empty($request->permission) || !is_array($request->permission) || count($request->permission) === 0) {
            return response()->json(['status' => 422, 'message' => 'Please select at least one permission.']);
        }

        foreach ($request->permission as $perm) {
            if (!in_array((int) $perm, self::VALID_PERMISSIONS, true)) {
                return response()->json(['status' => 422, 'message' => 'Invalid permission selected.']);
            }
        }

        // A creator can only grant permissions they hold themselves (no escalation).
        $ownPermissions = $this->ownPermissions();
        foreach ($request->permission as $perm) {
            if (!in_array((int) $perm, $ownPermissions, true) && !in_array($perm, $ownPermissions)) {
                return response()->json(['status' => 422, 'message' => 'You cannot grant a permission you do not hold.']);
            }
        }

        $role = $this->editableRole($request->id, true);
        if ($role instanceof \Illuminate\Http\JsonResponse) {
            return $role;
        }
        $role->name = $request->name;
        $role->permission = json_encode(array_values(array_unique($request->permission)));
        $role->updated_by = Auth::user()->id;

        if ($role->save()) {
            return response()->json(['status' => 200, 'message' => 'Role Updated Successfully.']);
        }
    
        return response()->json(['status' => 500, 'message' => 'Server Error!']);
    }

    /**
     * Permissions the current user holds (as ints), null-safe.
     */
    private function ownPermissions(): array
    {
        $user = Auth::user();
        if (!$user || !$user->role) {
            return [];
        }
        $perms = json_decode($user->role->permission, true) ?: [];
        return array_map('intval', (array) $perms);
    }

    /**
     * Resolve a role for editing, or fail safely.
     * 404 on bad id; 403 when it belongs to someone else and the
     * current user is not a role manager (permission 17).
     * For AJAX ($json=true) the 403 comes back as JSON.
     */
    private function editableRole($id, bool $json = false)
    {
        $role = Role::find($id);
        if (!$role) {
            if ($json) {
                return response()->json(['status' => 404, 'message' => 'Role not found.']);
            }
            abort(404, 'Role not found.');
        }

        $own = $this->ownPermissions();
        if ((int) $role->created_by !== (int) Auth::id() && !in_array(17, $own, true)) {
            if ($json) {
                return response()->json(['status' => 403, 'message' => 'You cannot edit roles created by others.']);
            }
            abort(403, 'You cannot edit roles created by others.');
        }

        return $role;
    }
    
}
