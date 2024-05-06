<?php

namespace App\Http\Controllers\Staff;

use DataTables;
use App\Models\Client;
use App\Models\Service;
use App\Models\WorkTime;
use Illuminate\Http\Request;
use App\Models\ClientService;
use Illuminate\Support\Carbon;
use App\Models\ServiceMessage;
use App\Models\ClientSubService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StaffServiceController extends Controller
{
    public function getServicesClientStaff(Request $request)
    {
            if ($request->ajax()) {
            $data = ClientService::with('clientSubServices')
                ->whereDate('service_deadline', '<=', now()->addDays(30))
                ->whereHas('clientSubServices', function ($query) {
                    $query->whereIn('sequence_status', [0, 1])
                          ->where('staff_id', Auth::id());
                })
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)
            
                ->addColumn('clientname', function(ClientService $clientservice) {
                    return $clientservice->client->name;
                })
                ->addColumn('servicename', function(ClientService $clientservice) {
                    return $clientservice->service->name;
                })
                ->addColumn('action', function(ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager->first_name;
                    return '<button class="btn btn-secondary change-status" data-id="'. $clientservice->id. '" data-manager-firstname="'. $managerFirstName. '">Change Status</button>';
                })
                ->make(true);
        }
    }

    public function getCompetedServices(Request $request)
    {
            if ($request->ajax()) {
            $data = ClientService::with('clientSubServices')
                ->whereDate('service_deadline', '<=', now()->addDays(30))
                ->whereHas('clientSubServices', function ($query) {
                    $query->where('sequence_status', 2)
                          ->where('staff_id', Auth::id());
                })
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)
            
                ->addColumn('clientname', function(ClientService $clientservice) {
                    return $clientservice->client->name;
                })
                ->addColumn('servicename', function(ClientService $clientservice) {
                    return $clientservice->service->name;
                })
                ->addColumn('action', function(ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager->first_name;
                    return '<button class="btn btn-secondary task-details" data-id="'. $clientservice->id. '" data-manager-firstname="'. $managerFirstName. '">Change Status</button>';
                })
                ->make(true);
        }
    }

    public function getClientSubServices($clientserviceId)
    {
        $clientSubServices = ClientSubService::with('subService','serviceMessage','workTimes')->where('client_service_id', $clientserviceId)->get();
        return response()->json($clientSubServices);
    }

    public function getServiceMessages($clientSubServiceId)
    {
        $messages = ServiceMessage::where('client_sub_service_id', $clientSubServiceId)->get();
        return response()->json($messages);
    }

    public function storeMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required',
            'client_sub_service_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        
            $serviceMessage = new ServiceMessage;
            $serviceMessage->manager_id = auth()->id(); 
            // $serviceMessage->staff_id = $request->staff_id;
            $serviceMessage->client_sub_service_id = $request->client_sub_service_id;
            $serviceMessage->message = $request->message;
            $serviceMessage->created_by = auth()->id();
            $serviceMessage->save();

        return response()->json(['success' => 'Message saved successfully.']);
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

            $nextTask = ClientSubService::where('client_service_id', $clientServiceId)->where('sequence_id', $nextService)->first();

            if (isset($nextTask)) {
                $nextTask->sequence_status = 0;
                $nextTask->updated_by = Auth::id();
                $nextTask->save();
            }

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
                    ->orderBy('created_at')
                    ->first();
        $workTime->end_time = Carbon::now();
        $workTime->start_time = Carbon::parse($workTime->start_time);
        $workTime->end_time = Carbon::parse($workTime->end_time);
        $workTime->duration = $workTime->start_time->diffInSeconds($workTime->end_time);

         $breaks = WorkTime::where('client_sub_service_id', $clientSubServiceId)
                    ->where('is_break', 1)
                    ->whereBetween('start_time', [$workTime->start_time, $workTime->end_time])
                    ->get();
        $breakDuration = $breaks->sum(function ($break) {
            $break->start_time = Carbon::parse($break->start_time);
            $break->end_time = Carbon::parse($break->end_time);
            return $break->end_time->diffInSeconds($break->start_time);
        });

        $workTime->duration -= $breakDuration;

        if ($workTime->save()) {
            $changests = ClientSubService::find($clientSubServiceId);
            $changests->status = 0;
            $changests->save();
            return response()->json(['message' => 'Work time stopped successfully'], 200);
        } else {
            return response()->json(['error' => 'Failed to stop work time.'], 422);
        }
    }

    public function startBreak(Request $request)
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
        $workTime->is_break = 1;
        $workTime->created_by = $staffId;

        if ($workTime->save()) {
            $changests = ClientSubService::find($clientSubServiceId);
            $changests->status = 3;
            $changests->save();
            return response()->json(['message' => 'Break time started successfully'], 200);
        } else {
            return response()->json(['error' => 'Failed to start work time.'], 422);
        }
    }

    public function stopBreak(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientSubServiceId' => 'required',
            'workTimesId' => 'required', 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $workTime = WorkTime::find($request->workTimesId);
        $workTime->end_time = Carbon::now();
        $startTime = Carbon::parse($workTime->start_time);
        $endTime = Carbon::parse($workTime->end_time);
        $duration = $endTime->diffInSeconds($startTime);
        $workTime->duration = $duration;

        if ($workTime->save()) {
            $clientSubServiceId = $request->clientSubServiceId;
            $changests = ClientSubService::find($clientSubServiceId);
            $changests->status = 2;
            $changests->save();

            return response()->json(['message' => 'Break time ended successfully'], 200);
        } else {
            return response()->json(['error' => 'Failed to start work time.'], 422);
        }
        
    }

    public function checkWorkTimeStatus()
    {
        $staffId = Auth::id();
        $ongoingWorkTime = WorkTime::where('staff_id', $staffId)
            ->whereNull('end_time')
            ->exists();
        if ($ongoingWorkTime) {
            return response()->json(['status' => 'ongoing']);
        } else {
            return response()->json(['status' => 'completed']);
        }
    }
}
