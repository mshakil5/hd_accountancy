<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\HolidayType;
use Illuminate\Http\Request;
use App\Models\HolidayRequest;
use App\Models\StaffHolidayType;
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
            'holiday_id' => 'required',
            'admin_note' => 'required',
            'status' => 'required',  
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $holiday = HolidayRequest::find($request->holiday_id);

        if ($holiday) {
            $holiday->update([
                'holiday_type' => $request->holiday_type,
                'admin_note' => $request->admin_note,
                'status' => $request->status,
            ]);
            return response()->json(['success' => true]);
        } else {
            return response()->json(['error' => 'Holiday not found'], 404);
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


}
