<?php
  
namespace App\Http\Controllers;
 
use App\Models\Client;
use App\Models\ClientService;
use App\Models\ProrotaDetail;
use App\Models\Service;
use App\Models\SubService;
use App\Models\User;
use App\Models\UserAttendanceLog;
use App\Models\WorkTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use session;
  
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
        $today = Carbon::today();

        $loggedStaff = UserAttendanceLog::with(['user:id,first_name,last_name'])
            ->where('user_id', '!=', auth()->id())
            ->select('id', 'user_id', 'start_time', 'end_time', 'status')
            ->where('status', 0)
            ->whereNull('end_time')
            ->whereDate('start_time', Carbon::today())
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

        $lateStaff = UserAttendanceLog::with(['user:id,first_name,last_name'])
            ->where('user_id', '!=', auth()->id())
            ->select(['id', 'user_id', 'start_time'])
            ->whereDate('start_time', Carbon::today())
            ->orderBy('start_time', 'asc')
            ->get()
            ->unique('user_id')
            ->filter(function ($log) {
                $startTime = Carbon::parse($log->start_time);
                $currentDayOfWeek = Carbon::now()->format('l');
        
                $prorotaDetail = ProrotaDetail::where('staff_id', $log->user_id)
                    ->where('day', $currentDayOfWeek)
                    ->select('start_time')
                    ->first();
        
                if (!$prorotaDetail) {
                    return false;
                }
        
                $log->is_late = $startTime->gt(Carbon::parse($prorotaDetail->start_time));
                return $log->is_late;
            })
            ->values();

            $formattedToday = Carbon::now()->format('d-m-Y');

            $absentStaff = User::select('id', 'first_name', 'last_name', 'type')
            ->whereIn('type', [2, 3])
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
            ->with(['logComments' => function ($query) use ($formattedToday) {
                $query->where('comment_date', $formattedToday);
            }])
            ->orderBy('id', 'desc')
            ->get();
            
        $totalAbsentStaffCount = $absentStaff->count();

        $currentDayOfWeek = Carbon::now()->format('l');

        $staffIds = ProrotaDetail::where('day', $currentDayOfWeek)
            ->pluck('staff_id');

        $allLogs = UserAttendanceLog::with(['user:id,first_name,last_name'])
            ->select('user_id', 'end_time')
            ->whereDate('end_time', Carbon::today())
            ->whereIn('user_id', $staffIds)
            ->orderBy('end_time', 'desc')
            ->get();

        $latestLogs = $allLogs->unique('user_id');

        $filteredLogs = $latestLogs->map(function ($log) use ($staffIds, $currentDayOfWeek) {
            $prorotaDetail = ProrotaDetail::where('staff_id', $log->user_id)
                ->where('day', $currentDayOfWeek)
                ->first();

            if ($prorotaDetail && isset($log->end_time)) {
                $endTime = Carbon::parse($log->end_time);
                $scheduledEndTime = Carbon::parse($prorotaDetail->end_time);

                if ($endTime->eq($scheduledEndTime)) {
                    $log->departure_status = 'On-Time';
                } elseif ($endTime->lt($scheduledEndTime)) {
                    $log->departure_status = 'Early Leave';
                } else {
                    $log->departure_status = 'Late Leave';
                }
            }

            return $log;
        });

        $staffs = User::whereIn('type', ['3', '2', '1'])->select('id', 'first_name', 'last_name', 'type')->orderBy('id', 'DESC')->get();
        $managers = User::whereIn('type', ['2', '1'])->select('id', 'first_name', 'last_name', 'type')->orderBy('id', 'DESC')->get();
        $services = Service::orderBy('id', 'DESC')->where('status', '1')->select('id', 'name')->get();
        $clients = Client::orderby('id','DESC')->select('id', 'name', 'refid')->get();
        return view('admin.dashboard', compact('staffs', 'loggedStaff', 'managers', 'services', 'lateStaff', 'absentStaff','filteredLogs','totalAbsentStaffCount','clients'));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function managerHome(): View
    {
        $clients = Client::orderBy('id', 'DESC')->select('id', 'name', 'refid')->get();
        $subServices = SubService::orderby('id','DESC')->select('id', 'name')->get();
        $user = auth()->user();
        $staffs = $user->staffs()
        ->select('users.id', 'users.first_name', 'users.last_name', 'users.type')
        ->orderBy('users.id', 'DESC')
        ->get()
        ->push($user->only(['id','first_name','last_name','type']));
        $managers = User::whereIn('type', ['3','2'])->select('id', 'first_name', 'last_name')->orderby('id','DESC')->get();
        $users = User::whereIn('type', ['3','2', '1'])->select('id', 'first_name', 'last_name', 'type')->orderby('id','DESC')->get();
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

        return view('manager.dashboard',compact('staffs','managers','activeTimeFormatted','clients','subServices', 'users'));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function staffHome(): View
    {
        $staffs = User::whereIn('type', ['3','2', '1'])->select('id', 'first_name', 'last_name')->orderby('id','DESC')->get();
        $managers = User::whereIn('type', ['3','2'])->select('id', 'first_name', 'last_name')->orderby('id','DESC')->get();
        $clients = Client::orderby('id','DESC')->select('id', 'name', 'refid')->get();
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

        $users = User::whereIn('type', ['3','2', '1'])->select('id', 'first_name', 'last_name', 'type')->orderby('id','DESC')->get();
        return view('staff.dashboard', compact('activeTimeFormatted','staffs','managers','clients','subServices', 'users'));
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

    public function clean()
    {
      $tables = [
        'accountancy_fees',
        'activity_log',
        'business_infos',
        'clients',
        'client_credentials',
        'client_properties',
        'client_service',
        'client_sub_services',
        'contact_infos',
        'director_infos',
        'holiday_records',
        'holiday_requests',
        'log_comments',
        'notes',
        'prorotas',
        'prorota_details',
        'recent_updates',
        'service_messages',
        'service_staff',
        'staff_holiday_types',
        'time_slots',
        'user_attendance_logs',
        'user_managers',
        'work_times',
      ];

      Schema::disableForeignKeyConstraints();

      foreach ($tables as $table) {
        DB::table($table)->truncate();
      }

      DB::table('users')->where('id', '!=', 1)->delete();
      Schema::enableForeignKeyConstraints();

      return 'Tables truncated successfully.';
    }

}