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
    
        if ($request->ajax()) {
            $data = ClientService::with(['clientSubServices' => function ($query) {
                    $query->where('staff_id', Auth::id())
                          ->whereIn('sequence_status', [0, 1]);
                }])
                ->where(function ($query) use ($currentUserId) {
                    $query->where('manager_id', $currentUserId)
                          ->orWhereHas('clientSubServices', function ($subQuery) use ($currentUserId) {
                              $subQuery->where('staff_id', $currentUserId)
                                       ->whereIn('sequence_status', [0, 1]);
                          });
                })
                ->distinct()
                ->orderBy('id', 'desc')
                ->get();
    
            return DataTables::of($data)
                ->addColumn('clientname', function (ClientService $clientservice) {
                    return $clientservice->client ? $clientservice->client->name : '';
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
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
                ->make(true);
        }
    }    

    public function getAllServices(Request $request)
    {
        if ($request->ajax()) {
            $data = ClientService::with('clientSubServices')
                ->whereHas('clientSubServices', function ($query) {
                    $query->where('staff_id', Auth::id());
                })
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)

                ->addColumn('clientname', function (ClientService $clientservice) {
                    return $clientservice->client ? $clientservice->client->name : " ";
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : " ";
                })
                ->addColumn('action', function (ClientService $clientservice) {
                    $managerFirstName = $clientservice->manager ? $clientservice->manager->first_name . ' ' . $clientservice->manager->last_name : 'N/A';
                    return '<button class="btn btn-secondary change-status" data-id="' . $clientservice->id . '" data-manager-firstname="' . $managerFirstName . '">Details</button>';
                })
                ->make(true);
        }
    }

    public function getClientSubServices($clientserviceId)
    {
        $clientSubServices = ClientSubService::with('subService', 'serviceMessage', 'workTimes', 'clientService')->where('client_service_id', $clientserviceId)->get();
        return response()->json($clientSubServices);
    }

    public function getServiceMessages($clientSubServiceId)
    {
        $messages = ServiceMessage::with('user:id,first_name')
            ->select('created_by', 'message')
            ->where('client_sub_service_id', $clientSubServiceId)
            ->get()
            ->map(function($message) {
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

    public function getCompetedServices(Request $request)
    {
        $managerName = Auth::user()->first_name;

        if ($request->ajax()) {
            $data = ClientService::with('clientSubServices')
                ->whereHas('clientSubServices', function ($query) {
                    $query->where('sequence_status', 2)
                        ->where('staff_id', Auth::id());
                })
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)

                ->addColumn('clientname', function (ClientService $clientservice) {
                    return $clientservice->client ? $clientservice->client->name : '';
                })
                ->addColumn('servicename', function (ClientService $clientservice) {
                    return $clientservice->service ? $clientservice->service->name : '';
                })
                ->addColumn('action', function (ClientService $clientservice) use ($managerName) {
                    return '<button class="btn btn-secondary task-details" data-id="' . $clientservice->id . '" data-manager="' . $managerName . '">Details</button>';
                })
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
                $clientSubService->save();

                $workTime = new WorkTime();
                $workTime->client_sub_service_id = $clientSubService->id;
                $workTime->start_time = $request->start_times[$i];
                $workTime->end_time = $request->end_times[$i];
                $workTime->staff_id = $staffId;
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
            'status' => 'required|in:0,2',
        ]);

        $clientService = ClientService::findOrFail($request->id);

        $clientService->status = $request->status;
        $clientService->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.'
        ]);
    }
}
