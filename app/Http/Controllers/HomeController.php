<?php
  
namespace App\Http\Controllers;
 
use session;
use App\Models\User;
use App\Models\Client;
use App\Models\Service;
use Illuminate\View\View;
use App\Models\SubService;
use Illuminate\Http\Request;
use App\Models\ProrotaDetail;
use Illuminate\Support\Carbon;
use App\Models\UserAttendanceLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
  
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
                ->whereNotIn('id', function ($query) use ($today) {
                    $currentDayOfWeek = Carbon::now()->format('l');
                    $query->select('staff_id')
                        ->from('prorota_details')
                        ->where('day', $currentDayOfWeek)
                        ->whereTime('start_time', '>', now());
                })
                ->with('logComments')
                ->orderBy('id', 'desc')
                ->get();
                // dd($absentStaff);

        $clients = Client::orderBy('id', 'DESC')->get();
        $staffs = User::whereIn('type', ['3', '2'])->orderBy('id', 'DESC')->get();
        $managers = User::whereIn('type', ['3', '2'])->orderBy('id', 'DESC')->get();
        $services = Service::orderBy('id', 'DESC')->get();

        return view('admin.dashboard', compact('clients', 'staffs', 'loggedStaff', 'managers', 'services', 'lateStaff', 'absentStaff'));
    }

  
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function managerHome(): View
    {
        $staffs = User::whereIn('type', ['3','2'])->orderby('id','DESC')->get();
        $managers = User::whereIn('type', ['3','2'])->orderby('id','DESC')->get();
        return view('manager.dashboard',compact('staffs','managers'));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function staffHome(): View
    {
        $staffs = User::whereIn('type', ['3','2'])->orderby('id','DESC')->get();
        $managers = User::whereIn('type', ['3','2'])->orderby('id','DESC')->get();
        $clients = Client::orderby('id','DESC')->get();
        $subServices = SubService::orderby('id','DESC')->get();
        $user = Auth::user();
        $attendanceLog = UserAttendanceLog::where('user_id', $user->id)
            ->orderBy('start_time', 'desc')
            ->first();

        $activeTime = $breakTime = null;
        if ($attendanceLog) {
            $startTime = Carbon::parse($attendanceLog->start_time);
            $endTime = Carbon::parse($attendanceLog->end_time);
            $timeDifference = $startTime->diff($endTime);
            $activeTime = $timeDifference->format('%H:%I:%S');
            $breakTime = $activeTime;
        }
        return view('staff.dashboard', compact('activeTime', 'breakTime','staffs','managers','clients','subServices'));
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

    public function sessionClear()
    {
        $user = Auth::user();
        $attendanceLog = UserAttendanceLog::where('user_id', $user->id)
            ->where('status', 0)
            ->latest()
            ->first();

        if ($attendanceLog) {
            $attendanceLog->end_time = now();
            $attendanceLog->status = 1;
            $attendanceLog->note = request()->input('note');
            $attendanceLog->save();
        }

        session()->flush();
        session()->regenerate();
        return redirect()->route('home');
    }

    // public function sessionClearByAdmin(Request $request)
    // {
    //     $staffId = $request->input('staffId');
    //     $user = User::find($staffId);

    //     // dd($user);
    //     if ($user) {
    //         $attendanceLog = UserAttendanceLog::where('user_id', $user->id)
    //             ->where('status', 0)
    //             ->latest()
    //             ->first();

    //         if ($attendanceLog) {
    //             $attendanceLog->end_time = now();
    //             $attendanceLog->status = 1;
    //             $attendanceLog->note = $request->input('note');
    //             $attendanceLog->save();
    //         }

    //         // Auth::logoutOtherDevices($user->id);

    //         return response()->json(['success' => true]);
    //     }

    //     return response()->json(['success' => false]);
    // }

    public function sessionClearByAdmin($userId)
    {

        $user = User::find($userId);

        if ($user ) {
            // Auth::logoutOtherDevices($user->id);
            // Auth::logout($user);
            // $user->session()->delete();

            return response()->json(['message' => 'Staff member logged out successfully'], 200);
        } else {
            return response()->json(['error' => 'Staff member not found'], 404);
        }
    }


    // Optionally, you can update the logged_in_at column to null
            // $user->update(['logged_in_at' => null]);


}