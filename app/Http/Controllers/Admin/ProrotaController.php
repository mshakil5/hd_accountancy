<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prorota;
use App\Models\ProrotaDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Models\Activity;

class ProrotaController extends Controller
{

  public function prorotaLog($id)
  {
      $prorota = Prorota::find($id);
  
      if (!$prorota) {
          return redirect()->back()->with('error', 'Prorota record not found.');
      }
  
      $prorotaLogs = Activity::with('causer')
          ->where('subject_type', Prorota::class)
          ->where('subject_id', $prorota->id)
          ->latest()
          ->get();
  
      $prorotaDetailLogs = Activity::with('causer')
          ->where('subject_type', ProrotaDetail::class)
          ->whereIn('subject_id', $prorota->prorotaDetail->pluck('id'))
          ->latest()
          ->get();
  
      return view('admin.prorota.prorota_log', compact('prorota', 'prorotaLogs', 'prorotaDetailLogs'));
  }

    public function index()
    {   
        return view('admin.prorota.index');
    }

    public function getprorota(Request $request)
    {
        if ($request->ajax()) {
            $data = Prorota::orderBy('id', 'DESC')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('staff_name', function($row) {
                  $firstName = $row->staff ? $row->staff->first_name : '';
                  $lastName = $row->staff ? $row->staff->last_name : '';
                  
                  return trim($firstName . ' ' . $lastName);
              })
                ->make(true);
        }
    }

    public function create()
    {
        $staffs = User::whereIn('type', ['2', '3'])
            ->whereNotIn('id', function ($query) {
                $query->select('staff_id')->from('prorotas');
            })
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('id', 'DESC')
            ->get();
    
        return view('admin.prorota.create', compact('staffs'));
    }    

    public function store(Request $request)
    {

        

        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|max:255',
            'schedule_type' => 'string|max:255',
            'start_time.*' => ['required_with:end_time.*'],
            'end_time.*' => ['required_with:start_time.*'],
        ],[
            'staff_id' => 'Please select an employee.',
            'start_time.*.required_with' => 'Start time is required when end time is provided.',
            'end_time.*.required_with' => 'End time is required when start time is provided.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $days = $request->day;
        $start_times = $request->start_time;
        $end_times = $request->end_time;

        $data = new Prorota;
        $data->staff_id = $request->staff_id;
        $data->schedule_type = $request->schedule_type;
        if ($data->save()) {
            foreach ($request->day as $key => $day) {
                if (!empty($request->get('start_time')[$key])) {
                    $schedule = new ProrotaDetail();
                    $schedule->staff_id = $request->staff_id;
                    $schedule->prorota_id = $data->id;
                    $schedule->day =  $request->get('day')[$key];
                    $schedule->start_time = $request->get('start_time')[$key];
                    $schedule->end_time = $request->get('end_time')[$key];
                    $schedule->save();
                }
                

            }
            return response()->json(['status' => 200, 'message' => 'Staff schedule created successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }

    }

    public function showDetails($id)
    {
        $data = Prorota::with('prorotaDetail')->findOrFail($id);
        return view('admin.prorota.details', compact('data'));
    }

    public function deleteData($id)
    {
            $user = Prorota::findOrFail($id);
            $user->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Data deleted successfully.'
            ]);
    }

    public function edit($id)
    {
        $staffs = User::whereIn('type', ['2','3'])->select('id','first_name','last_name','email')->orderby('id','DESC')->get();
        $data = Prorota::with('prorotaDetail')->findOrFail($id);
        return view('admin.prorota.edit', compact('data','staffs'));
    }

    public function update(Request $request)
    {

        

        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|max:255',
            // 'schedule_type' => '|string|max:255',
            'start_time.*' => ['required_with:end_time.*'],
            'end_time.*' => ['required_with:start_time.*'],
            'day.*' => ['distinct'],
        ],[
            'staff_id' => 'Please select an employee.',
            'start_time.*.required_with' => 'Start time is required when end time is provided.',
            'end_time.*.required_with' => 'End time is required when start time is provided.',
            'day.*.distinct' => 'Duplicate values are not allowed for the day field.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $data = Prorota::find($request->prorota_id);
        $data->staff_id = $request->staff_id;
        $data->schedule_type = $request->schedule_type;
        if ($data->save()) {

            $missingIds = ProrotaDetail::where('prorota_id', $data->id)
                ->whereNotIn('id', $request->prorotaDetail_id)
                ->pluck('id');

            ProrotaDetail::whereIn('id', $missingIds)->delete();


            foreach ($request->day as $key => $day) {
                if (!empty($request->get('start_time')[$key])) {
                    if ($request->get('prorotaDetail_id')[$key]) {
                        $schedule = ProrotaDetail::find($request->get('prorotaDetail_id')[$key]);
                        $schedule->staff_id = $request->staff_id;
                        $schedule->prorota_id = $data->id;
                        $schedule->day =  $request->get('day')[$key];
                        $schedule->start_time = $request->get('start_time')[$key];
                        $schedule->end_time = $request->get('end_time')[$key];
                        $schedule->save();
                    } else {
                        
                        $schedule = new ProrotaDetail();
                        $schedule->staff_id = $request->staff_id;
                        $schedule->prorota_id = $data->id;
                        $schedule->day =  $request->get('day')[$key];
                        $schedule->start_time = $request->get('start_time')[$key];
                        $schedule->end_time = $request->get('end_time')[$key];
                        $schedule->save();
                    }
                    
                }
            }
            return response()->json(['status' => 200, 'message' => 'Staff schedule created successfully', 'dids' => $missingIds]);
        } else {
            return response()->json(['status' => 500, 'message' => 'Server Error']);
        }

    }
}
