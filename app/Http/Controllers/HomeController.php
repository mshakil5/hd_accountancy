<?php
  
namespace App\Http\Controllers;
 
use session;
use App\Models\User;
use App\Models\Client;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\UserAttendanceLog;
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


     // $loggedStaff = UserAttendanceLog::with('user')
        //     ->where('status', 0)
        //     ->orderBy('id', 'desc')
        //     ->get()
        //     ->map(function ($log) {
        //         $duration = Carbon::now()->diff($log->start_time);
        //         $log->duration = $duration->format('%H:%I:%S');
        //         return $log;
        //     });


    public function adminHome(): View
    {
        $clients = Client::orderby('id','DESC')->get();
        $staffs = User::where('type','3')->orderby('id','DESC')->get();
        return view('admin.dashboard',compact('clients','staffs'));
    }

  
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function managerHome(): View
    {
        return view('manager.dashboard');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    // public function staffHome(): View
    // {
    //     return view('staff.dashboard');
    // }

    public function staffHome(): View
    {
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
        return view('staff.dashboard', compact('activeTime', 'breakTime'));
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

}