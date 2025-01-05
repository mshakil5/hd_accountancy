<?php
  
namespace App\Http\Controllers;
 
use session;
use App\Models\User;
use App\Models\Client;
use App\Models\Service;
use App\Models\WorkTime;
use Illuminate\View\View;
use App\Models\SubService;
use Illuminate\Http\Request;
use App\Models\ProrotaDetail;
use Illuminate\Support\Carbon;
use App\Models\UserAttendanceLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ClientService;
  
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (auth()->user()->type == 'admin') {
            return redirect()->route('admin.home');
        }
        else if (auth()->user()->type == 'manager') {
            return redirect()->route('manager.home');
        }
        else if (auth()->user()->type == 'staff') {
            return redirect()->route('staff.home');
        }
        else{
           return redirect()->route('user.home');
        }
    } 
  
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

  
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function adminHome(): View
    {
        $loggedStaff = UserAttendanceLog::with('user')
            ->where('status', 0)
            ->whereNull('end_time')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($log) {
                $startTime = Carbon::parse($log->start_time);
                $endTime = $log->end_time ? Carbon::parse($log->end_time) : Carbon::now();
                $duration = $endTime->diff($startTime)->format('%H:%I:%S');
                $log->duration = $duration;

                $currentDayOfWeek = Carbon::now()->format('l');
                $prorotaDetail = ProrotaDetail::where('staff_id', $log->user_id)
                    ->where('day', $currentDayOfWeek)
                    ->first();

                if ($prorotaDetail) {
                    $log->is_late = $startTime->format('H:i:s') > $prorotaDetail->start_time;
                } else {
                    $log->is_late = false;
                    $log->prorotaNotFound = true;
                }

                $today = Carbon::today()->toDateString();
                   $workTime = WorkTime::where('staff_id', $log->user_id)
                    ->whereNotNull('start_time')
                    ->whereNull('end_time')
                    ->latest()
                    ->first();

                if ($workTime) {
                    if ($workTime->client_sub_service_id) {
                        $log->current_status = 'Working';
                    } elseif ($workTime->is_break == 1) {
                        $log->current_status = 'On Break';
                    } else {
                        $log->current_status = 'Logged In';
                    }
                } else {
                    $log->current_status = 'Logged In';
                }

                return $log;
            })
            ->values(); 

            $today = now()->toDateString();
            $lateStaff = UserAttendanceLog::with('user')
            ->orderBy('id', 'desc')
            ->get()
            ->filter(function ($log) {
                $startTime = Carbon::parse($log->start_time);
                $currentDayOfWeek = Carbon::now()->format('l');
                $prorotaDetail = ProrotaDetail::where('staff_id', $log->user_id)
                    ->where('day', $currentDayOfWeek)
                    ->first();

                if ($prorotaDetail) {
                    $log->is_late = $startTime->gt(Carbon::parse($prorotaDetail->start_time));
                } else {
                    $log->is_late = true;
                }

                return $log->is_late;
            })
            ->pluck('user');

            $absentStaff = User::whereIn('type', [2, 3])
                ->whereNotIn('id', function ($query) use ($today) {
                    $query->select('user_id')
                        ->from('user_attendance_logs')
                        ->whereDate('start_time', $today);
                })
                ->whereNotIn('id', function ($query) use ($today) {
                    $query->select('staff_id')
                        ->from('holiday_requests')
                        ->where('status', 1)
                        ->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today);
                })
                ->with('logComments')
                ->orderBy('id', 'desc')
                ->get();
                $totalAbsentStaffCount = $absentStaff->count();

                $currentDayOfWeek = Carbon::now()->format('l');
                $prorotaDetails = ProrotaDetail::where('day', $currentDayOfWeek)->get();

                    $allLogs = UserAttendanceLog::with('user')
                        ->whereHas('user', function ($query) use ($prorotaDetails) {
                            $query->whereIn('id', $prorotaDetails->pluck('staff_id'));
                        })
                        ->orderBy('end_time', 'desc')
                        ->get();

                    $latestLogs = [];
                    foreach ($allLogs as $attendanceLog) {
                        if (!isset($latestLogs[$attendanceLog->user_id])) {
                            $latestLogs[$attendanceLog->user_id] = $attendanceLog;
                        }
                    }

                $filteredLogs = [];
                foreach ($latestLogs as $latestLog) {
                    if (isset($latestLog->end_time)) {
                        $prorotaDetail = $prorotaDetails->firstWhere('staff_id', $latestLog->user_id);

                        if ($prorotaDetail) {
                            $endTime = Carbon::parse($latestLog->end_time);
                            $scheduledEndTime = Carbon::parse($prorotaDetail->end_time);

                            $departureStatus = '';
                            if ($endTime->eq($scheduledEndTime)) {
                                $departureStatus = 'On-Time';
                            } elseif ($endTime->lt($scheduledEndTime)) {
                                $departureStatus = 'Early Leave';
                            } else {
                                $departureStatus = 'Late Leave';
                            }

                            $latestLog->departure_status = $departureStatus;

                            $filteredLogs[] = $latestLog;
                        }
                    }
                }

        $staffs = User::whereIn('type', ['3', '2'])->select('id', 'first_name', 'last_name')->orderBy('id', 'DESC')->get();
        $managers = User::whereIn('type', ['3', '2'])->select('id', 'first_name', 'last_name')->orderBy('id', 'DESC')->get();
        $services = Service::orderBy('id', 'DESC')->where('status', '1')->select('id', 'name')->get();

        return view('admin.dashboard', compact('staffs', 'loggedStaff', 'managers', 'services', 'lateStaff', 'absentStaff','filteredLogs','totalAbsentStaffCount'));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function managerHome(): View
    {
        $clients = Client::orderBy('id', 'DESC')->select('id', 'name')->get();
        $subServices = SubService::orderby('id','DESC')->select('id', 'name')->get();
        $staffs = User::whereIn('type', ['3','2'])->select('id', 'first_name', 'last_name')->orderby('id','DESC')->get();
        $managers = User::whereIn('type', ['3','2'])->select('id', 'first_name', 'last_name')->orderby('id','DESC')->get();
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

        $activeTimeFormatted = gmdate('H:i:s', $activeTimeInSeconds);

        $clients = Client::orderby('id','DESC')->select('id', 'name')->get();
        return view('manager.dashboard',compact('staffs','managers','activeTimeFormatted','clients','subServices', 'clients'));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function staffHome(): View
    {
        $staffs = User::whereIn('type', ['3','2'])->select('id', 'first_name', 'last_name')->orderby('id','DESC')->get();
        $managers = User::whereIn('type', ['3','2'])->select('id', 'first_name', 'last_name')->orderby('id','DESC')->get();
        $clients = Client::orderby('id','DESC')->select('id', 'name')->get();
        $subServices = SubService::orderby('id','DESC')->select('id', 'name')->get();
        
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

        $activeTimeFormatted = gmdate('H:i:s', $activeTimeInSeconds);

        $clients = Client::orderby('id','DESC')->select('id', 'name')->get();

        return view('staff.dashboard', compact('activeTimeFormatted','staffs','managers','clients','subServices', 'clients'));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function userHome(): View
    {
        return view('user.dashboard');
    }

    public function customlogout(Request $request, $attendenceId)
    {
        $attendanceLog = UserAttendanceLog::find($attendenceId);
        if ($attendanceLog) {
            if ($request->has('note')) {
                $attendanceLog->note = $request->note;
            }
            $attendanceLog->end_time = Carbon::now();
            $startTime = Carbon::parse($attendanceLog->start_time);
            $endTime = Carbon::parse($attendanceLog->end_time);
            $attendanceLog->duration = $endTime->diffInSeconds($startTime);
            \Session::getHandler()->destroy($attendanceLog->session_id);
            $attendanceLog->session_id = null;
            $attendanceLog->status = 1;
            $attendanceLog->save();
        } 
    }

    public function toggleSidebar(Request $request)
    {
        $user = Auth::user();
        $user->sidebar = $request->input('sidebar');
        $user->save();

        return redirect()->route('admin.home');
    }

}