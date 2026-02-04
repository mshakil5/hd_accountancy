<?php

namespace App\Http\Controllers\Manager;

use DataTables;
use App\Models\User;
use App\Models\Client;
use App\Models\WorkTime;
use App\Models\SubService;
use Illuminate\Http\Request;
use App\Models\ClientService;
use App\Models\ServiceMessage;
use Illuminate\Support\Carbon;
use App\Models\ClientSubService;
use App\Models\UserAttendanceLog;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function getAllAssignedServices(Request $request)
    {
        $currentUserId = Auth::id();
        $today = Carbon::today()->format('d-m-Y');

        if ($request->ajax()) {
            $data = ClientService::where('type', '!=', 2)
                ->whereRaw("STR_TO_DATE(due_date, '%d-%m-%Y') <= STR_TO_DATE(?, '%d-%m-%Y')", [$today])
                ->with(['clientSubServices' => function ($query) {
                    $query->where('staff_id', Auth::id());
                }, 'client', 'directorInfo', 'service', 'manager'])
                ->where(function ($query) use ($currentUserId) {
                    $query->where('manager_id', $currentUserId)
                        ->orWhereHas('clientSubServices', function ($subQuery) use ($currentUserId) {
                            $subQuery->where('staff_id', $currentUserId)
                                ->whereIn('sequence_status', [0, 1]);
                        });
                })
                ->whereIn('status', [0, 1]);

            return DataTables::of($data)
                ->addColumn('id', function (ClientService $clientservice) {
                    return $clientservice->id;
                })
                ->addColumn('clientname', function (ClientService $clientservice) {
                    return $clientservice->director_info_id ? $clientservice->directorInfo->name ?? '' : $clientservice->client->name ?? '';
                })
                ->filterColumn('clientname', function($query, $keyword) {
                    $query->whereHas('client', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    })->orWhereHas('directorInfo', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
                })
                ->filterColumn('servicename', function($query, $keyword) {
                    $query->whereHas('service', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('due_date', function (ClientService $clientservice) {
                    return $clientservice->due_date ? Carbon::parse($clientservice->due_date)->format('d-m-Y') : 'N/A';
                })
                ->addColumn('legal_deadline', function (ClientService $clientservice) {
                    $legalDeadline = $clientservice->legal_deadline;
                    if ($legalDeadline) {
                        return [
                            'formatted' => Carbon::parse($legalDeadline)->format('d-m-Y'),
                            'original' => $legalDeadline
                        ];
                    }
                    return [
                        'formatted' => 'N/A',
                        'original' => null
                    ];
                })
                ->addColumn('status', function (ClientService $clientservice) {
                    return $clientservice->status;
                })
                ->addColumn('action', function (ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager 
                        ? $clientservice->manager->first_name . ' ' . $clientservice->manager->last_name 
                        : 'N/A';
                    return '<button class="btn btn-secondary change-status" data-id="' 
                        . $clientservice->id . '" data-manager-firstname="' . $managerFirstName . '">Details</button>';
                })
                ->filterColumn('id', function($query, $keyword) {
                    $query->where('id', 'like', "%{$keyword}%");
                })
                ->order(function ($query) {
                    $query->orderByRaw("STR_TO_DATE(due_date, '%d-%m-%Y') ASC");
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }    

    public function getTeamOverview(Request $request)
    {
        $authUserId = Auth::id();
        $authUser = User::find($authUserId);
        
        if ($request->ajax()) {
            $staffs = [];
            
            if ($authUser->type == 'admin') {
                $staffs = User::where('type', '!=', 'admin')->get();
            } else if ($authUser->type == 'manager') {
                $staffs = $authUser->staffs;
            }

            return DataTables::of($staffs)
                ->addColumn('name', function (User $user) {
                    return $user->first_name . ' ' . $user->last_name;
                })
                ->addColumn('role', function (User $user) {
                    $role = $user->type == 'manager' ? 'Manager' : 'Staff';
                    $badge = $user->type == 'manager' ? 'primary' : 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . $role . '</span>';
                })
                ->addColumn('assigned_tasks', function (User $user) {
                    $count = ClientSubService::where('staff_id', $user->id)
                        ->whereHas('clientService', function ($query) {
                            $query->whereIn('status', [0, 1]);
                        })
                        ->where('sequence_status', '!=', 2)
                        ->count();
                    return $count;
                })
                ->addColumn('in_progress', function (User $user) {
                    $count = ClientSubService::where('staff_id', $user->id)
                        ->where('sequence_status', 0)
                        ->whereHas('clientService', function ($query) {
                            $query->whereIn('status', [0, 1]);
                        })
                        ->count();
                    return '<span class="badge bg-warning text-dark">' . $count . '</span>';
                })
                ->addColumn('completed', function (User $user) {
                    $count = ClientSubService::where('staff_id', $user->id)
                        ->where('sequence_status', 2)
                        ->whereHas('clientService', function ($query) {
                            $query->whereIn('status', [0, 1]);
                        })
                        ->count();
                    return '<span class="badge bg-success">' . $count . '</span>';
                })
                ->addColumn('not_started', function (User $user) {
                    $count = ClientSubService::where('staff_id', $user->id)
                        ->where('sequence_status', 1)
                        ->whereHas('clientService', function ($query) {
                            $query->whereIn('status', [0, 1]);
                        })
                        ->count();
                    return '<span class="badge bg-secondary">' . $count . '</span>';
                })
                ->addColumn('status', function (User $user) {
                    $hasActiveWorkTime = WorkTime::where('staff_id', $user->id)
                        ->where('is_break', 0)
                        ->whereNull('end_time')
                        ->exists();

                    $lastActivity = WorkTime::where('staff_id', $user->id)
                        ->latest('created_at')
                        ->first();

                    if ($hasActiveWorkTime) {
                        return '<span class="badge bg-success">Active</span>';
                    } elseif ($lastActivity && $lastActivity->created_at->isToday()) {
                        return '<span class="badge bg-info">Online</span>';
                    } else {
                        return '<span class="badge bg-warning">Idle</span>';
                    }
                })
                ->addColumn('action', function (User $user) {
                    return '<button class="btn btn-sm btn-info view-staff-details" data-staff-id="' . $user->id . '">
                        <i class="fas fa-eye"></i> View
                    </button>';
                })
                ->rawColumns(['role', 'in_progress', 'completed', 'not_started', 'status', 'action'])
                ->make(true);
        }
    }

    public function getStaffDetails($staffId)
    {
        $staff = User::with(['clientSubServices' => function ($query) {
            $query->with('clientService', 'subService', 'workTimes')
                ->whereHas('clientService', function ($q) {
                    $q->whereIn('status', [0, 1]);
                });
        }])->find($staffId);

        if (!$staff) {
            return response()->json(['error' => 'Staff not found'], 404);
        }

        $clientSubServices = $staff->clientSubServices->where('sequence_status', '!=', 2);

        $totalTasks = $clientSubServices->count();
        $inProgress = $clientSubServices->where('sequence_status', 0)->count();
        $notStarted = $clientSubServices->where('sequence_status', 1)->count();
        $completed = $staff->clientSubServices->where('sequence_status', 2)->count();

        $currentTasks = $clientSubServices->map(function ($task) {
            $totalDuration = $task->workTimes
                ->where('is_break', 0)
                ->sum('duration');

            $hours = floor($totalDuration / 3600);
            $minutes = floor(($totalDuration % 3600) / 60);
            $seconds = $totalDuration % 60;

            return [
                'client' => $task->clientService && $task->clientService->client ? $task->clientService->client->name : 'N/A',
                'service' => $task->clientService && $task->clientService->service ? $task->clientService->service->name : 'N/A',
                'sub_service' => $task->subService ? $task->subService->name : 'N/A',
                'deadline' => $task->deadline,
                'status' => $task->sequence_status,
                'duration' => "{$hours}h {$minutes}m {$seconds}s"
            ];
        });

        $hasActiveWorkTime = WorkTime::where('staff_id', $staffId)
            ->where('is_break', 0)
            ->whereNull('end_time')
            ->exists();

        $lastActivity = WorkTime::where('staff_id', $staffId)
            ->latest('created_at')
            ->first();

        if ($hasActiveWorkTime) {
            $onlineStatus = 'Active';
        } elseif ($lastActivity && $lastActivity->created_at->isToday()) {
            $onlineStatus = 'Online';
        } else {
            $onlineStatus = 'Idle';
        }

        return response()->json([
            'staff' => [
                'name' => $staff->first_name . ' ' . $staff->last_name,
                'email' => $staff->email,
                'role' => $staff->type == 'manager' ? 'Manager' : 'Staff',
                'status' => $onlineStatus,
                'total_tasks' => $totalTasks,
                'in_progress' => $inProgress,
                'not_started' => $notStarted,
                'completed' => $completed
            ],
            'current_tasks' => $currentTasks
        ]);
    }
    
    public function getAllServices(Request $request)
    {
        if ($request->ajax()) {
            $data = ClientService::with(['clientSubServices', 'client', 'directorInfo', 'service', 'manager'])
                ->whereHas('clientSubServices', function ($query) {
                    $query->where('staff_id', Auth::id());
                });

            return DataTables::of($data)
                ->addColumn('id', function (ClientService $clientservice) {
                    return $clientservice->id;
                })
                ->addColumn('clientname', function (ClientService $clientservice) {
                    return $clientservice->director_info_id ? $clientservice->directorInfo?->name : $clientservice->client?->name;
                })
                ->filterColumn('clientname', function($query, $keyword) {
                    $query->whereHas('client', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    })->orWhereHas('directorInfo', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service?->name ?? '';
                })
                ->filterColumn('servicename', function($query, $keyword) {
                    $query->whereHas('service', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('due_date', function (ClientService $clientservice) {
                    return $clientservice->due_date ?? '';
                })
                ->addColumn('legal_deadline', function (ClientService $clientservice) {
                    return $clientservice->legal_deadline ?? '';
                })
                ->addColumn('service_deadline', function (ClientService $clientservice) {
                    return $clientservice->service_deadline ?? '';
                })
                ->addColumn('action', function (ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager ? $clientservice->manager->first_name . ' ' . $clientservice->manager->last_name : 'N/A';
                    return '<button class="btn btn-secondary change-status" data-id="' . $clientservice->id . '" data-manager-firstname="' . $managerFirstName . '">Details</button>';
                })
                ->filterColumn('id', function($query, $keyword) {
                    $query->where('id', 'like', "%{$keyword}%");
                })
                ->order(function ($query) {
                    $query->orderByRaw("STR_TO_DATE(due_date, '%d-%m-%Y') DESC");
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function getClientSubServices($clientserviceId)
    {
        $authUserId = auth()->id();
        
        $clientSubServices = ClientSubService::with('subService', 'serviceMessage', 'workTimes', 'staff', 'clientService')
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

    public function getServiceMessages($clientSubServiceId)
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

            $maxSequenceNo = ClientSubService::where('client_service_id', $clientServiceId)->max('sequence_id');
    
            if ($nextService <= $maxSequenceNo) {
                $nextTask = ClientSubService::where('client_service_id', $clientServiceId)
                    ->where('sequence_id', $nextService)
                    ->first();
    
                if ($nextTask && $nextTask->sequence_status != 2) {
                    $nextTask->sequence_status = 0;
                    $nextTask->updated_by = Auth::id();
                    $nextTask->save();
                }
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
        $serviceMessage->viewed_by = json_encode([strval(auth()->id())]);
        $serviceMessage->save();

        return response()->json(['success' => 'Message saved successfully.']);
    }

    public function getCompetedServices(Request $request)
    {
        $managerName = Auth::user()->first_name;

        if ($request->ajax()) {
            $data = ClientService::where('type', '!=', 2)
                ->with(['clientSubServices', 'client', 'directorInfo', 'service'])
                ->whereHas('clientSubServices', function ($query) {
                    $query->where('sequence_status', 2)
                        ->where('staff_id', Auth::id());
                });

            return DataTables::of($data)
                ->addColumn('id', function (ClientService $clientservice) {
                    return $clientservice->id;
                })
                ->addColumn('clientname', function (ClientService $clientservice) {
                    return $clientservice->director_info_id ? $clientservice->directorInfo->name : $clientservice->client->name;
                })
                ->filterColumn('clientname', function($query, $keyword) {
                    $query->whereHas('client', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    })->orWhereHas('directorInfo', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
                })
                ->filterColumn('servicename', function($query, $keyword) {
                    $query->whereHas('service', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('due_date', function (ClientService $clientservice) {
                    return $clientservice->due_date;
                })
                ->addColumn('legal_deadline', function (ClientService $clientservice) {
                    return $clientservice->legal_deadline;
                })
                ->addColumn('service_deadline', function (ClientService $clientservice) {
                    return $clientservice->service_deadline;
                })
                ->addColumn('action', function (ClientService $clientservice) use ($managerName) {
                    return '<button class="btn btn-secondary task-details" data-id="' . $clientservice->id . '" data-manager="' . $managerName . '">Details</button>';
                })
                ->filterColumn('id', function($query, $keyword) {
                    $query->where('id', 'like', "%{$keyword}%");
                })
                ->order(function ($query) {
                    $query->orderByRaw("STR_TO_DATE(due_date, '%d-%m-%Y') DESC");
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    
    public function getCompetedServicesAsManager(Request $request)
    {
        $managerName = Auth::user()->first_name;

        if ($request->ajax()) {
            $data = ClientService::where('type', '!=', 2)
                ->with(['clientSubServices', 'client', 'directorInfo', 'service'])
                ->where('manager_id', Auth::id())
                ->where('status', 2);

            return DataTables::of($data)
                ->addColumn('id', function (ClientService $clientservice) {
                    return $clientservice->id;
                })
                ->addColumn('clientname', function (ClientService $clientservice) {
                    return $clientservice->director_info_id ? $clientservice->directorInfo->name : $clientservice->client->name;
                })
                ->filterColumn('clientname', function($query, $keyword) {
                    $query->whereHas('client', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    })->orWhereHas('directorInfo', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
                })
                ->filterColumn('servicename', function($query, $keyword) {
                    $query->whereHas('service', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('due_date', function (ClientService $clientservice) {
                    return $clientservice->due_date;
                })
                ->addColumn('legal_deadline', function (ClientService $clientservice) {
                    return $clientservice->legal_deadline;
                })
                ->addColumn('service_deadline', function (ClientService $clientservice) {
                    return $clientservice->service_deadline;
                })
                ->addColumn('status', function (ClientService $clientservice) {
                    return $clientservice->status;
                })
                ->addColumn('action', function (ClientService $clientservice) use ($managerName) {
                    return '<button class="btn btn-secondary task-details1" data-id="' . $clientservice->id . '" data-manager="' . $managerName . '">Details</button>';
                })
                ->filterColumn('id', function($query, $keyword) {
                    $query->where('id', 'like', "%{$keyword}%");
                })
                ->order(function ($query) {
                    $query->orderByRaw("STR_TO_DATE(due_date, '%d-%m-%Y') DESC");
                })
                ->rawColumns(['action'])
                ->make(true);
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
            ->where('is_break', 0)
            ->orderBy('id', 'DESC')
            ->first();
        $startDateTime = Carbon::parse($workTime->start_time);
        $endDateTime = Carbon::parse($workTime->end_time);

        $totalDurationExcludingBreaks = $startDateTime->diffInSeconds($endDateTime);

        $breaks = WorkTime::where('client_sub_service_id', $clientSubServiceId)
            ->where('is_break', 1)
            ->sum('duration');

        $adjustedDuration = $totalDurationExcludingBreaks - $breaks;
        $workTime->duration = $adjustedDuration;
        $workTime->end_time = Carbon::now();

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
        $startOfDay = Carbon::today()->startOfDay();
        $endOfDay = Carbon::today()->endOfDay();

        $ongoingWorkTime = WorkTime::where('staff_id', $staffId)
            ->whereNull('end_time')
            ->where('is_break', 0)
            ->whereNotNull('start_time')
            ->whereNotNull('client_sub_service_id')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->exists();
        if ($ongoingWorkTime) {
            return response()->json(['status' => 'ongoing']);
        } else {
            return response()->json(['status' => 'completed']);
        }
    }

    public function takeBreak(Request $request)
    {
        $workTime = new WorkTime();
        // $workTime->manager_id = Auth::id();
        $workTime->staff_id = Auth::id();
        $workTime->start_time = Carbon::now();
        $workTime->start_date = Carbon::today()->format('d-m-Y');
        $workTime->is_break = 1;
        $workTime->created_by = Auth::id();
        $workTime->save();
        return response()->json(['message' => 'Break started successfully', 'workTimeId' => $workTime->id], 200);
    }

    public function checkBreakStatus(Request $request)
    {
        $workTimeId = $request->input('workTimeId');
        $workTime = WorkTime::find($workTimeId);

        if ($workTime && $workTime->is_break == 1) {
            return response()->json(['isBreak' => true]);
        } else {
            return response()->json(['isBreak' => false]);
        }
    }

    public function breakOut(Request $request)
    {
        $workTimeId = $request->input('workTimeId');
        $workTime = WorkTime::find($workTimeId);
        if ($workTime) {
            $startTime = $workTime->start_time;
            $endTime = Carbon::now();
            $duration = $endTime->diffInSeconds($startTime);
            $workTime->end_time = $endTime;
            $workTime->duration = $duration;
            $workTime->save();
            return response()->json(['success' => true, 'message' => 'Break Out successful']);
        } else {
            return response()->json(['success' => false, 'message' => 'WorkTime not found'], 404);
        }
    }

    public function getCompetedServicesModal(Request $request)
    {
        $staffId = Auth::id();
        $today = now()->startOfDay();

        $userId = auth()->id();
        $startOfDay = Carbon::today()->startOfDay();

        $activeTimeInSeconds = UserAttendanceLog::where('user_id', $userId)
            ->whereNotNull('end_time')
            ->whereBetween('created_at', [$startOfDay, now()])
            ->sum('duration');

        $ongoingSessions = UserAttendanceLog::where('user_id', $userId)
            ->whereNull('end_time')
            ->where('created_at', '>=', $startOfDay)
            ->get();

        $currentTime = now();
        foreach ($ongoingSessions as $session) {
            $startTime = Carbon::parse($session->start_time);
            $activeTimeInSeconds += $startTime->diffInSeconds($currentTime);
        }


        $startOfDay = Carbon::today()->startOfDay();
        $endOfDay = Carbon::today()->endOfDay();

        $clientSubServices = ClientSubService::where('staff_id', $staffId)
            ->where('sequence_status', 2)
            ->whereBetween('updated_at', [$startOfDay, $endOfDay])
            ->with(['workTimes', 'client', 'subService'])
            ->get();

        $completedServices = [];
        $totalDuration = 0;

        foreach ($clientSubServices as $clientSubService) {
            $workTime = $clientSubService->workTimes->first();

            if ($workTime) {
                $completedServices[] = [
                    'client_name' => $clientSubService->client->name,
                    'client_id' => $clientSubService->client_id,
                    'sub_service_name' => $clientSubService->subService->name,
                    'sub_service_id' => $clientSubService->sub_service_id,
                    'note' => $clientSubService->note,
                    'start_time' => $workTime->start_time,
                    'end_time' => $workTime->end_time,
                ];
            }
        }

        $totalBreakDuration = WorkTime::where('staff_id', $staffId)
            ->whereDate('start_time', $today)
            ->where('is_break', true)
            ->sum('duration');

        $totalDuration = WorkTime::where('staff_id', $staffId)
            ->whereDate('start_time', $today)
            ->where('is_break', false)
            ->sum('duration');

        return response()->json([
            'login_time' => $activeTimeInSeconds,
            'total_duration' => $totalDuration,
            'total_break_duration' => $totalBreakDuration,
            'completed_services' => $completedServices,
        ]);
    }

    public function saveNotes(Request $request)
    {
        $staffId = auth()->id();

        if ($request->filled(['client_ids', 'sub_service_ids', 'notes', 'start_times', 'end_times'])) {
            for ($i = 0; $i < count($request->client_ids); $i++) {
                $clientSubService = new ClientSubService();
                $clientSubService->client_id = $request->client_ids[$i];
                $clientSubService->sub_service_id = $request->sub_service_ids[$i];
                $clientSubService->note = $request->notes[$i];
                $clientSubService->staff_id = $staffId;
                $clientSubService->type = 2;
                $clientSubService->save();

                $workTime = new WorkTime();
                $workTime->client_sub_service_id = $clientSubService->id;
                $today = Carbon::now()->format('Y-m-d');
                $startTime = $today . ' ' . $request->start_times[$i] . ':00';
                $endTime = $today . ' ' . $request->end_times[$i] . ':00';
                $workTime->start_time = Carbon::parse($startTime);
                $workTime->end_time = Carbon::parse($endTime);
                $workTime->staff_id = $staffId;
                $workTime->type = 2;
                $workTime->duration = $workTime->end_time->diffInSeconds($workTime->start_time);
                $workTime->start_date = Carbon::now()->format('d-m-Y');
                $workTime->save();
            }
        }

        $attendanceLog = UserAttendanceLog::where('user_id', $staffId)
            ->where('status', 0)
            ->latest()
            ->first();

        if ($attendanceLog) {
            $attendanceLog->end_time = now();
            $startTime = Carbon::parse($attendanceLog->start_time);
            $endTime = Carbon::parse($attendanceLog->end_time);
            $attendanceLog->duration = $endTime->diffInSeconds($startTime);
            $attendanceLog->session_id = null;
            $attendanceLog->status = 1;
            $noteInput = $request->input('noteInput');
            $attendanceLog->note = $noteInput;
            $attendanceLog->save();
        }

        session()->flush();
        session()->regenerate();

        return redirect()->route('login')->with('success', 'Notes saved successfully');
    }

    public function allTaskList()
    {
        $staffs = User::whereIn('type', ['3', '2'])->orderby('id', 'DESC')->get();
        $managers = User::whereIn('type', ['3', '2'])->orderby('id', 'DESC')->get();
        $clients = Client::orderby('id', 'DESC')->get();
        $subServices = SubService::orderby('id', 'DESC')->get();
        return view('manager.task.index', compact('staffs', 'managers', 'clients', 'subServices'));
    }

    public function updateSubServiceStaff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientSubServiceId' => 'required',
            'newStaffId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $clientSubService = ClientSubService::findOrFail($request->clientSubServiceId);

        $clientSubService->staff_id = $request->newStaffId;
        $clientSubService->save();

        return response()->json([
            'message' => 'Staff updated successfully'
        ], 200);
    }

    public function changeSubServiceStatus(Request $request)
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

    public function changeServiceStatus(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'status' => 'required',
        ]);

        $clientService = ClientService::findOrFail($request->id);

        $clientService->status = $request->status;
        $clientService->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.'
        ]);
    }

    public function getOneTimeJobs(Request $request)
    {
        $authUserId = (string) auth()->id();
        $query = ClientService::where('type', 2)
            ->with(['service', 'messages'])
            ->where('manager_id', Auth::id())
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
                return \Carbon\Carbon::parse($clientService->legal_deadline)->format('d-m-Y');
            })
            ->editColumn('status', function ($clientService) {
                return $clientService->status;
            })
            ->addColumn('has_new_message', function ($clientService) {
                return $clientService->has_new_message ? 'Yes' : 'No';
            })
            ->make(true);
    }

    public function updateJobStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|integer|in:0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => 'Invalid status'], 422);
        }

        $clientService = ClientService::find($id);
        if (!$clientService) {
            return response()->json(['status' => 404, 'message' => 'Job not found'], 404);
        }

        $clientService->status = $request->status;
        $clientService->save();

        return response()->json(['status' => 200, 'message' => 'Status updated successfully']);
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
}
