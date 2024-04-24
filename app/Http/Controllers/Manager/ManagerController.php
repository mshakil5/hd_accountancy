<?php

namespace App\Http\Controllers\Manager;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ManagerController extends Controller
{
    public function editProfile(Request $request)
    {
         $managerId = auth()->id();
         $manager = User::findOrFail($managerId);
         return view('manager.profile.edit', compact('manager'));
    }

    public function updateProfile(Request $request)
    {
        $managerId = auth()->id();
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|numeric|digits:11',
            'email' => 'required|email|unique:users,email,' . $managerId,
            'ni_number' => 'nullable|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/',
            'date_of_birth' => 'nullable|date',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'town' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:255',
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            'password' => 'nullable|string|max:255',
            'confirm_password' => 'nullable|same:password',
        ],[
            'ni_number.regex' => 'The NI number must contain alphabet and number.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $manager = User::findOrFail($managerId);

        if ($request->hasFile('image')) {
            if (!empty($manager->image)) {
                $oldImagePath = public_path('images/manager') . '/' . $manager->image;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->move(public_path('images/manager'), $imageName);
            $manager->image = $imageName;
        }

        $manager->first_name = $request->first_name;
        $manager->last_name = $request->last_name;
        $manager->phone = $request->phone;
        $manager->email = $request->email;
        $manager->ni_number = $request->ni_number;
        $manager->date_of_birth = $request->date_of_birth;
        $manager->address_line1 = $request->address_line1;
        $manager->address_line2 = $request->address_line2;
        $manager->address_line3 = $request->address_line3;
        $manager->town = $request->town;
        $manager->postcode = $request->postcode;
        $manager->updated_by = Auth::id();

        if(isset($request->password)){
            $manager->password = Hash::make($request->password);
        }

        if ($manager->save()) {
            return response()->json(['status' => 200, 'message' => 'Manager updated successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }

    }
}
