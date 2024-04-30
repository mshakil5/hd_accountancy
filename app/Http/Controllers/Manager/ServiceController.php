<?php

namespace App\Http\Controllers\Manager;

use DataTables;
use Illuminate\Http\Request;
use App\Models\ClientService;
use App\Models\ServiceMessage;
use App\Models\ClientSubService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function getAllAssignedServices(Request $request)
    {
        $currentUserId = Auth::id();
        $managerName = Auth::user()->first_name;
            if ($request->ajax()) {
            $data = ClientService::with('clientSubServices')
                ->where('manager_id', $currentUserId)
                ->whereDate('service_deadline', '<=', now()->addDays(30))
                ->whereHas('clientSubServices', function ($query) {
                    $query->whereIn('sequence_status', [0, 1]);
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
                ->addColumn('action', function(ClientService $clientservice) use ($managerName) {
                    return '<button class="btn btn-secondary change-status" data-id="' . $clientservice->id . '" data-manager="' . $managerName . '">Change Status</button>';
                })
                ->make(true);
        }
    }

    public function getClientSubServices($clientserviceId)
    {
        $clientSubServices = ClientSubService::with('subService','serviceMessage')->where('client_service_id', $clientserviceId)->get();
        return response()->json($clientSubServices);
    }

    public function getServiceMessages($clientSubServiceId)
    {
        $messages = ServiceMessage::where('client_sub_service_id', $clientSubServiceId)->get();
        return response()->json($messages);
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
            $serviceMessage->save();

        return response()->json(['success' => 'Message saved successfully.']);
    }

}
