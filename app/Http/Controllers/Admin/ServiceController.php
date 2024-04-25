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

    public function delete($id)
    {
        $service = Service::find($id);

        if ($service) {
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
            $data = ClientService::with('client')->where('status', 1)->whereDate('deadline', '<=', now()->addDays(30))->orderBy('id', 'desc')->get();

            $data->transform(function ($item, $key) {
                $servicesIds = $item->assigned_services ?: [];
                $serviceNames = [];

                foreach ($servicesIds as $serviceId) {
                    $service = Service::find($serviceId);
                    if ($service) {
                        $serviceNames[] = $service->name;
                    }
                }

                $item->service_names = implode(', ', $serviceNames);

                return $item;
            });

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('client_name', function($row) {
                    return $row->client ? $row->client->name : '';
                })
                ->addColumn('assigned_services', function($row) {
                    return $row->service_names;
                })
                ->addColumn('deadline', function($row) {
                    return $row->deadline;
                })
                ->rawColumns(['client_name', 'assigned_services', 'deadline'])
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
            $data = ServiceStaff::with(['client', 'staff'])->where('status', 2)->orderBy('id', 'desc')->get();

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
            'serviceId' => 'required|integer',
            'managerId' => 'required|integer',
            'subServices' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $clientService = ClientService::where('client_id', $request->clientId)
                                        ->where('service_id', $request->serviceId)
                                        ->first();

        if (!$clientService) {
            $clientService = new ClientService();
            $clientService->client_id = $request->clientId;
            $clientService->service_id = $request->serviceId;
            $clientService->manager_id = $request->managerId;
            $clientService->save();
        }

        if ($request->has('subServices')) {
            foreach ($request->subServices as $subServiceData) {
                $clientSubService = ClientSubService::where('client_service_id', $clientService->id)
                                                        ->where('sub_service_id', $subServiceData['subServiceId'])
                                                        ->first();

                if (!$clientSubService) {
                    $clientSubService = new ClientSubService();
                    $clientSubService->client_service_id = $clientService->id;
                    $clientSubService->client_id = $request->clientId; 
                    $clientSubService->manager_id = $request->managerId;
                    $clientSubService->sub_service_id = $subServiceData['subServiceId'];
                }

                $clientSubService->frequency = $subServiceData['frequency'];
                $clientSubService->deadline = $subServiceData['deadline'];
                $clientSubService->note = $subServiceData['note'];
                $clientSubService->staff_id = $subServiceData['staffId'];
                $clientSubService->save();
            }
        }

        return response()->json(['status' => 200, 'message' => 'Data saved successfully']);
    }

    public function deleteSubservice($id)
    {
        $subService = ClientSubService::find($id);
        $subService->delete();
        return response()->json(['message' => 'Sub-service deleted successfully'], 200);
    }

}
