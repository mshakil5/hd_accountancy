<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Carbon;
use App\Models\WorkTime;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
      return view('admin.reports.index');
    }

    public function createReport()
    {
      $clients = Client::where('status', 1)->select('id', 'name')->latest()->get();
      return view('admin.reports.create', compact('clients'));
    }

    public function generateReport(Request $request)
    {
        $reportName = $request->report_name;
        $baseName = $request->base_name;
        $dateRange = explode(' - ', $request->date_range);
        $startDate = Carbon::createFromFormat('d/m/Y', trim($dateRange[0]))->startOfDay();
        $endDate = Carbon::createFromFormat('d/m/Y', trim($dateRange[1]))->endOfDay();
        $comparePeriods = (int) $request->compare_with;
    
        $monthsDiff = $startDate->diffInMonths($endDate);
    
        $periods = [];
        for ($i = 0; $i <= $comparePeriods; $i++) { 
            $pastStart = $startDate->copy()->subMonths($i * $monthsDiff);
            $pastEnd = $endDate->copy()->subMonths($i * $monthsDiff);
            $periods[] = $pastStart->format('M Y') . ' - ' . $pastEnd->format('M Y');
        }
    
        $query = WorkTime::query()
            ->select(
                'client_sub_services.client_id',
                'clients.name as client_name',
                'clients.refid as client_ref',
                DB::raw('SUM(duration) as total_duration'),
                DB::raw("DATE_FORMAT(work_times.created_at, '%Y-%m') as period")
            )
            ->join('client_sub_services', 'work_times.client_sub_service_id', '=', 'client_sub_services.id')
            ->join('clients', 'client_sub_services.client_id', '=', 'clients.id')
            ->whereBetween('work_times.created_at', [$startDate, $endDate])
            ->groupBy('client_sub_services.client_id', 'clients.name', 'clients.refid', 'period');
    
        if ($baseName !== 'All') {
            $query->where('client_sub_services.client_id', $baseName);
        }
    
        $data = $query->get();
    
        $reportData = [];
        
        foreach ($data as $item) {
            $clientId = $item->client_id;
            $formattedPeriod = Carbon::createFromFormat('Y-m', $item->period)->format('M Y');
            $hoursWorked = round($item->total_duration / 3600, 2);
    
            if (!isset($reportData[$clientId])) {
                $reportData[$clientId] = [
                    'client_ref' => $item->client_ref,
                    'client_name' => $item->client_name,
                    'hours' => array_fill_keys($periods, "0 hr"),
                ];
            }
    
            foreach ($periods as $periodRange) {
                [$rangeStart, $rangeEnd] = explode(' - ', $periodRange);
                $rangeStart = Carbon::createFromFormat('M Y', $rangeStart);
                $rangeEnd = Carbon::createFromFormat('M Y', $rangeEnd);
    
                if (Carbon::createFromFormat('M Y', $formattedPeriod)->between($rangeStart, $rangeEnd)) {
                    $reportData[$clientId]['hours'][$periodRange] = "{$hoursWorked} hr";
                    break;
                }
            }
        }
    
        return response()->json([
            'report_name' => $reportName,
            'periods' => $periods,
            'report_data' => array_values($reportData),
        ]);
    }
}
