<?php

namespace App\Http\Controllers\Admin;

use DataTables;
use App\Models\Client;
use App\Models\Service;
use App\Models\SubService;
use App\Models\ServiceStaff;
use Illuminate\Http\Request;
use App\Models\ClientService;
use App\Models\ClientSubService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function index()
    {
        $data = Service::with('subServices')->orderBy('id', 'DESC')->get();
        $allsubServices = SubService::orderby('id','DESC')->get();
        return view('admin.service.index', compact('data','allsubServices'));
    }

    public function store(Request $request)
    {
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Name \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $chkname = Service::where('name',$request->name)->first();
        if($chkname){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This name already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }
        $data = new Service;
        $data->name = $request->name;
        $data->created_by = Auth::id();
        if ($data->save()) {
            $serviceId = $data->id;
            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Created Successfully.</b></div>";
             return response()->json(['status' => 300, 'message' => $message, 'service_id' => $serviceId]);
        }else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        }
    }

    public function edit($id)
    {
        $where = [
            'id'=>$id
        ];
        $info = Service::with('subServices')->find($id);
        return response()->json($info);
    }

    public function update(Request $request)
    {
        if(empty($request->name)){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Username \" field..!</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $duplicatename = Service::where('name',$request->name)->where('id','!=', $request->codeid)->first();
        if($duplicatename){
            $message ="<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This name already added.</b></div>";
            return response()->json(['status'=> 303,'message'=>$message]);
            exit();
        }

        $data = Service::find($request->codeid);
        $data->name = $request->name;
        $data->updated_by = Auth::id();
        if ($data->save()) {

            $newlyCreatedSubServiceIds = [];
            foreach ($request->current_sub_service_names as $index => $name) {
                if (isset($request->current_sub_service_ids[$index])) {
                    $id = $request->current_sub_service_ids[$index];

                    $subService = SubService::find($id);
                    // editing sub services
                    if ($subService) {
                        $subService->name = $name;
                        $subService->save();
                    }
                }
            }

            //if that subservice not found in databse
            $existingSubServiceNames = SubService::where('service_id', $request->codeid)->pluck('name')->toArray();
            $newSubServiceNames = array_diff($request->current_sub_service_names, $existingSubServiceNames);

            foreach ($newSubServiceNames as $name) {
                $existingSubService = SubService::where('service_id', $request->codeid)->where('name', $name)->first();
                // create new sub service
                if (!$existingSubService) {
                    $newSubService = new SubService();
                    $newSubService->service_id = $request->codeid;
                    $newSubService->name = $name;
                    $newSubService->save();
                    $newlyCreatedSubServiceIds[] = $newSubService->id;
                }
            }

            // deleting removed sub services
            $existingSubServiceIds = SubService::where('service_id', $request->codeid)->pluck('id')->toArray();
            $updatedSubServiceIds = $request->current_sub_service_ids;
            $subServicesToDelete = array_diff($existingSubServiceIds, $updatedSubServiceIds, $newlyCreatedSubServiceIds);
            SubService::whereIn('id', $subServicesToDelete)->delete();

            $message ="<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Updated Successfully.</b></div>";
            return response()->json(['status'=> 300,'message'=>$message]);
        }
        else{
            return response()->json(['status'=> 303,'message'=>'Server Error!!']);
        } 
    }

    public function checkAssignment(Request $request)
    {
        $subServiceId = $request->input('id');

        $isAssigned = DB::table('client_sub_services')
            ->where('sub_service_id', $subServiceId)
            ->exists();

        return response()->json(['assigned' => $isAssigned]);
    }

    public function delete($id)
    {
        $service = Service::find($id);

        if ($service) {
            $hasAssignedSubServices = DB::table('client_sub_services')
                ->where('sub_service_id', $service->subServices()->pluck('id')->toArray())
                ->exists();
    
            if ($hasAssignedSubServices) {
                return response()->json(['success' => false, 'message' => 'Cannot delete service because one or more sub-services are assigned to clients.'], 403);
            }
    
            $service->subServices()->delete();
            $service->delete();
            return response()->json(['success' => true, 'message' => 'Data has been deleted successfully']);
        } else {
            return response()->json(['success' => false, 'message' => 'Service not found']);
        }
    }

    public function serviceAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required',
            'services' => 'required|array|min:1',
            'deadline' => 'required|date',
        ], [
            'deadline.required' => 'The deadline field is required.',
            'deadline.date' => 'The deadline must be a valid date.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $client = Client::find($request->client_id);

        if (!$client) {
            return response()->json(['status' => 500, 'message' => 'Client not found'], 500);
        }

        $clientService = ClientService::where('client_id', $request->client_id)->first();
        if ($clientService) {
            $clientService->assigned_services = $request->input('services');
            $clientService->deadline = $request->deadline;
            $clientService->save();
        } else {
            $clientService = new ClientService();
            $clientService->client_id = $request->client_id;
            $clientService->assigned_services = $request->input('services');
            $clientService->deadline = $request->deadline;
            $clientService->save();
        }

        return response()->json(['status' => 200, 'message' => 'Services assigned successfully']);
    }

    public function getAssignedServices($client_id)
    {
        $clientService = ClientService::where('client_id', $client_id)->first();

        if (!$clientService) {
            return response()->json(['status' => 'error', 'message' => 'Client service not found'], 404);
        }

        $serviceIds = $clientService->assigned_services;

        $assignedServices = Service::whereIn('id', $serviceIds)->get();

        $response = [
            'status' => 'success',
            'assigned_services' => $assignedServices,
            'deadline' => $clientService->deadline
        ];

        return response()->json($response);
    }

    public function assignServiceStaff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required',
            'staff_id' => 'required',
            'deadline' => 'required|date',
            'note' => 'required|string',
            'assigned_services' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
            }

            $serviceStaff = new ServiceStaff;

            $serviceStaff->client_id = $request->client_id;
            $serviceStaff->staff_id = $request->staff_id;
            $serviceStaff->assigned_services = $request->assigned_services;
            $serviceStaff->deadline = $request->deadline;
            $serviceStaff->note = $request->note;
            $serviceStaff->created_by = auth()->user()->id;
            
            if ($serviceStaff->save()) {

                $clientServiceId = $request->client_service_id;

                $clientService = ClientService::find($clientServiceId);
                    if ($clientService) {
                        $clientService->status = 0;
                        $clientService->save();
                    }

                return response()->json(['status' => 200, 'message' => 'Service staff assigned successfully.'], 200);
            } else {
                return response()->json(['status' => 500, 'message' => 'Failed to assign service staff.'], 500);
            }

    }

    public function getAllServices()
    {
         $services = Service::orderBy('id', 'desc')->get();
         return response()->json(['status' => 200, 'services' => $services], 200);
    }

    public function createSpecificService(Request $request) 
    {
        $validator = Validator::make($request->all(), [
           'name' => 'required|string|max:255|unique:services',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $service = new Service;
        $service->name = $request->input('name');

        if ($service->save()) {
            return response()->json(['status' => 200, 'message' => 'Specific service created successfully'], 200);
        } else {
            return response()->json(['status' => 500, 'message' => 'Failed to create'], 500);
        }
    }

    public function getServicesClientStaff(Request $request)
    {
        if ($request->ajax()) {
            $data = ServiceStaff::with(['client', 'staff'])->where('status', 1)->orWhere('status', 3)->orderBy('id', 'desc')->get();

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
                ->addColumn('deadline', function($row) {
                    return $row->deadline;
                })
                ->rawColumns(['client_name', 'tasks', 'staff_name'])
                ->make(true);
        }
    }

    public function getAllAssignedServices(Request $request)
    {
        if ($request->ajax()) {
            $data = ClientService::with('client', 'manager', 'service', 'clientSubServices')
            ->where('status', 1)
            ->whereDate('service_deadline', '<=', now()->addDays(30))
            ->whereHas('clientSubServices', function ($query) {
                $query->whereNull('staff_id');
            })
            ->orderBy('id', 'desc')
            ->get();

            return DataTables::of($data)
                ->make(true);
        }
    }

    public function updateServiceStatus(Request $request)
    {
        if ($request->ajax()) {
            $serviceStaff = ServiceStaff::find($request->input('service_staff_id'));
            if ($serviceStaff) {
                $serviceStaff->status = $request->input('status');
                $serviceStaff->save();
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false]);
        }
    }

    public function getCompletedServices(Request $request)
    {
        if ($request->ajax()) {
            $data = ClientService::with('clientSubServices')
                ->whereDate('service_deadline', '<=', now()->addDays(30))
                ->whereHas('clientSubServices', function ($query) {
                    $query->where('sequence_status', 2);
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
                    return '<button class="btn btn-secondary task-details" data-id="'. $clientservice->id. '" data-manager-firstname="'. $managerFirstName. '">Details</button>';
                })
                ->make(true);
        }

    }

    public function getClientSubService($clientserviceId)
    {
        $clientSubServices = ClientSubService::with('subService','serviceMessage','workTimes')->where('client_service_id', $clientserviceId)->get();
        return response()->json($clientSubServices);
    }

    public function getSubServices($serviceId)
    {
        $service = Service::find($serviceId);
        $subServices = SubService::where('service_id',$serviceId)->select('id', 'name')->get();
        return response()->json($subServices);
    }

    public function saveService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientId' => 'required|integer',
            // 'services' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()->toArray()], 422);
        }

        foreach ($request->services as $serviceData) {
            $validator = Validator::make($serviceData, [
                // 'serviceId' => 'required|integer',
                // 'managerId' => 'required|integer',
                // 'service_frequency' => 'required',
                // 'service_deadline' => 'required',
                // 'due_date' => 'required',
                // 'legal_deadline' => 'required',
                // 'subServices' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 422, 'errors' => $validator->errors()->toArray()], 422);
            }

            $clientService = new ClientService();
            $uniqueId = date("His") . '-' . $request->clientId;
            $clientService->client_id = $request->clientId;
            $clientService->service_id = $serviceData['serviceId'];
            $clientService->manager_id = $serviceData['managerId'];
            $clientService->service_frequency = $serviceData['service_frequency'];
            $clientService->service_deadline = $serviceData['service_deadline'];
            $clientService->due_date = $serviceData['due_date'];
            $clientService->legal_deadline = $serviceData['legal_deadline'];
            $clientService->unique_id = $uniqueId;
            $clientService->save();

            if (isset($serviceData['subServices'])) {
                foreach ($serviceData['subServices'] as $key => $subServiceData) {
                    $clientSubService = new ClientSubService();
                    $clientSubService->client_service_id = $clientService->id;
                    $clientSubService->client_id = $request->clientId;
                    $clientSubService->sequence_id = $key + 1;
                    $clientSubService->sub_service_id = $subServiceData['subServiceId'];
                    $clientSubService->deadline = $subServiceData['deadline'];
                    $clientSubService->note = $subServiceData['note'];
                    $clientSubService->staff_id = $subServiceData['staffId'];
                    $clientSubService->created_by = auth()->id();

                    if ($key === 0) {
                        $clientSubService->sequence_status = 0;
                    }
                    $clientSubService->save();
                }
            }
        }

        return response()->json(['status' => 200, 'message' => 'Data saved successfully']);
    }

    public function updateService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientId' => 'required|integer',
            'services' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()->toArray()], 422);
        }

        foreach ($request->services as $serviceData) {
            if (!isset($serviceData['client_service_id'])) {
                $clientService = new ClientService();
                $clientService->client_id = $request->clientId;
                $clientService->service_id = $serviceData['serviceId']; 
                $clientService->manager_id = $serviceData['managerId'];
                $clientService->service_frequency = $serviceData['service_frequency'];
                $clientService->service_deadline = $serviceData['service_deadline'];
                $clientService->due_date = $serviceData['due_date'];
                $clientService->legal_deadline = $serviceData['legal_deadline'];
                $clientService->save();
                
                $serviceData['client_service_id'] = $clientService->id;
            } else {
                $existingService = ClientService::find($serviceData['client_service_id']);
                if ($existingService) {
                    $existingService->update([
                        'manager_id' => $serviceData['managerId'],
                        'service_frequency' => $serviceData['service_frequency'],
                        'service_deadline' => $serviceData['service_deadline'],
                        'due_date' => $serviceData['due_date'],
                        'legal_deadline' => $serviceData['legal_deadline'],
                    ]);
                }
            }

            $clientServiceIds = collect($request->services)->pluck('client_service_id')->toArray();
            ClientService::where('client_id', $request->clientId)
                        ->whereNotIn('id', $clientServiceIds)
                        ->delete();

            if (isset($serviceData['subServices'])) {
                foreach ($serviceData['subServices'] as $key => $subServiceData) {
                    if (!isset($subServiceData['client_sub_service_id'])) {
                        $clientSubService = new ClientSubService();
                        $clientSubService->client_service_id = $serviceData['client_service_id']; 
                        $clientSubService->client_id = $request->clientId;
                        $clientSubService->sequence_id = $key + 1;
                        $clientSubService->sub_service_id = $subServiceData['subServiceId'];
                        $clientSubService->deadline = $subServiceData['deadline'];
                        $clientSubService->note = $subServiceData['note'];
                        $clientSubService->staff_id = $subServiceData['staffId'];
                        $clientSubService->save();
                    } else {
                        $existingSubService = ClientSubService::find($subServiceData['client_sub_service_id']);
                        if ($existingSubService) {
                            $existingSubService->update([
                                'deadline' => $subServiceData['deadline'],
                                'note' => $subServiceData['note'],
                                'staff_id' => $subServiceData['staffId'],
                            ]);
                        }
                    }
                }
            }

            $clientSubServiceIds = collect($serviceData['subServices'])->pluck('client_sub_service_id')->toArray();
            ClientSubService::where('client_service_id', $serviceData['client_service_id'])
                            ->whereNotIn('id', $clientSubServiceIds)
                            ->delete();
        }

        return response()->json(['status' => 200, 'message' => 'Data updated successfully']);
    }
    
    public function updateStaffService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientId' => 'required|integer',
            'serviceId' => 'required|integer',
            'managerId' => 'required|integer',
            'service_frequency' => 'required',
            'service_deadline' => 'required',
            'subServices' => 'required|array',
            'subServices.*.subServiceId' => 'required|integer',
            'subServices.*.deadline' => 'required',
            'subServices.*.staffId' => 'required|integer',
            'subServices.*.note' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $clientService = ClientService::where('client_id', $request->clientId)
                                        ->where('service_id', $request->serviceId)
                                        ->first();

        if ($clientService) {
            $clientService->manager_id = $request->managerId;
            $clientService->service_frequency = $request->service_frequency;
            $clientService->service_deadline = $request->service_deadline;
            $clientService->save();
        } else {
            return response()->json(['status' => 404, 'message' => 'Client service not found'], 404);
        }

        if ($request->has('subServices')) {
            foreach ($request->subServices as $key => $subServiceData) {
                $serialKey = $key + 1;
                $clientSubService = ClientSubService::where('client_service_id', $clientService->id)
                                                        ->where('sub_service_id', $subServiceData['subServiceId'])
                                                        ->first();

                if (!$clientSubService) {
                    return response()->json(['status' => 404, 'message' => 'Client sub-service not found'], 404);
                }

                $clientSubService->deadline = $subServiceData['deadline'];
                $clientSubService->note = $subServiceData['note'];
                $clientSubService->staff_id = $subServiceData['staffId'];
                $clientSubService->sequence_id = $serialKey;
                $clientSubService->save();
            }
        }

        return response()->json(['status' => 200, 'message' => 'Data updated successfully']);
    }

    // public function deleteSubservice($id)
    // {
    //     $subService = ClientSubService::find($id);
    //     $subService->delete();
    //     return response()->json(['message' => 'Sub-service deleted successfully'], 200);
    // }

     public function getClientSubServices($clientserviceId)
    {
        $clientSubServices = ClientSubService::with('subService')->where('client_service_id', $clientserviceId)->get();
        return response()->json($clientSubServices);
    }

    public function updateSubservices(Request $request)
    {
        $subServicesData = $request->input('subServicesData');
        $clientServiceId = $request->input('clientServiceId');

        foreach ($subServicesData as $subServiceData) {
            $clientSubService = ClientSubService::where('sub_service_id', $subServiceData['subServiceId'])
                                                  ->first();

            if ($clientSubService) {
                $clientSubService->update([
                    'deadline' => $subServiceData['deadline'],
                    'note' => $subServiceData['note'],
                    'staff_id' => $subServiceData['staffId']
                ]);
            } else {
                return response()->json(['status' => 404, 'message' => 'Sub-service not found'], 404);
            }
        }

        return response()->json(['status' => 200, 'message' => 'Sub-services updated successfully']);
    }

    public function completedTasks(Request $request)
    {
        $staffId = $request->input('staff_id');
        $completedTasks = ClientSubService::where('staff_id', $staffId)
            ->where('sequence_status', 2)
            ->whereNotNull('deadline')
            ->with('subService','createdBy')
            ->get();

        return response()->json(['completedTasks' => $completedTasks]);
    }

    public function tasksInProgress(Request $request)
    {
        $staffId = $request->input('staff_id');
        $inProgressTasks = ClientSubService::where('staff_id', $staffId)
            ->where('sequence_status', 0)
            ->whereNotNull('deadline')
            ->with('subService','createdBy')
            ->get();

        return response()->json(['inProgressTasks' => $inProgressTasks]);
    }

    public function dueTasks(Request $request)
    {
        $staffId = $request->input('staff_id');
        $dueTasks = ClientSubService::where('staff_id', $staffId)
            ->where('sequence_status', 1)
            ->whereNotNull('deadline')
            ->with('subService','createdBy')
            ->get();

        return response()->json(['dueTasks' => $dueTasks]);
    }

    public function getAssignedService(Request $request)
    {
        if ($request->ajax()) {
            // $data = ClientService::with('clientSubServices')
            //     ->where('due_date', '>=', now()->startOfDay())
            //     ->where('due_date', '<=', now()->addDays(30)->endOfDay())
            //     // ->whereHas('clientSubServices', function ($query) {
            //     //     $query->whereNotNull('staff_id');
            //     // })
            //     ->orderBy('id', 'desc')
            //     ->get();

            $data = ClientService::with('clientSubServices')
                ->where('status', 1)
                ->where('due_date', '<=', now()->endOfDay())
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)
            
                ->addColumn('clientname', function(ClientService $clientservice) {
                    return $clientservice->client ? $clientservice->client->name : '';
                })
                ->addColumn('servicename', function(ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
                })
                ->addColumn('legal_deadline', function(ClientService $clientservice) {
                    $legalDeadline = $clientservice->legal_deadline;
                    if ($legalDeadline) {
                        return [
                            'formatted' => \Carbon\Carbon::parse($legalDeadline)->format('d.m.y'),
                            'original' => $legalDeadline
                        ];
                    }

                    return [
                        'formatted' => 'N/A',
                        'original' => null
                    ];
                })
                ->addColumn('service_deadline', function(ClientService $clientservice) {
                    $serviceDeadline = $clientservice->service_deadline;
                
                    if ($serviceDeadline) {
                        return [
                            'formatted' => \Carbon\Carbon::parse($serviceDeadline)->format('d.m.y'),
                            'original' => $serviceDeadline
                        ];
                    }
                    return [
                        'formatted' => 'N/A',
                        'original' => null
                    ];
                })
                ->addColumn('action', function(ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager->first_name;
                    return '<button class="btn btn-secondary task-detail" data-id="'. $clientservice->id. '" data-manager-firstname="'. $managerFirstName. '">Details</button>';
                })
                ->make(true);
        }

    }

    public function getTodaysDeadlineService(Request $request)
    {
        if ($request->ajax()) {
            $data = ClientService::with('clientSubServices')
                ->whereDate('service_deadline', '=', now())
                ->whereHas('clientSubServices', function ($query) {
                    $query->whereNotNull('staff_id');
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
                    return '<button class="btn btn-secondary task" data-id="'. $clientservice->id. '" data-manager-firstname="'. $managerFirstName. '">Details</button>';
                })
                ->make(true);
        }

    }

    public function updateSubServiceStatus(Request $request)
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

}
