<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;
use App\Models\SubService;
use App\Models\ClientService;
use DataTables;
use Carbon\Carbon;
use App\Models\ClientSubService;
use Illuminate\Support\Facades\Validator;
use App\Models\WorkTime;
use App\Models\ServiceMessage;

class AdminController extends Controller
{
    public function getAdmin()
    {
        $data = User::where('type', '1')->select('id', 'first_name', 'last_name', 'email', 'phone')->orderby('id','DESC')->get();
        return view('admin.admin.index', compact('data'));
    }

    public function adminStore(Request $request)
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
        $chkemail = User::where('email',$request->email)->first();
        if($chkemail){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This email was already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(empty($request->password)){            
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Password\" field..!</b></div>"; 
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if (strlen($request->password) < 6) {
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Password must be at least 6 characters long.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        if(isset($request->password) && ($request->password != $request->confirm_password)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Password doesn't match.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        
        $data = new User;
        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->phone = $request->phone;
        $data->email = $request->email;
        $data->type = "1";
        $data->created_by =  Auth::id();
        if(isset($request->password)){
            $data->password = Hash::make($request->password);
        }
        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Admin created successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        }
    }

    public function adminEdit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = User::where($where)->select('id', 'first_name', 'last_name', 'phone', 'email')->first();
        return response()->json($info);
    }

    public function adminUpdate(Request $request)
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
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This email was already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }    
        if(isset($request->password) && ($request->password != $request->confirm_password || strlen($request->password) < 6)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Password doesn't match or is less than 6 characters.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data = User::find($request->codeid);
        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->phone = $request->phone;
        $data->email = $request->email;
        $data->updated_by = Auth::id();
        if(isset($request->password)){
            $data->password = Hash::make($request->password);
        }
        if ($data->save()) {
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Admin Updated Successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }
        else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        } 
    }

    public function adminDelete($id)
    {
        if(User::destroy($id)){
            return response()->json(['success'=>true,'message'=>'Admin has been deleted successfully']);
        }else{
            return response()->json(['success'=>false,'message'=>'Delete Failed']);
        }
    }
    
    public function getAdminTasks()
    {
        $users = User::whereIn('type', ['3','2', '1'])->select('id', 'first_name', 'last_name', 'type')->orderby('id','DESC')->get();
        $clients = Client::orderBy('id', 'DESC')->select('id', 'name', 'refid')->get();
        $staffs = User::whereIn('type', ['3','2', '1'])->select('id', 'first_name', 'last_name')->orderby('id','DESC')->get();
        $subServices = SubService::orderby('id','DESC')->select('id', 'name')->get();
        return view('admin.tasks.index', compact('users', 'clients', 'staffs', 'subServices'));
    }

    public function getAllAssignedServices(Request $request)
    {
        $currentUserId = Auth::id();
    
        if ($request->ajax()) {
            $data = ClientService::where('type', '!=', 2)
            ->where('due_date', '<=', now()->endOfDay())
            ->with(['clientSubServices' => function ($query) {
                    $query->where('staff_id', Auth::id());
                        //   ->whereIn('sequence_status', [0, 1]);
                }])
                ->where(function ($query) use ($currentUserId) {
                    $query->where('manager_id', $currentUserId)
                          ->orWhereHas('clientSubServices', function ($subQuery) use ($currentUserId) {
                              $subQuery->where('staff_id', $currentUserId)
                                       ->whereIn('sequence_status', [0, 1]);
                          });
                })
                ->whereIn('status', [0, 1])
                ->distinct()
                ->orderBy('id', 'desc')
                ->get();
    
            return DataTables::of($data)
                ->addColumn('clientname', function (ClientService $clientservice) {
                    return $clientservice->client ? $clientservice->client->name : '';
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
                })
                ->addColumn('status', function (ClientService $clientservice) {
                    return $clientservice->status;
                })
                ->addColumn('action', function (ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager 
                        ? $clientservice->manager->first_name . ' ' . $clientservice->manager->last_name 
                        : 'N/A';
                    return '<button class="btn btn-secondary change-status" data-id="' 
                        . $clientservice->id . '" data-manager-firstname="' . $managerFirstName . '">Details</button>';
                })
                ->make(true);
        }
    } 

    public function getNotes(Request $request)
    {
        $notes = Note::where('user_id', Auth::id())->latest()->get();

        return DataTables::of($notes)
            ->addColumn('sl', function ($note) {
                return '';
            })
            ->addColumn('content', function ($note) {
                return $note->content;
            })
            ->addColumn('action', function ($note) {
                return '<button class="btn btn-primary action-btn" data-note="' . $note->content . '" data-id="' . $note->id . '">Assign</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getCompetedServices(Request $request)
    {
        $managerName = Auth::user()->first_name;

        if ($request->ajax()) {
            $data = ClientService::where('type', '!=', 2)->with('clientSubServices')
                ->whereHas('clientSubServices', function ($query) {
                    $query->where('sequence_status', 2)
                        ->where('staff_id', Auth::id());
                })
                ->where('due_date', '<=', now()->endOfDay())
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)

                ->addColumn('clientname', function (ClientService $clientservice) {
                    return $clientservice->client ? $clientservice->client->name : '';
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
                })
                ->addColumn('action', function (ClientService $clientservice) use ($managerName) {
                    return '<button class="btn btn-secondary task-details" data-id="' . $clientservice->id . '" data-manager="' . $managerName . '">Details</button>';
                })
                ->make(true);
        }
    }

    public function getCompetedServicesAsManager(Request $request)
    {
        $managerName = Auth::user()->first_name;

        if ($request->ajax()) {
            $data = ClientService::where('type', '!=', 2)->with('clientSubServices')
                ->where('manager_id', Auth::id())
                ->where('status', 2)
                ->where('due_date', '<=', now()->endOfDay())
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)

                ->addColumn('clientname', function (ClientService $clientservice) {
                    return $clientservice->client ? $clientservice->client->name : '';
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
                })
                ->addColumn('action', function (ClientService $clientservice) use ($managerName) {
                    return '<button class="btn btn-secondary task-details1" data-id="' . $clientservice->id . '" data-manager="' . $managerName . '">Details</button>';
                })
                ->make(true);
        }
    }

    public function getOneTimeJobs(Request $request)
    {
        $authUserId = (string) auth()->id();
        $query = ClientService::where('type', 2)
            ->with(['service', 'messages'])
            ->where('manager_id', Auth::id())
            ->get()
            ->map(function ($clientService) use ($authUserId) {
                $clientService->has_new_message = $clientService->messages->contains(function ($message) use ($authUserId) {
                    $viewedBy = json_decode($message->viewed_by, true) ?? [];
                    return !in_array($authUserId, $viewedBy);
                });
    
                return $clientService;
            });

        return DataTables::of($query)
            ->editColumn('servicename', function ($clientService) {
                return $clientService->service->name;
            })
            ->editColumn('legal_deadline', function ($clientService) {
                return Carbon::parse($clientService->legal_deadline)->format('d-m-Y');
            })
            ->editColumn('status', function ($clientService) {
                return $clientService->status;
            })
            ->addColumn('has_new_message', function ($clientService) {
                return $clientService->has_new_message ? 'Yes' : 'No';
            })
            ->make(true);
    }

    public function getClientSubServices($clientserviceId)
    {
        $authUserId = auth()->id();
        
        $clientSubServices = ClientSubService::with('subService', 'serviceMessage', 'workTimes', 'staff', 'clientService')
            ->where('client_service_id', $clientserviceId)
            ->get()
            ->map(function ($subService) use ($authUserId) {
                $subService->has_new_message = $subService->serviceMessage->contains(function ($message) use ($authUserId) {
                    $viewedBy = json_decode($message->viewed_by, true) ?? [];
                    return !in_array($authUserId, $viewedBy);
                });

                return $subService;
            });

        return response()->json($clientSubServices);
    }

    public function changeServiceStatus(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'status' => 'required',
        ]);

        $clientService = ClientService::findOrFail($request->id);

        $clientService->status = $request->status;
        $clientService->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.'
        ]);
    }

    public function updateSubServiceStaff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientSubServiceId' => 'required',
            'newStaffId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $clientSubService = ClientSubService::findOrFail($request->clientSubServiceId);

        $clientSubService->staff_id = $request->newStaffId;
        $clientSubService->save();

        return response()->json([
            'message' => 'Staff updated successfully'
        ], 200);
    }

    public function updateSubServiceStatus(Request $request)
    {
        $clientSubServiceId = $request->input('clientSubServiceId');
        $newStatus = $request->input('newStatus');
        $clientSubService = ClientSubService::find($clientSubServiceId);
        $clientServiceId = $clientSubService->client_service_id;
        $serviceSequenceNo = $clientSubService->sequence_id;
        $nextService = $serviceSequenceNo + 1;
        if ($clientSubService) {
            $clientSubService->sequence_status = $newStatus;
            $clientSubService->updated_by = Auth::id();
            $clientSubService->save();

            $maxSequenceNo = ClientSubService::where('client_service_id', $clientServiceId)->max('sequence_id');
    
            if ($nextService <= $maxSequenceNo) {
                $nextTask = ClientSubService::where('client_service_id', $clientServiceId)
                    ->where('sequence_id', $nextService)
                    ->first();
    
                if ($nextTask && $nextTask->sequence_status != 2) {
                    $nextTask->sequence_status = 0;
                    $nextTask->updated_by = Auth::id();
                    $nextTask->save();
                }
            }
    
            return response()->json(['message' => 'Status updated successfully']);
        } else {
            return response()->json(['error' => 'Client sub-service not found'], 404);
        }
    }

    public function changeSubServiceStatus(Request $request)
    {
        $clientSubServiceId = $request->input('clientSubServiceId');
        $newStatus = $request->input('newStatus');
        $clientSubService = ClientSubService::find($clientSubServiceId);

        if ($clientSubService) {
            $clientSubService->sequence_status = $newStatus;
            $clientSubService->status = 1;
            $clientSubService->updated_by = Auth::id();
            $clientSubService->save();

            return response()->json(['message' => 'Status updated successfully']);
        } else {
            return response()->json(['error' => 'Client sub-service not found'], 404);
        }
    }

    public function startWorkTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientSubServiceId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $clientSubServiceId = $request->clientSubServiceId;
        $staffId = Auth::id();

        $workTime = new WorkTime();
        $workTime->staff_id = $staffId;
        $workTime->client_sub_service_id = $clientSubServiceId;
        $workTime->start_time = Carbon::now();
        $workTime->start_date = Carbon::today()->format('d-m-Y');
        $workTime->created_by = $staffId;

        if ($workTime->save()) {
            $changests = ClientSubService::find($clientSubServiceId);
            $changests->status = 2;
            $changests->save();
            return response()->json(['message' => 'Work time started successfully'], 200);
        } else {
            return response()->json(['error' => 'Failed to start work time.'], 422);
        }
    }

    public function stopWorkTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientSubServiceId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $clientSubServiceId = $request->clientSubServiceId;
        $workTime = WorkTime::where('client_sub_service_id', $clientSubServiceId)
            ->where('is_break', 0)
            ->orderBy('id', 'DESC')
            ->first();
        $startDateTime = Carbon::parse($workTime->start_time);
        $endDateTime = Carbon::parse($workTime->end_time);

        $totalDurationExcludingBreaks = $startDateTime->diffInSeconds($endDateTime);

        $breaks = WorkTime::where('client_sub_service_id', $clientSubServiceId)
            ->where('is_break', 1)
            ->sum('duration');

        $adjustedDuration = $totalDurationExcludingBreaks - $breaks;
        $workTime->duration = $adjustedDuration;
        $workTime->end_time = Carbon::now();

        if ($workTime->save()) {
            $changests = ClientSubService::find($clientSubServiceId);
            $changests->status = 0;
            $changests->save();
            return response()->json(['message' => 'Work time stopped successfully'], 200);
        } else {
            return response()->json(['error' => 'Failed to stop work time.'], 422);
        }
    }

    public function getServiceMessages($clientSubServiceId)
    {
        $authUserId = (string) auth()->id();
        
        $messages = ServiceMessage::with('user:id,first_name')
            ->where('client_sub_service_id', $clientSubServiceId)
            ->get()
            ->map(function ($message) use ($authUserId) {
                $viewedBy = $message->viewed_by ? json_decode($message->viewed_by, true) : [];
    
                if (!in_array($authUserId, $viewedBy)) {
                    $viewedBy[] = $authUserId;
                }
    
                $viewedBy = array_map('strval', $viewedBy);
                $viewedBy = array_unique($viewedBy);
    
                $message->viewed_by = json_encode($viewedBy);
                $message->save();
    
                return [
                    'userName' => $message->user->first_name,
                    'messageContent' => $message->message,
                ];
            });
    
        return response()->json($messages);
    }

    public function storeMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required',
            'staff_id' => 'required',
            'client_sub_service_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $serviceMessage = new ServiceMessage;
        $serviceMessage->manager_id = auth()->id();
        $serviceMessage->staff_id = $request->staff_id;
        $serviceMessage->client_sub_service_id = $request->client_sub_service_id;
        $serviceMessage->message = $request->message;
        $serviceMessage->created_by = auth()->id();
        $serviceMessage->viewed_by = json_encode([strval(auth()->id())]);
        $serviceMessage->save();

        return response()->json(['success' => 'Message saved successfully.']);
    }

    public function updateJobStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|integer|in:0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => 'Invalid status'], 422);
        }

        $clientService = ClientService::find($id);
        if (!$clientService) {
            return response()->json(['status' => 404, 'message' => 'Job not found'], 404);
        }

        $clientService->status = $request->status;
        $clientService->save();

        return response()->json(['status' => 200, 'message' => 'Status updated successfully']);
    }

    public function getServiceComment($clientServiceId)
    {
        $authUserId = (string) auth()->id();
        
        $messages = ServiceMessage::with('user:id,first_name')
            ->where('client_service_id', $clientServiceId)
            ->get()
            ->map(function ($message) use ($authUserId) {
                $viewedBy = $message->viewed_by ? json_decode($message->viewed_by, true) : []; 
    
                if (!in_array($authUserId, $viewedBy)) {
                    $viewedBy[] = $authUserId;
                }
    
                $viewedBy = array_map('strval', $viewedBy);
                $viewedBy = array_unique($viewedBy);
    
                $message->viewed_by = json_encode($viewedBy); 
    
                $message->save();
    
                return [
                    'userName' => $message->user->first_name,
                    'messageContent' => $message->message,
                ];
            });
    
        return response()->json($messages);
    } 

    public function storeComment(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'client_service_id' => 'required|integer',
        ]);

        $serviceMessage = new ServiceMessage();
        $serviceMessage->message = $validated['message'];
        $serviceMessage->client_service_id = $validated['client_service_id'];
        $serviceMessage->created_by = auth()->id();
        $serviceMessage->viewed_by = json_encode([strval(auth()->id())]);
        $serviceMessage->save();

        return response()->json(['success' => true, 'message' => 'Message saved successfully']);
    }
}
