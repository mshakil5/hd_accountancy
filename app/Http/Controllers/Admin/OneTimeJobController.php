<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Models\ClientService;
use App\Models\ClientSubService;

class OneTimeJobController extends Controller
{
    public function create()
    {
        $services = Service::select('id', 'name')->orderby('id', 'DESC')->get();
        $managers = User::where('type', '2')->select('id', 'first_name', 'last_name')->orderby('id', 'DESC')->get();
        $staffs = User::whereIn('type', ['2', '3'])->select('id', 'first_name', 'last_name')->orderby('id', 'DESC')->get();
        return view('admin.one_time_job.create', compact('services', 'managers', 'staffs'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'services' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()->toArray()], 422);
        }

        foreach ($request->services as $serviceData) {
            $validator = Validator::make($serviceData, [
                'serviceId' => 'required|integer',
                'managerId' => 'required|integer',
                'legal_deadline' => 'required',
                'subServices' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 422, 'errors' => $validator->errors()->toArray()], 422);
            }

            $clientService = new ClientService();
            $uniqueId = date("His") . '-' . mt_rand(1000, 9999);
            $clientService->service_id = $serviceData['serviceId'];
            $clientService->manager_id = $serviceData['managerId'];
            $clientService->legal_deadline = $serviceData['legal_deadline'];
            $clientService->unique_id = $uniqueId;
            $clientService->type = 2;
            $clientService->save();

            if (isset($serviceData['subServices'])) {
                foreach ($serviceData['subServices'] as $key => $subServiceData) {
                    $clientSubService = new ClientSubService();
                    $clientSubService->client_service_id = $clientService->id;
                    $clientSubService->sub_service_id = $subServiceData['subServiceId'];
                    $clientSubService->deadline = $subServiceData['deadline'];
                    $clientSubService->note = $subServiceData['note'];
                    $clientSubService->staff_id = $subServiceData['staffId'];
                    $clientSubService->created_by = auth()->id();
                    $clientSubService->sequence_id = $key + 1;
                    if ($key === 0) {
                        $clientSubService->sequence_status = 0;
                    }
                    $clientSubService->save();
                }
            }
        }

        return response()->json(['status' => 200, 'message' => 'Data saved successfully']);
    }
}
