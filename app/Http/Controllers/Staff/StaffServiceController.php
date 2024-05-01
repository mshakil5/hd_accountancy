<?php

namespace App\Http\Controllers\Staff;

use DataTables;
use App\Models\Client;
use App\Models\Service;
use App\Models\WorkTime;
use Illuminate\Http\Request;
use App\Models\ClientService;
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

    public function getClientSubServices($clientserviceId)
    {
        $clientSubServices = ClientSubService::with('subService','serviceMessage','workTime')->where('client_service_id', $clientserviceId)->get();
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
            'startTime' => 'required',
            'clientSubServiceId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $startTime = $request->startTime;
        $clientSubServiceId = $request->clientSubServiceId;
        $managerId = Auth::id();

        $workTime = new WorkTime();
        $workTime->manager_id = $managerId;
        $workTime->client_sub_service_id = $clientSubServiceId;
        $workTime->start_time = $startTime;
        $workTime->created_by = $managerId;
        $workTime->save();

        return response()->json(['message' => 'Work time started successfully'], 200);
    }

    public function stopWorkTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stopTime' => 'required',
            'clientSubServiceId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $stopTime = $request->stopTime;
        $clientSubServiceId = $request->clientSubServiceId;
        $workTime = WorkTime::where('client_sub_service_id', $clientSubServiceId)->first();

        if (!$workTime) {
            return response()->json(['error' => 'No active work time found for the provided client sub-service'], 404);
        }
        $workTime->end_time = $stopTime;
        $workTime->save();

        return response()->json(['message' => 'Work time stopped successfully'], 200);
    }
}
