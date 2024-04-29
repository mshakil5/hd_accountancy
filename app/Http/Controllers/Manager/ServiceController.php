<?php

namespace App\Http\Controllers\Manager;

use DataTables;
use Illuminate\Http\Request;
use App\Models\ClientService;
use App\Models\ClientSubService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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
        $clientSubServices = ClientSubService::with('subService')->where('client_service_id', $clientserviceId)->get();
        return response()->json($clientSubServices);
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
}
