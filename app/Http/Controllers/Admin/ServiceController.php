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
use App\Models\ServiceMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class ServiceController extends Controller
{

    public function showActivity($id)
    {
        $service = Service::find($id);

        if (!$service) {
            return redirect()->back()->with('error', 'Service not found.');
        }

        $serviceActivities = Activity::where('subject_type', Service::class)
            ->where('subject_id', $service->id)
            ->latest()
            ->get();

        $subServiceActivities = Activity::whereIn('subject_type', [SubService::class])
            ->whereIn('subject_id', $service->subServices->pluck('id'))
            ->latest()
            ->get();

        return view('admin.service.activities', compact('service', 'serviceActivities', 'subServiceActivities'));
    }

    public function index()
    {
        $data = Service::with('subServices')->where('status', 1)->orderBy('id', 'DESC')->get();
        $allsubServices = SubService::orderby('id', 'DESC')->get();
        return view('admin.service.index', compact('data', 'allsubServices'));
    }

    public function store(Request $request)
    {
        if (empty($request->name)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Name \" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
            exit();
        }

        $chkname = Service::where('name', $request->name)->first();
        if ($chkname) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This name already added.</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
            exit();
        }
        $data = new Service;
        $data->name = $request->name;
        $data->created_by = Auth::id();
        if ($data->save()) {
            $serviceId = $data->id;
            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Created Successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message, 'service_id' => $serviceId]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
        }
    }

    public function edit($id)
    {
        $where = [
            'id' => $id
        ];
        $info = Service::with('subServices')->find($id);
        return response()->json($info);
    }

    public function update(Request $request)
    {
        if (empty($request->name)) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Please fill \"Username \" field..!</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
            exit();
        }

        $duplicatename = Service::where('name', $request->name)->where('id', '!=', $request->codeid)->first();
        if ($duplicatename) {
            $message = "<div class='alert alert-warning'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>This name already added.</b></div>";
            return response()->json(['status' => 303, 'message' => $message]);
            exit();
        }

        $data = Service::find($request->codeid);
        $data->name = $request->name;
        $data->updated_by = Auth::id();
        if ($data->save()) {

            $newlyCreatedSubServiceIds = [];
            if (isset($request->current_sub_service_names) && is_array($request->current_sub_service_names)) {
                foreach ($request->current_sub_service_names as $index => $name) {
                    if (isset($request->current_sub_service_ids[$index])) {
                        $id = $request->current_sub_service_ids[$index];

                        $subService = SubService::find($id);
                        if ($subService) {
                            $subService->name = $name;
                            $subService->save();
                        }
                    }
                }
            }

            $existingSubServiceNames = SubService::where('service_id', $request->codeid)->pluck('name')->toArray();

            $currentSubServiceNames = isset($request->current_sub_service_names) && is_array($request->current_sub_service_names)
                ? $request->current_sub_service_names
                : [];

            $newSubServiceNames = array_diff($currentSubServiceNames, $existingSubServiceNames);

            $newlyCreatedSubServiceIds = [];

            foreach ($newSubServiceNames as $name) {
                $existingSubService = SubService::where('service_id', $request->codeid)->where('name', $name)->first();
                if (!$existingSubService) {
                    $newSubService = new SubService();
                    $newSubService->service_id = $request->codeid;
                    $newSubService->name = $name;
                    $newSubService->save();
                    $newlyCreatedSubServiceIds[] = $newSubService->id;
                }
            }

            $existingSubServiceIds = SubService::where('service_id', $request->codeid)->pluck('id')->toArray();

            $updatedSubServiceIds = isset($request->current_sub_service_ids) && is_array($request->current_sub_service_ids)
                ? $request->current_sub_service_ids
                : [];

            $newlyCreatedSubServiceIds = isset($newlyCreatedSubServiceIds) && is_array($newlyCreatedSubServiceIds)
                ? $newlyCreatedSubServiceIds
                : [];

            $subServicesToDelete = array_diff($existingSubServiceIds, $updatedSubServiceIds, $newlyCreatedSubServiceIds);

            SubService::whereIn('id', $subServicesToDelete)->delete();

            $message = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><b>Data Updated Successfully.</b></div>";
            return response()->json(['status' => 300, 'message' => $message]);
        } else {
            return response()->json(['status' => 303, 'message' => 'Server Error!!']);
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
            $data = ServiceStaff::with(['client', 'staff'])->where('type', 1)->where('status', 1)->orWhere('status', 3)->orderBy('id', 'desc')->get();

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
                ->addColumn('client_name', function ($row) {
                    return $row->client ? $row->client->name : '';
                })
                ->addColumn('tasks', function ($row) {
                    return $row->assigned_services;
                })
                ->addColumn('staff_name', function ($row) {
                    return $row->staff ? $row->staff->first_name : '';
                })
                ->addColumn('deadline', function ($row) {
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
                ->where('type', 1)
                ->where(function ($query) {
                $query->whereNull('manager_id')
                        ->orWhereHas('clientSubServices', function ($subQuery) {
                            $subQuery->whereNull('staff_id');
                        });
                })
                ->distinct()
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)
                ->addColumn('clientname', function (ClientService $clientservice) {
                    if ($clientservice->director_info_id) {
                        return $clientservice->directorInfo->name;
                    }
                    return $clientservice->client->name;
                })
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
        $today = Carbon::today()->format('d-m-Y');
        if ($request->ajax()) {
            $data = ClientService::where('type', 1)->with('clientSubServices')
                // ->where('status', 2)
                ->where('is_admin_approved', 1)
                ->where('type', 1)
                ->where('due_date', '<=', $today)
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)

                // ->addColumn('clientname', function (ClientService $clientservice) {
                //     return $clientservice->client->name;
                // })

                ->addColumn('clientname', function (ClientService $clientservice) {
                    if ($clientservice->director_info_id) {
                        return $clientservice->directorInfo->name;
                    }
                    return $clientservice->client->name;
                })

                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service->name;
                })
                ->addColumn('action', function (ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager ? $clientservice->manager->first_name . ' ' . $clientservice->manager->last_name : 'N/A';
                    return '<button class="btn btn-secondary task-details" data-id="' . $clientservice->id . '" data-manager-firstname="' . $managerFirstName . '">Details</button>';
                })
                ->make(true);
        }
    }

    public function getClientSubService($clientserviceId)
    {
        $authUserId = auth()->id();
        
        $clientSubServices = ClientSubService::with('subService', 'serviceMessage', 'workTimes', 'staff')
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

    public function getSubServices($serviceId)
    {
        $service = Service::find($serviceId);
        $subServices = SubService::where('service_id', $serviceId)->select('id', 'name')->get();
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
                'serviceId' => 'required|integer',
                'managerId' => 'required|integer',
                'service_frequency' => 'required',
                'due_date' => 'required',
                'service_deadline' => 'required',
                'legal_deadline' => 'required',
                'subServices' => 'required|array',
            ]);

            if (isset($serviceData['service_data_Id']) && $serviceData['service_data_Id'] == 1) {
                $validator->addRules([
                    'director_info_id' => 'required|integer',
                ]);
            }            

            if ($validator->fails()) {
                return response()->json(['status' => 422, 'errors' => $validator->errors()->toArray()], 422);
            }

            $frequency = $serviceData['service_frequency'];
            $nextServiceDeadline = '';
            $nextDueDate = '';
            $nextLegalDeadline = '';

            if ($frequency === 'Monthly') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addMonth()->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addMonth()->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addMonth()->format('d-m-Y');
            } elseif ($frequency === 'Quarterly') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addMonths(3)->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addMonths(3)->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addMonths(3)->format('d-m-Y');
            } elseif ($frequency === 'Annually') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addYear()->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addYear()->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addYear()->format('d-m-Y');
            } elseif ($frequency === 'Weekly') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addWeek()->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addWeek()->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addWeek()->format('d-m-Y');
            } elseif ($frequency === '2 Weekly') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addWeeks(2)->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addWeeks(2)->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addWeeks(2)->format('d-m-Y');
            } elseif ($frequency === '4 Weekly') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addWeeks(4)->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addWeeks(4)->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addWeeks(4)->format('d-m-Y');
            }
                     
            
            $clientService = new ClientService();
            $uniqueId = date("His") . '-' . $request->clientId;
            $clientService->client_id = $request->clientId;
            $clientService->service_id = $serviceData['serviceId'];
            $clientService->manager_id = $serviceData['managerId'];
            $clientService->director_info_id = $serviceData['director_info_id'] ?? null;
            $clientService->service_frequency = $serviceData['service_frequency'];
            $clientService->service_deadline = $serviceData['service_deadline'];
            $clientService->due_date = $serviceData['due_date'];
            $clientService->legal_deadline = $serviceData['legal_deadline'];
            $clientService->unique_id = $uniqueId;
            $clientService->next_service_deadline = $nextServiceDeadline;
            $clientService->next_due_date = $nextDueDate;
            $clientService->next_legal_deadline = $nextLegalDeadline;
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

        foreach ($request->services as $serviceData) {
            $validator = Validator::make($serviceData, [
                'serviceId' => 'required|integer',
                'managerId' => 'required|integer',
                'service_frequency' => 'required',
                'due_date' => 'required',
                'service_deadline' => 'required',
                'legal_deadline' => 'required',
                'subServices' => 'required|array',
            ]);

            if (isset($serviceData['service_data_Id']) && $serviceData['service_data_Id'] == 1) {
                $validator->addRules([
                    'director_info_id' => 'required|integer',
                ]);
            }

            if ($validator->fails()) {
                return response()->json(['status' => 422, 'errors' => $validator->errors()->toArray()], 422);
            }
            
            $nextServiceDeadline = '';
            $nextDueDate = '';
            $nextLegalDeadline = '';

            $frequency = $serviceData['service_frequency'];

            if ($frequency === 'Monthly') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addMonth()->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addMonth()->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addMonth()->format('d-m-Y');
            } elseif ($frequency === 'Quarterly') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addMonths(3)->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addMonths(3)->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addMonths(3)->format('d-m-Y');
            } elseif ($frequency === 'Annually') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addYear()->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addYear()->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addYear()->format('d-m-Y');
            } elseif ($frequency === 'Weekly') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addWeek()->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addWeek()->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addWeek()->format('d-m-Y');
            } elseif ($frequency === '2 Weekly') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addWeeks(2)->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addWeeks(2)->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addWeeks(2)->format('d-m-Y');
            } elseif ($frequency === '4 Weekly') {
                $nextServiceDeadline = Carbon::parse($serviceData['service_deadline'])->addWeeks(4)->format('d-m-Y');
                $nextDueDate = Carbon::parse($serviceData['due_date'])->addWeeks(4)->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($serviceData['legal_deadline'])->addWeeks(4)->format('d-m-Y');
            }

            if (!isset($serviceData['client_service_id'])) {
                $clientService = new ClientService();
                $clientService->client_id = $request->clientId;
                $clientService->service_id = $serviceData['serviceId'];
                $clientService->manager_id = $serviceData['managerId'];
                $clientService->service_frequency = $serviceData['service_frequency'];
                $clientService->service_deadline = $serviceData['service_deadline'];
                $clientService->due_date = $serviceData['due_date'];
                $clientService->legal_deadline = $serviceData['legal_deadline'];
                $clientService->next_service_deadline = $nextServiceDeadline;
                $clientService->next_due_date = $nextDueDate;
                $clientService->next_legal_deadline = $nextLegalDeadline;
                $clientService->director_info_id = $serviceData['director_info_id'] ?? null;
                $clientService->save();

                $serviceData['client_service_id'] = $clientService->id;
            } else {
                $existingService = ClientService::find($serviceData['client_service_id']);
                if ($existingService) {
                    $existingService->update([
                        'manager_id' => $serviceData['managerId'],
                        'director_info_id' => $serviceData['director_info_id'] ?? null,
                        'service_frequency' => $serviceData['service_frequency'],
                        'service_deadline' => $serviceData['service_deadline'],
                        'due_date' => $serviceData['due_date'],
                        'legal_deadline' => $serviceData['legal_deadline'],
                        'next_service_deadline' => $nextServiceDeadline,
                        'next_due_date' => $nextDueDate,
                        'next_legal_deadline' => $nextLegalDeadline
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
                        $clientSubService->created_by = auth()->id();
                        if ($key === 0) {
                            $clientSubService->sequence_status = 0;
                        }
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
            'subServices.*.note' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $clientService = ClientService::where('client_id', $request->clientId)
            ->where('service_id', $request->serviceId)
            ->first();

        if ($clientService) {

            $frequency = $request->service_frequency;
            $nextServiceDeadline = '';
            $nextDueDate = '';
            $nextLegalDeadline = '';

            if ($frequency === 'Monthly') {
                $nextServiceDeadline = Carbon::parse($request->service_deadline)->addMonth()->format('d-m-Y');
                $nextDueDate = Carbon::parse($request->dueDate)->addMonth()->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($request->legalDeadline)->addMonth()->format('d-m-Y');
            } elseif ($frequency === 'Quarterly') {
                $nextServiceDeadline = Carbon::parse($request->service_deadline)->addMonths(3)->format('d-m-Y');
                $nextDueDate = Carbon::parse($request->dueDate)->addMonths(3)->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($request->legalDeadline)->addMonths(3)->format('d-m-Y');
            } elseif ($frequency === 'Annually') {
                $nextServiceDeadline = Carbon::parse($request->service_deadline)->addYear()->format('d-m-Y');
                $nextDueDate = Carbon::parse($request->dueDate)->addYear()->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($request->legalDeadline)->addYear()->format('d-m-Y');
            } elseif ($frequency === 'Weekly') {
                $nextServiceDeadline = Carbon::parse($request->service_deadline)->addWeek()->format('d-m-Y');
                $nextDueDate = Carbon::parse($request->dueDate)->addWeek()->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($request->legalDeadline)->addWeek()->format('d-m-Y');
            } elseif ($frequency === '2 Weekly') {
                $nextServiceDeadline = Carbon::parse($request->service_deadline)->addWeeks(2)->format('d-m-Y');
                $nextDueDate = Carbon::parse($request->dueDate)->addWeeks(2)->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($request->legalDeadline)->addWeeks(2)->format('d-m-Y');
            } elseif ($frequency === '4 Weekly') {
                $nextServiceDeadline = Carbon::parse($request->service_deadline)->addWeeks(4)->format('d-m-Y');
                $nextDueDate = Carbon::parse($request->dueDate)->addWeeks(4)->format('d-m-Y');
                $nextLegalDeadline = Carbon::parse($request->legalDeadline)->addWeeks(4)->format('d-m-Y');
            }

            $clientService->manager_id = $request->managerId;
            $clientService->service_frequency = $request->service_frequency;
            $clientService->service_deadline = $request->service_deadline;
            $clientService->due_date = $request->dueDate;
            $clientService->legal_deadline = $request->legalDeadline;
            $clientService->next_service_deadline = $nextServiceDeadline;
            $clientService->next_due_date = $nextDueDate;
            $clientService->next_legal_deadline = $nextLegalDeadline;
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
            ->with('subService', 'createdBy')
            ->get();

        return response()->json(['completedTasks' => $completedTasks]);
    }

    public function tasksInProgress(Request $request)
    {
        $staffId = $request->input('staff_id');
        $inProgressTasks = ClientSubService::where('staff_id', $staffId)
            ->where('sequence_status', 0)
            ->whereNotNull('deadline')
            ->with('subService', 'createdBy')
            ->get();

        return response()->json(['inProgressTasks' => $inProgressTasks]);
    }

    public function dueTasks(Request $request)
    {
        $staffId = $request->input('staff_id');
        $dueTasks = ClientSubService::where('staff_id', $staffId)
            ->where('sequence_status', 1)
            ->whereNotNull('deadline')
            ->with('subService', 'createdBy')
            ->get();

        return response()->json(['dueTasks' => $dueTasks]);
    }

    public function getAssignedService(Request $request)
    {
        if ($request->ajax()) {

            $today = Carbon::today()->format('d-m-Y');

            $data = ClientService::with('clientSubServices')
                // ->whereIn('status', [0, 1, 2])
                ->whereNotNull('service_deadline')
                ->whereNotNull('due_date')
                ->whereNotNull('legal_deadline')
                ->whereNotNull('service_frequency')
                ->where('is_admin_approved', 0)
                ->where('type', 1)
                ->where('due_date', '<=', $today)
                ->whereHas('clientSubServices', function ($query) {
                    $query->whereNotNull('staff_id');
                })
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)

                // ->addColumn('clientname', function (ClientService $clientservice) {
                //     return $clientservice->client ? $clientservice->client->name : '';
                // })
                ->addColumn('clientname', function (ClientService $clientservice) {
                    if ($clientservice->director_info_id) {
                        return $clientservice->directorInfo->name;
                    }
                    return $clientservice->client->name;
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
                })
                ->addColumn('legal_deadline', function (ClientService $clientservice) {
                    $legalDeadline = $clientservice->legal_deadline;
                    if ($legalDeadline) {
                        return [
                            'formatted' => \Carbon\Carbon::parse($legalDeadline)->format('d-m-Y'),
                            'original' => $legalDeadline
                        ];
                    }

                    return [
                        'formatted' => 'N/A',
                        'original' => null
                    ];
                })

                ->addColumn('due_date', function (ClientService $clientservice) {
                    $dueDate = $clientservice->due_date;
                    if ($dueDate) {
                        return \Carbon\Carbon::parse($dueDate)->format('d-m-Y');
                    }
                    return 'N/A';
                })

                ->addColumn('service_deadline', function (ClientService $clientservice) {
                    $serviceDeadline = $clientservice->service_deadline;

                    if ($serviceDeadline) {
                        return [
                            'formatted' => \Carbon\Carbon::parse($serviceDeadline)->format('d-m-Y'),
                            'original' => $serviceDeadline
                        ];
                    }
                    return [
                        'formatted' => 'N/A',
                        'original' => null
                    ];
                })
                ->addColumn('action', function (ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager ? $clientservice->manager->first_name . ' ' . $clientservice->manager->last_name : 'N/A';
                    return '<button class="btn btn-secondary task-detail" data-id="' . $clientservice->id . '" data-manager-firstname="' . $managerFirstName . '">Details</button>';
                })
                ->make(true);
        }
    }

    public function changeServiceStatus(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required|integer',
            'status' => 'required|boolean',
        ]);
    
        $service = ClientService::find($validatedData['id']);
        if ($service) {
            $service->is_admin_approved = $validatedData['status'];
            $service->save();
    
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }
    
        return response()->json(['success' => false, 'message' => 'Failed to update status.']);
    }

    public function getOneTimeAssignedService(Request $request)
    {
        if ($request->ajax()) {

            $data = ClientService::with(['clientSubServices', 'manager'])
                ->whereIn('status', [0, 1])
                ->where('type', 2)
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)

                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
                })
                ->editColumn('legal_deadline', function ($clientService) {
                    return Carbon::parse($clientService->legal_deadline)->format('d-m-Y');
                })

                ->addColumn('action', function (ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager ? $clientservice->manager->first_name . ' ' . $clientservice->manager->last_name : 'N/A';
                    return '<button class="btn btn-secondary assigned-task-detail" data-id="' . $clientservice->id . '" data-manager-firstname="' . $managerFirstName . '">Details</button>';
                })
                ->make(true);
        }
    }

    public function getOneTimeCompletedService(Request $request)
    {
        if ($request->ajax()) {

            $data = ClientService::with(['clientSubServices', 'manager'])
                ->where('status', 2)
                ->where('type', 2)
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)

                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
                })
                ->editColumn('legal_deadline', function ($clientService) {
                    return Carbon::parse($clientService->legal_deadline)->format('d-m-Y');
                })

                ->addColumn('action', function (ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager ? $clientservice->manager->first_name . ' ' . $clientservice->manager->last_name : 'N/A';
                    return '<button class="btn btn-secondary assigned-task-detail" data-id="' . $clientservice->id . '" data-manager-firstname="' . $managerFirstName . '">Details</button>';
                })
                ->make(true);
        }
    }

    public function getTodaysDeadlineService(Request $request)
    {
        if ($request->ajax()) {
            $today = Carbon::today()->format('d-m-Y');
            $data = ClientService::where('type', 1)
                ->with('clientSubServices')
                ->where('service_deadline', $today)
                ->whereHas('clientSubServices', function ($query) {
                    $query->whereNotNull('staff_id');
                })
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)

                ->addColumn('clientname', function (ClientService $clientservice) {
                    if ($clientservice->director_info_id) {
                        return $clientservice->directorInfo->name;
                    }
                    return $clientservice->client->name;
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service->name;
                })
                ->addColumn('action', function (ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager ? $clientservice->manager->first_name . ' ' . $clientservice->manager->last_name : 'N/A';
                    return '<button class="btn btn-secondary task" data-id="' . $clientservice->id . '" data-manager-firstname="' . $managerFirstName . '">Details</button>';
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

    public function getServiceMessage($clientSubServiceId)
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
        $validated = $request->validate([
            'message' => 'required|string',
            'client_sub_service_id' => 'required|integer',
        ]);

        $serviceMessage = new ServiceMessage();
        $serviceMessage->message = $validated['message'];
        $serviceMessage->client_sub_service_id = $validated['client_sub_service_id'];
        $serviceMessage->created_by = auth()->id();
        $serviceMessage->viewed_by = json_encode([strval(auth()->id())]);
        $serviceMessage->save();

        return response()->json(['success' => true, 'message' => 'Message saved successfully']);
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

    public function toggleContinuous(Request $request)
    {
        $clientService = ClientService::find($request->id);

        if ($clientService) {
            $clientService->continuous = !$clientService->continuous;
            $clientService->save();

            return response()->json(['success' => true, 'new_status' => $clientService->continuous]);
        }

        return response()->json(['success' => false, 'message' => 'Service not found']);
    }

}
