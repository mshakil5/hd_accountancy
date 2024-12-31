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
        $data = ClientService::where('type', 2)->select('id', 'service_id', 'manager_id', 'legal_deadline', 'created_at', 'status')->orderby('id', 'DESC')->get();

        $managerAndStaffs = User::whereIn('type', [ '2', '3' ])->select('id', 'first_name', 'last_name', 'type')->orderby('id', 'DESC')->get();

        return view('admin.one_time_job.create', compact('data', 'managerAndStaffs'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task' => 'required|string',
            'manager_id' => 'required|integer',
            'legal_deadline' => 'nullable|date',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()->toArray()], 422);
        }
    
        // Create a new service
        $service = new Service();
        $service->name = $request->task;
        $service->status = 2;
        $service->created_by = auth()->id();
        $service->save();

        // Create a new client service
        $clientService = new ClientService();
        $uniqueId = date("His") . '-' . mt_rand(1000, 9999);
        $clientService->service_id = $service->id;
        $clientService->manager_id = $request->manager_id;
        $clientService->legal_deadline = $request->legal_deadline;
        $clientService->unique_id = $uniqueId;
        $clientService->type = 2;
        $clientService->save();
    
        return response()->json(['status' => 200, 'message' => 'Data saved successfully']);
    }
}
