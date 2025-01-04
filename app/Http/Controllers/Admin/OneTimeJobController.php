<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Models\ClientService;
use App\Models\ClientSubService;
use Carbon\Carbon;
use DataTables;

class OneTimeJobController extends Controller
{
    public function create()
    {
        $managerAndStaffs = User::whereIn('type', ['2', '3'])->select('id', 'first_name', 'last_name', 'type')->orderby('id', 'DESC')->get();

        return view('admin.one_time_job.create', compact('managerAndStaffs'));
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

        $service = new Service();
        $service->name = $request->task;
        $service->status = 2;
        $service->created_by = auth()->id();
        $service->save();

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

    public function getData(Request $request)
    {
        $authUserId = (string) auth()->id();

        $query = ClientService::select([
            'id',
            'type',
            'service_id',
            'manager_id',
            'legal_deadline',
            'status',
            'created_at'
        ])
            ->with([
                'service:id,name',
                'manager:id,first_name',
                'messages:id,client_service_id,viewed_by'
            ])
            ->where('type', 2)
            ->orderBy('id', 'DESC')
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
}
