<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Models\WorkTime;
use Illuminate\Support\Facades\DB;
use App\Models\ClientSubService;

class ReportController extends Controller
{
    public function createReport()
    {
      $employees = User::where('status', 1)->select('id', 'first_name', 'last_name', 'id_number')->latest()->get();
      $clients = Client::where('status', 1)->select('id', 'name', 'refid')->latest()->get();
      return view('admin.reports.create', compact('clients', 'employees'));
    }

    public function generateReport(Request $request)
    {
          $request->validate([
            'report_name' => 'required|string',
            'date_range' => 'required|string',
            'compare_with' => 'nullable|integer',
            'report_base' => 'required|string',
            'base_name' => 'nullable|string',
        ]);

        $reportName = $request->report_name; 
        $startDate = Carbon::createFromFormat('d/m/Y', explode(' - ', $request->date_range)[0]);
        $endDate = Carbon::createFromFormat('d/m/Y', explode(' - ', $request->date_range)[1]);
        $compareWith = (int) $request->compare_with;
        $reportBase = $request->report_base; 
        $baseName = $request->base_name;
    
        $periods = $this->calculatePeriods($startDate, $endDate, $compareWith);
        $workTimes = [];
    
        if ($reportBase === 'employee') {
            $workTimes = $this->fetchWorkTimes($periods, $baseName, $reportName);
        }
    
        return response()->json($workTimes);

    }

    private function calculatePeriods(Carbon $startDate, Carbon $endDate, $periods)
    {
        $difference = $endDate->diffInDays($startDate);
        $results = [];

        $results[] = ['start' => $startDate->format('d/m/Y'), 'end' => $endDate->format('d/m/Y')];

        for ($i = 1; $i < $periods; $i++) {
            if ($difference === 0) {
                $startDate->subDay();
                $endDate->subDay();
            } elseif ($difference < 28) {
                $startDate->subDays($difference + 1);
                $endDate->subDays($difference + 1);
            } elseif ($difference >= 28 && $difference < 365) {
                $startDate->subMonth();
                $endDate->subMonth();
            } else {
                $startDate->subYear();
                $endDate->subYear();
            }

            $results[] = ['start' => $startDate->format('d/m/Y'), 'end' => $endDate->format('d/m/Y')];
        }

        return $results;
    }

    private function fetchWorkTimes(array $periods, $staffId = null, $reportName)
    {
        $workTimes = [];
        $formattedPeriods = [];
    
        foreach ($periods as $period) {
            $start = Carbon::createFromFormat('d/m/Y', $period['start'])->startOfDay();
            $end = Carbon::createFromFormat('d/m/Y', $period['end'])->endOfDay();
            
            $formattedPeriod = $start->format('d F Y') . ' to ' . $end->format('d F Y');
            $formattedPeriods[] = $formattedPeriod;
    
            $query = WorkTime::whereBetween('created_at', [$start, $end])
                             ->whereNotNull('staff_id')
                             ->whereNotNull('client_sub_service_id');
    
            if ($staffId && $staffId !== 'All') {
                $query->where('staff_id', $staffId);
                $staffName = optional(User::find($staffId))->first_name . ' ' . optional(User::find($staffId))->last_name ?? '';
            } else {
                $staffName = 'All Employees';
            }
    
            $records = $query->get()->map(function ($workTime) {
                $clientSubService = ClientSubService::whereNotNull('client_id')
                                                    ->where('id', $workTime->client_sub_service_id)
                                                    ->first();
    
                if (!$clientSubService || !$clientSubService->client_id) {
                    return null;
                }
    
                $client = Client::where('id', $clientSubService->client_id)->first();
    
                return [
                    'client_id' => $clientSubService->client_id,
                    'client_name' => $client->name ?? '',
                    'refid' => $client->refid ?? '',
                    'duration' => (int) $workTime->duration,
                ];
            })->filter();
    
            $groupedRecords = $records->groupBy('client_id')->map(function ($group, $clientId) {
                return [
                    'client_id' => $clientId,
                    'client_name' => $group->first()['client_name'],
                    'refid' => $group->first()['refid'],
                    'total_duration' => $group->sum('duration'),
                ];
            });
    
            $workTimes[] = [
                'period' => $formattedPeriod,
                'records' => $groupedRecords->values(),
            ];
        }
    
        return [
          'report_name' => $reportName,
          'report_base' => $staffName,
          'date_range' => $formattedPeriods[0] ?? '',
          'work_times' => $workTimes,
      ];
    } 
     
    public function fetchWorkTimeDetails(Request $request)
    {
        $clientId = $request->client_id;
        $dateRange = $request->period;
        $dates = explode(' to ', $dateRange);
        $startDate = Carbon::parse($dates[0])->format('Y-m-d');
        $endDate = Carbon::parse($dates[1])->format('Y-m-d');
    
        $clientSubServiceIds = ClientSubService::where('client_id', $clientId)->pluck('id');
    
        $workTimes = WorkTime::whereNotNull('client_sub_service_id')
            ->whereNotNull('staff_id')
            ->whereIn('client_sub_service_id', $clientSubServiceIds)
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->get(['created_at', 'duration', 'client_sub_service_id', 'type']);
    
        if ($workTimes->isEmpty()) {
            return response()->json(['details' => []]);
        }
    
        $groupedWorkTimes = $workTimes->groupBy(function ($workTime) {
            return Carbon::parse($workTime->created_at)->format('d F Y') . '-' . optional($workTime->clientSubService->subService)->id . '-' . $workTime->type;
        });
    
        $responseDetails = $groupedWorkTimes->map(function ($group) {
            $totalDuration = $group->sum('duration');
            $firstRecord = $group->first();
            $date = Carbon::parse($firstRecord->created_at)->format('d F Y');
            $subServiceName = optional($firstRecord->clientSubService->subService)->name ?? '';
            $type = $firstRecord->type;
    
            return [
                'date' => $date,
                'duration' => $totalDuration,
                'service_name' => $subServiceName,
                'type' => $type,
            ];
        });
    
        $response = [
            'client_name' => optional($workTimes->first()->clientSubService->client)->name ?? '',
            'details' => $responseDetails->values()->toArray(),
        ];
    
        return response()->json($response);
    }
    
    
}
