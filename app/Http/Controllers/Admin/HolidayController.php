<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\HolidayRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
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

     public function getholiday(Request $request)
    {
        if ($request->ajax()) {
            $data = HolidayRequest::orderBy('id', 'DESC')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('staff_name', function($row) {
                    return $row->staff ? $row->staff->first_name : '';
                })
                ->make(true);
        }
    }
    
}