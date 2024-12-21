<?php

namespace App\Http\Controllers\Admin;

use DataTables;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\ClientSubService;
use App\Models\UserAttendanceLog;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StaffController extends Controller
{
    public function index()
    {
        $departments = Department::select('id', 'name')->orderby('id','DESC')->get();
        $managers = User::where('type', '2')->select('id', 'id_number', 'first_name')->orderby('id','DESC')->get();
        return view('admin.staff.index', compact('managers','departments'));
    }

    public function getStuffs(Request $request)
    {
        if ($request->ajax()) {
            $data = User::where('type', ['3'])->orderBy('id', 'DESC')->get();
            return Datatables::of($data)
                ->addIndexColumn()
                ->make(true);
        }
    }

    public function create()
    {
        $managers = User::where('type', '2')->orderby('id','DESC')->get();
        $departments = Department::orderby('id','DESC')->get();
        return view('admin.staff.create', compact('managers','departments'));
    }

    public function store(Request $request)
    {
            $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            // 'last_name' => 'required|string|max:255',
            // 'phone' => 'required|numeric|digits:11',
            'email' => 'required|email|unique:users,email',
            // 'ni_number' => 'required|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/',
            // 'date_of_birth' => 'required|date',
            // 'address_line1' => 'required|string|max:255',
            // 'address_line2' => 'required|string|max:255',
            // 'town' => 'required|string|max:255',
            // 'postcode' => 'required|string|max:255',
            // 'department_id' => 'required|integer',
            // 'job_title' => 'required|string|max:255',
            // 'employment_status' => 'required|string|max:255',
            // 'joining_date' => 'required|date',
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            // 'reporting_to' => 'required',
            'password' => 'required|string|max:255',
            'confirm_password' => 'required|same:password',
        ],[
            'ni_number.regex' => 'The NI number must contain alphabet and  number.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $data = new User;

        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->phone = $request->phone;
        $data->email = $request->email;
        $data->ni_number = $request->ni_number;
        $data->date_of_birth = $request->date_of_birth;
        $data->address_line1 = $request->address_line1;
        $data->address_line2 = $request->address_line2;
        // $data->address_line3 = $request->address_line3;
        $data->town = $request->town;
        $data->postcode = $request->postcode;
        $data->department_id = $request->department_id;
        $data->job_title  = $request->job_title;
        $data->employment_status  = $request->employment_status;
        $data->joining_date  = $request->joining_date;
        $data->reporting_to  = $request->reporting_to;
        $data->id_number = mt_rand(10000000, 99999999);

        $data->type = "3";
        $data->total_holiday = "20";
        $data->created_by =  Auth::id();

        if(isset($request->password)){
            $data->password = Hash::make($request->password);
        }

        if ($request->hasFile('image')) {
            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->move(public_path('images/staff'), $imageName);
            $data->image = $imageName;
        }

        if ($data->save()) {
            return response()->json(['status' => 200, 'message' => 'Staff created successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }

    }

    public function edit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = User::where($where)->get()->first();
        return response()->json($info);
    }

    public function update(Request $request)
    {
        if(empty($request->first_name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"First name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        // if(empty($request->last_name)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Last name \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->phone)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Phone \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if (!preg_match('/^\d{11}$/', $request->phone)) {
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Phone number must be 11 digits.</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        if(empty($request->email)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Email \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        $duplicateemail = User::where('email',$request->email)->where('id','!=', $request->codeid)->first();
        if($duplicateemail){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This email already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        // if(empty($request->ni_number)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"NI Number \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->date_of_birth)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Date of birth \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->address_line1)){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Address \" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->department_id )){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Department\" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->job_title )){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Job title\" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->employment_status )){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Employment status\" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->joining_date )){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Joining date\" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        // if(empty($request->reporting_to )){
        //     $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Reporting to\" field..!</b></div>";
        //     return response()->json(['status'=> 303,'message'=>$message]);
        //     exit();
        // }
        if(isset($request->password) && ($request->password != $request->confirm_password)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Password doesn't match.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data = User::find($request->codeid);

        if($request->image != 'null'){
            $request->validate([
                'image' => 'required|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            ]);

            if ($data->image && file_exists(public_path('images/staff/' . $data->image))) {
                unlink(public_path('images/staff/' . $data->image));
            }

            $rand = mt_rand(100000, 999999);
            $imageName = time(). $rand .'.'.$request->image->extension();
            $request->image->move(public_path('images/staff'), $imageName);
            $data->image = $imageName;
        }

        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->phone = $request->phone;
        $data->email = $request->email;
        $data->id_number = $request->id_number;
        $data->ni_number = $request->ni_number;
        $data->date_of_birth = $request->date_of_birth;
        $data->address_line1 = $request->address_line1;
        $data->address_line2 = $request->address_line2;
        $data->address_line3 = $request->address_line3;
        $data->department_id = $request->department_id;
        $data->job_title  = $request->job_title;
        $data->employment_status  = $request->employment_status;
        $data->joining_date  = $request->joining_date;
        $data->reporting_to  = $request->reporting_to;
        $data->reporting_employee_id  = $request->reporting_employee_id;
        $data->updated_by =  Auth::id();

        if(isset($request->password)){
            $data->password = Hash::make($request->password);
        }
        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Updated Successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }
        else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        } 
    }

    public function delete($id)
    {
        if(User::destroy($id)){
            return response()->json(['success'=>true,'message'=>'Staff has been deleted successfully']);
        }else{
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    }

    public function prevLogStaffs()
    {
        $previouslyLoggedStaff = UserAttendanceLog::with('user')
            ->where('start_time', '>=', now()->subDays(30))
            ->whereNotNull('end_time') 
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($log) {
                $duration = Carbon::parse($log->start_time)->diff($log->end_time);
                $log->duration = $duration->format('%H:%I:%S');
                return $log;
            });
        
        return view('admin.staff.previous_logged', compact('previouslyLoggedStaff'));

    }

    public function allPrevLogStaffs()
    {
        $previouslyLoggedStaff = UserAttendanceLog::with('user')
            ->whereNotNull('end_time') 
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($log) {
                $duration = Carbon::parse($log->start_time)->diff($log->end_time);
                $log->duration = $duration->format('%H:%I:%S');
                return $log;
            });
        
        return view('admin.staff.all_previous_logged', compact('previouslyLoggedStaff'));

    }

    public function updateLogs(Request $request, $id)
    {
        $request->validate([
            'start_time' => 'required|date_format:d-m-Y H:i:s',
            'end_time' => 'required|date_format:d-m-Y H:i:s',
            'note' => 'nullable|string'
        ]);

        $staff = UserAttendanceLog::find($id);

        $startTime = Carbon::createFromFormat('d-m-Y H:i:s', $request->input('start_time'));
        $endTime = Carbon::createFromFormat('d-m-Y H:i:s', $request->input('end_time'));

        $staff->start_time = $startTime;
        $staff->end_time = $endTime;
        $staff->note = $request->input('note');
        $staff->save();
        
        return response()->json(['success' => true, 'message' => 'Staff record updated successfully']);
    }

    public function changeStatus(Request $request)
    {
        $user = User::find($request->user_id);
        if ($user) {
            $user->status = !$user->status;
            $user->save();
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }
    }

    public function showDetails($id)
    {
        $staff = User::findOrFail($id);
        $departments = Department::orderby('id','DESC')->get();
        $managers = User::where('type', '2')->orderby('id','DESC')->get();
        return view('admin.staff.details', compact('staff', 'departments', 'managers'));
    }
    
    public function updateStaff(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|numeric|digits:11',
            'email' => 'required|email|unique:users,email,' . $id,
            'ni_number' => 'required|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/',
            'date_of_birth' => 'required|date',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'string|max:255',
            'town' => 'required|string|max:255',
            'postcode' => 'required|string|max:255',
            'department_id' => 'required|integer',
            'job_title' => 'required|string|max:255',
            'employment_status' => 'required|string|max:255',
            'joining_date' => 'required|date',
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            'reporting_to' => 'required',
            'password' => 'nullable|string|max:255',
            'confirm_password' => 'nullable|same:password',
        ],[
            'ni_number.regex' => 'The NI number must contain alphabet and number.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $staff = User::findOrFail($id);

        if ($request->hasFile('image')) {
            if (!empty($staff->image)) {
                $oldImagePath = public_path('images/staff') . '/' . $staff->image;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->move(public_path('images/staff'), $imageName);
            $staff->image = $imageName;
        }

        $staff->first_name = $request->first_name;
        $staff->last_name = $request->last_name;
        $staff->phone = $request->phone;
        $staff->email = $request->email;
        $staff->ni_number = $request->ni_number;
        $staff->date_of_birth = $request->date_of_birth;
        $staff->address_line1 = $request->address_line1;
        $staff->address_line2 = $request->address_line2;
        $staff->address_line3 = $request->address_line3;
        $staff->town = $request->town;
        $staff->postcode = $request->postcode;
        $staff->department_id = $request->department_id;
        $staff->job_title = $request->job_title;
        $staff->employment_status = $request->employment_status;
        $staff->joining_date = $request->joining_date;
        $staff->reporting_to = $request->reporting_to;

        $staff->type = "3";
        $staff->updated_by = Auth::id();

        if(isset($request->password)){
            $staff->password = Hash::make($request->password);
        }

        if ($staff->save()) {
            return response()->json(['status' => 200, 'message' => 'Staff updated successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }
    }

    public function getServicesClientStaff(Request $request)
    {
        if ($request->ajax()) {
            $data = ServiceStaff::with(['client', 'staff'])->orderBy('id', 'desc')->get();

            $data->transform(function ($item, $key) {
                $assigned_services = $item->assigned_services;
                $services_names = [];

                foreach ($assigned_services as $service_id) {
                    $service = Service::find($service_id);
                    if ($service) {
                        $services_names[] = $service->name;
                    }
                }
                $item->assigned_services = implode(', ', $services_names);

                return $item;
            });

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('client_name', function($row) {
                    return $row->client ? $row->client->name : '';
                })
                ->addColumn('tasks', function($row) {
                    return $row->assigned_services;
                })
                ->addColumn('staff_name', function($row) {
                    return $row->staff ? $row->staff->first_name : '';
                })
                ->rawColumns(['client_name', 'tasks', 'staff_name'])
                ->make(true);
        }

    }

    public function deleteStaff($id)
    {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Staff deleted successfully.'
            ]);
    }

    public function editProfile(Request $request)
    {
         $staffId = auth()->id();
         $staff = User::findOrFail($staffId);
         return view('staff.profile.edit', compact('staff'));
    }

    public function updateProfile(Request $request)
    {
        $staffId = auth()->id();
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|numeric|digits:11',
            'email' => 'required|email|unique:users,email,' . $staffId,
            'ni_number' => 'required|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/',
            'date_of_birth' => 'required|date',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'string|max:255',
            'town' => 'required|string|max:255',
            'postcode' => 'required|string|max:255',
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,pdf|max:8048',
            'password' => 'nullable|string|max:255',
            'confirm_password' => 'nullable|same:password',
        ],[
            'ni_number.regex' => 'The NI number must contain alphabet and number.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $staff = User::findOrFail($staffId);

        if ($request->hasFile('image')) {
            if (!empty($staff->image)) {
                $oldImagePath = public_path('images/staff') . '/' . $staff->image;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->move(public_path('images/staff'), $imageName);
            $staff->image = $imageName;
        }

        $staff->first_name = $request->first_name;
        $staff->last_name = $request->last_name;
        $staff->phone = $request->phone;
        $staff->email = $request->email;
        $staff->ni_number = $request->ni_number;
        $staff->date_of_birth = $request->date_of_birth;
        $staff->address_line1 = $request->address_line1;
        $staff->address_line2 = $request->address_line2;
        $staff->address_line3 = $request->address_line3;
        $staff->town = $request->town;
        $staff->postcode = $request->postcode;
        $staff->type = "3";
        $staff->updated_by = Auth::id();

        if(isset($request->password)){
            $staff->password = Hash::make($request->password);
        }

        if ($staff->save()) {
            return response()->json(['status' => 200, 'message' => 'Staff updated successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }

    }

    public function getStaffDetails($id)
    {
        $staff = User::with('department','reportingUser')->findOrFail($id);
        return response()->json($staff);
    }

    public function updateStaffPersonal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required',
            'first_name' => 'required|string',
            // 'last_name' => 'required|string',
            // 'phone' => 'required|numeric|digits:11',
            'email' => 'required|email|unique:users,email,'. $request->input('staff_id'),
            // 'ni_number' => 'required|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/',
            // 'date_of_birth' => 'required',
            // 'address_1' => 'required',
            // 'address_2' => 'required',
        ]);

         if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::find($request->staff_id);

        if ($user) {
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->phone = $request->phone;
            $user->email = $request->email;
            $user->ni_number = $request->ni_number;
            $user->date_of_birth = $request->date_of_birth;
            $user->address_line1 = $request->address_1;
            $user->address_line2 = $request->address_2;

            if ($request->hasFile('image')) {

                if ($user->image && file_exists(public_path('images/staff/' . $user->image))) {
                    unlink(public_path('images/staff/' . $user->image));
                }

                $imageName = time() . '_' . $request->image->getClientOriginalName();
                $request->image->move(public_path('images/staff'), $imageName);
                $user->image = $imageName;
            }

            $user->save();

            return response()->json(['message' => 'User details updated successfully.']);
        }

    }

    public function updateStaffJob(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required',
            // 'job_title' => 'required',
            // 'employment_status' => 'required',
            // 'reporting_id' => 'required',
            // 'joining_date' => 'required|date',
            // 'reporting_to' => 'required',
        ]);

         if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::find($request->staff_id);

        if ($user) {
            $user->department_id = $request->department;
            $user->job_title = $request->job_title;
            $user->employment_status = $request->employment_status;
            $user->joining_date = $request->joining_date;
            $user->reporting_to = $request->reporting_to;
            $user->reporting_employee_id = $request->reporting_id;
            $user->save();

            return response()->json(['message' => 'User details updated successfully.']);
        }
    }

    public function staffTaskDetails($staffId)
    {
        $user = User::findOrFail($staffId);
        $staffName = $user->first_name. ' '. $user->last_name;
        $today = now()->startOfDay();
        $clientSubServices = ClientSubService::with(['workTimes', 'client', 'subService'])
            ->where('staff_id', $staffId)
            ->whereDate('updated_at', $today)
            ->get();

        return view('admin.staff.task_details', compact('clientSubServices','staffName'));
    }

    public function updateClientService(Request $request)
    {
        $request->validate([
            'clientSubServiceId' => 'required',
        ]);

        $clientSubService = ClientSubService::find($request->clientSubServiceId);
        if ($clientSubService) {
            $clientSubService->sequence_status = $request->sequence_status;
            $clientSubService->status = 1;
            $clientSubService->save();

            return response()->json(['status' => 'success', 'message' => 'Sequence status updated successfully']);
        }

        return response()->json(['status' => 'error', 'message' => 'Client Sub Service not found'], 404);
    }

}
