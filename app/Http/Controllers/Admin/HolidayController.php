<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    public function index()
    {   
        return view('admin.holiday.index');
    }

    public function create()
    {
        $staffs = User::whereIn('type', ['2','3'])->select('id','first_name','last_name','email')->orderby('id','DESC')->get();
        // dd($staffs);
        return view('admin.holiday.create', compact('staffs'));
    }

    
}
