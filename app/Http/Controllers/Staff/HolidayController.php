<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\HolidayRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\UserAttendanceLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    public function index()
    {
        $pending = HolidayRequest::where('staff_id', Auth::user()->id)->where('status', 0)->sum('total_day');
        $taken = HolidayRequest::where('staff_id', Auth::user()->id)->where('status', 1)->sum('total_day');
        $holidayRequests = HolidayRequest::where('staff_id', Auth::user()->id)
                                        ->orderBy('id', 'desc')
                                        ->get();

        return view('staff.holiday.index', compact('pending','taken','holidayRequests'));
    }


    public function holidayRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'holiday_type' => 'max:255',
            'comment' => 'required|string|max:255',
            'start_date' => 'required',
            'start_date' => ['required_with:end_date'],
            'end_date' => ['required_with:start_date'],
        ],[
            'holiday_type' => 'Please select holiday type.',
            'start_date' => 'Date field required',
            'start_date.required_with' => 'Start date is required when end date is provided.',
            'end_date.required_with' => 'End date is required when start date is provided.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }
        
        // Convert the database date fields to Carbon instances
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Calculate the difference in days
        $differenceInDays = $startDate->diffInDays($endDate);

        $data = new HolidayRequest();
        $data->staff_id = Auth::user()->id;
        $data->holiday_type = $request->holiday_type;
        $data->start_date = $request->start_date;
        $data->end_date = $request->end_date;
        $data->total_day = $differenceInDays+1;
        $data->comment = $request->comment;
        if ($data->save()) {
            return response()->json(['status' => 200, 'message' => 'Holiday request send successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }

    }


    public function indexManager()
    {
        $pending = HolidayRequest::where('staff_id', Auth::user()->id)->where('status', 0)->sum('total_day');
        $taken = HolidayRequest::where('staff_id', Auth::user()->id)->where('status', 1)->sum('total_day');
        $holidayRequests = HolidayRequest::where('staff_id', Auth::user()->id)
                                        ->orderBy('id', 'desc')
                                        ->get();

        return view('manager.holiday.index', compact('pending','taken','holidayRequests'));
    }

    public function holidayRequestManager(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'holiday_type' => 'max:255',
            'comment' => 'required|string|max:255',
            'start_date' => 'required',
            'start_date' => ['required_with:end_date'],
            'end_date' => ['required_with:start_date'],
        ],[
            'holiday_type' => 'Please select holiday type.',
            'start_date' => 'Date field required',
            'start_date.required_with' => 'Start date is required when end date is provided.',
            'end_date.required_with' => 'End date is required when start date is provided.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }
        
        // Convert the database date fields to Carbon instances
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Calculate the difference in days
        $differenceInDays = $startDate->diffInDays($endDate);

        $data = new HolidayRequest();
        $data->staff_id = Auth::user()->id;
        $data->holiday_type = $request->holiday_type;
        $data->start_date = $request->start_date;
        $data->end_date = $request->end_date;
        $data->total_day = $differenceInDays+1;
        $data->comment = $request->comment;
        if ($data->save()) {
            return response()->json(['status' => 200, 'message' => 'Holiday request send successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }

    }



}
