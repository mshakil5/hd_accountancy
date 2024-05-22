<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Client;
use App\Models\HolidayType;
use Illuminate\Http\Request;
use App\Models\HolidayRecord;
use App\Models\HolidayRequest;
use Illuminate\Support\Carbon;
use App\Models\StaffHolidayType;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class HolidayController extends Controller
{
    public function index()
    {  
        $holidayTypes = HolidayType::orderBy('id', 'desc')->get();
        return view('admin.holiday.index',compact('holidayTypes'));
    }

    public function create()
    {
        $staffs = User::whereIn('type', ['2','3'])->select('id','first_name','last_name','email')->orderby('id','DESC')->get();
        return view('admin.holiday.create', compact('staffs'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required',
            'start_date' => 'required',
            'start_date' => ['required_with:end_date'],
            'end_date' => ['required_with:start_date'],
        ],[
            'start_date.required_with' => 'Start date is required when end date is provided.',
            'end_date.required_with' => 'End date is required when start date is provided.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }
        
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $differenceInDays = $startDate->diffInDays($endDate);

        $data = new HolidayRequest();
        $data->staff_id = $request->staff_id;
        $data->start_date = $request->start_date;
        $data->end_date = $request->end_date;
        $data->total_day = $differenceInDays+1;
        if ($data->save()) {

            if ($data->status == 0) {
                $holidayRecord = HolidayRecord::where('staff_id', $request->staff_id)->first();
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                $differenceInDays = $startDate->diffInDays($endDate) + 1;

                if (!$holidayRecord) {
                    $holidayRecord = new HolidayRecord();
                    $holidayRecord->staff_id = $request->staff_id;
                    $holidayRecord->pending_holidays = $differenceInDays;
                    $holidayRecord->year = now()->year;
                    $holidayRecord->created_by = auth()->user()->id;
                    $holidayRecord->save();
                }  else {
                    $holidayRecord->pending_holidays = $holidayRecord->pending_holidays + $differenceInDays; 
                    $holidayRecord->updated_by = auth()->user()->id;
                    $holidayRecord->save();
                }
            }

            return response()->json(['status' => 200, 'message' => 'Holiday request send successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }

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
                ->addColumn('staff_id', function($row) {
                    return $row->staff ? $row->staff->id : null;
                })
                ->addColumn('DT_RowId', function($row) {
                    return $row->id;
                })
                ->make(true);
        }
    }

    public function storeHoliday(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'holiday_id' => 'required',
            'holiday_type_id' => 'required',
            'admin_note' => 'required',
            'status' => 'required',  
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $holiday = HolidayRequest::find($request->holiday_id);
        if ($holiday) {
            $holiday->holiday_type_id = $request->holiday_type_id;
            $holiday->admin_note = $request->admin_note;
            $holiday->status = $request->status;
            $holiday->save();

            if ($holiday->status == 1) {
                $holidayRecord = HolidayRecord::where('staff_id', $request->staff_id)->first();
                $differenceInDays = $holiday->total_day;
                $holidayRecord->booked_holidays = $holidayRecord->booked_holidays + $differenceInDays; 
                $holidayRecord->pending_holidays = $holidayRecord->pending_holidays - $differenceInDays;
                $holidayRecord->updated_by = auth()->user()->id;
                $holidayRecord->save();
            }

            return response()->json(['success' => true]);
        }
    }

    public function getHolidaysForStaff(Request $request)
    {
        $staffId = $request->input('staff_id');
        $holidays = StaffHolidayType::where('staff_id', $staffId)
            ->with('holidayType')
            ->get(['id', 'holiday_type_id', 'day']); 

        $holidayTypes = HolidayType::all();

        $holidaysWithDays = [];

        foreach ($holidayTypes as $holidayType) {
            $holiday = $holidays->firstWhere('holiday_type_id', $holidayType->id);
            $holidaysWithDays[] = [
                'staff_holiday_type_id' => $holiday ? $holiday->id : null,
                'holiday_type_id' => $holidayType->id,
                'day' => $holiday ? $holiday->day : 0,
                'holiday_type_name' => $holidayType->type,
            ];
        }

        return response()->json($holidaysWithDays);
    }

    public function updateDay(Request $request)
    {
        $request->validate([
            'staff_id' => 'required',
            'day' => 'required',
        ]);

        $staffHolidayType = StaffHolidayType::find($request->input('staffHolidayTypeId'));

        if ($staffHolidayType) {
            $staffHolidayType->day = $request->input('day');
            $staffHolidayType->updated_by = Auth::id();
            $staffHolidayType->save();

            return response()->json(['success' => true, 'message' => 'Day updated successfully']);
        } else {
            $newStaffHolidayType = new StaffHolidayType;
            $newStaffHolidayType->staff_id = $request->input('staff_id');
            $newStaffHolidayType->day = $request->input('day');
            $newStaffHolidayType->holiday_type_id = $request->input('holiday_type_id');
            $newStaffHolidayType->created_by = Auth::id();
            $newStaffHolidayType->save();

            return response()->json(['success' => true, 'message' => 'New StaffHolidayType created successfully']);
        }
    }

    public function editHoliday($id)
    {
        $holiday = HolidayRequest::findOrFail($id); 
        $staff_id = $holiday->staff_id;
        $clientName = USer::findOrFail($staff_id)->first_name;
        $holidayTypes = HolidayType::orderBy('id', 'desc')->get();
        return view('admin.holiday.edit_holiday', compact('holiday','clientName','holidayTypes','staff_id'));
    }

    public function updateHoliday(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'holiday_type_id' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'comment' => 'nullable|string|max:255',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $holiday = HolidayRequest::findOrFail($id);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1; 
        $previousTotalDays= $holiday->total_day;
        
            if ($totalDays > $previousTotalDays) {
            $difference = $totalDays - $previousTotalDays;
            $holidayRecord = HolidayRecord::where('staff_id', $request->staff_id)->latest()->first();
            $holidayRecord->pending_holidays = $holidayRecord->pending_holidays + $difference;
            $holidayRecord->save();
        } else {
            $difference = $previousTotalDays - $totalDays;
            $holidayRecord = HolidayRecord::where('staff_id', $request->staff_id)->first();
            $holidayRecord->pending_holidays = $holidayRecord->pending_holidays - $difference;
            $holidayRecord->save();
        }
        
        $holiday->holiday_type_id = $request->holiday_type_id;
        $holiday->start_date = $request->start_date;
        $holiday->end_date = $request->end_date;
        $holiday->comment = $request->comment;
        $holiday->admin_note = $request->admin_note;
        $holiday->status = $request->status;
        $holiday->total_day = $totalDays;

        $holiday->save();

        if ($holiday->status == 1) {
            $holidayRecord = HolidayRecord::where('staff_id', $request->staff_id)->first();
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $differenceInDays = $startDate->diffInDays($endDate) + 1;
            $holidayRecord->booked_holidays = $holidayRecord->booked_holidays + $differenceInDays; 
            $holidayRecord->pending_holidays = $holidayRecord->pending_holidays - $differenceInDays;
            $holidayRecord->updated_by = auth()->user()->id;
            $holidayRecord->save();
        }

         return response()->json(['success' => true]);
    }

}
