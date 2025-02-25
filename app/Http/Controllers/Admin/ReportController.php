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
                $staffName = Staff::find($staffId)->name ?? 'Unknown';
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
    
}
