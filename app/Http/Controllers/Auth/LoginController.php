<?php
  
namespace App\Http\Controllers\Auth;
  
use Illuminate\Http\Request;
use App\Models\UserAttendanceLog;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
  
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */
  
    use AuthenticatesUsers;
  
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;
  
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    
    /**
     * Create a new controller instance.
     *
     * @return RedirectResponse
     */
    public function login(Request $request): RedirectResponse
    {   
        $input = $request->all();
    
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        if(auth()->attempt(array('email' => $input['email'], 'password' => $input['password'])))
        {
            $user = Auth::user();

            if ($user->type == 'staff' && $user->status == 0) {
                auth()->logout();
                return redirect()->route('login')
                    ->withErrors(['email' => 'You cannot log in. Your account is deactivated.'])
                    ->withInput($request->except('password'));
            }

            if($user->type == 'staff' || $user->type == 'manager'){
                $attendanceLog = new UserAttendanceLog();
                $attendanceLog->user_id = $user->id;
                $attendanceLog->start_time = now();
                $attendanceLog->status = 0;
                $attendanceLog->session_id = Session::getId();
                $attendanceLog->created_by = Auth::user()->id;
                $attendanceLog->save();
            }

            if (auth()->user()->type == 'admin') {
                return redirect()->route('admin.home');
            } else if (auth()->user()->type == 'manager') {
                return redirect()->route('manager.home');
            } else if (auth()->user()->type == 'staff'){
                return redirect()->route('staff.home');
            } else {
                return redirect()->route('user.home');
            }
        } else {
            return redirect()->route('login')
            ->withErrors(['email' => 'Wrong credentials.'])
            ->withInput($request->except('password'));
        }
    }

}