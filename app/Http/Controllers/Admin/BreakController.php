<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BreakController extends Controller
{
    public function takeBreak(Request $request)
    {
        $chkProcessingWork = WorkTime::whereNull('end_time')
            ->where('staff_id', Auth::user()->id)
            ->where('is_break', 0)
            ->orderBy('id', 'DESC')
            ->first();

        $workTime = new WorkTime();
        $workTime->staff_id = Auth::id();
        $workTime->start_time = Carbon::now();
        $workTime->start_date = Carbon::today()->format('d-m-Y');
        $workTime->is_break = 1;
        $workTime->created_by = Auth::id();

        if (isset($chkProcessingWork)) {
            $workTime->client_sub_service_id = $chkProcessingWork->client_sub_service_id;
        }

        $workTime->save();
        
        return response()->json([
            'message' => 'Break started successfully', 
            'workTimeId' => $workTime->id
        ], 200);
    }

    public function checkBreakStatus(Request $request)
    {
        $workTimeId = $request->input('workTimeId');
        
        if ($workTimeId) {
            $workTime = WorkTime::find($workTimeId);
            if ($workTime && $workTime->is_break == 1 && is_null($workTime->end_time)) {
                return response()->json(['isBreak' => true]);
            }
        }

        // Also check if there's any active break for the user
        $activeBreak = WorkTime::where('staff_id', Auth::id())
            ->where('is_break', 1)
            ->whereNull('end_time')
            ->first();

        if ($activeBreak) {
            return response()->json(['isBreak' => true, 'workTimeId' => $activeBreak->id]);
        }

        return response()->json(['isBreak' => false]);
    }

    public function breakOut(Request $request)
    {
        $workTimeId = $request->input('workTimeId');
        
        if (!$workTimeId) {
            // If no workTimeId provided, find the active break
            $workTime = WorkTime::where('staff_id', Auth::id())
                ->where('is_break', 1)
                ->whereNull('end_time')
                ->first();
        } else {
            $workTime = WorkTime::find($workTimeId);
        }
        
        if ($workTime) {
            $startTime = $workTime->start_time;
            $endTime = Carbon::now();
            $duration = $endTime->diffInSeconds($startTime);
            $workTime->end_time = $endTime;
            $workTime->duration = $duration;
            $workTime->save();
            
            return response()->json([
                'success' => true, 
                'message' => 'Break ended successfully'
            ]);
        } else {
            return response()->json([
                'success' => false, 
                'message' => 'Break session not found'
            ], 404);
        }
    }
}