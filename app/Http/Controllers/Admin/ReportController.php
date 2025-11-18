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
            $workTimes = $this->fetchWorkTimes($periods, $baseName, $reportName, $reportBase);
        }  elseif ($reportBase === 'client') {  
            $workTimes = $this->fetchClientWorkTimes($periods, $baseName, $reportName, $reportBase);
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

    private function fetchClientWorkTimes(array $periods, $clientId = null, $reportName, $reportBase)
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
                            ->whereNotNull('client_sub_service_id')
                            ->whereHas('clientSubService', function ($query) {
                                $query->whereNotNull('client_id');
                            })
                            ->where('is_break', 0);

            if ($clientId && $clientId !== 'All') {
                $query->whereHas('clientSubService', function ($q) use ($clientId) {
                    $q->where('client_id', $clientId);
                });
                $clientName = optional(Client::find($clientId))->name ?? 'Unknown Client';
            } else {
                $clientName = 'All Clients';
            }

            $records = $query->get()->map(function ($workTime) {
                $staff = User::find($workTime->staff_id);

                return [
                    'staff_id' => $workTime->staff_id,
                    'staff_id_number' => $staff ? ($staff->id_number) : '',
                    'staff_name' => $staff ? ($staff->first_name . ' ' . $staff->last_name) : 'Unknown Staff',
                    'duration' => (int) $workTime->duration,
                ];
            });

            $groupedRecords = $records->groupBy('staff_id')->map(function ($group, $staffId) {
                return [
                    'staff_id' => $staffId,
                    'staff_id_number' => $group->first()['staff_id_number'],
                    'staff_name' => $group->first()['staff_name'],
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
            'report_base' => $reportBase,
            'report_base_name' => $clientName,
            'date_range' => $formattedPeriods[0] ?? '',
            'work_times' => $workTimes,
        ];
    }

    private function fetchWorkTimes(array $periods, $staffId = null, $reportName, $reportBase)
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
                $staff = User::find($staffId);
                $staffName = $staff ? ($staff->first_name . ' ' . $staff->last_name) : 'Unknown Staff';
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
            'report_base' => $reportBase,
            'report_base_name' => $staffName,
            'date_range' => $formattedPeriods[0] ?? '',
            'work_times' => $workTimes,
        ];
    }
    
    public function fetchWorkTimeDetails(Request $request)
    {
        $clientId = $request->client_id;
        $dateRange = $request->period;
        $staffId = $request->staff_id;
        $dates = explode(' to ', $dateRange);
        $startDate = Carbon::parse($dates[0])->format('d-m-Y');
        $endDate = Carbon::parse($dates[1])->format('d-m-Y');
    
        $clientSubServiceIds = ClientSubService::where('client_id', $clientId)->pluck('id');
    
        $workTimes = WorkTime::whereNotNull('client_sub_service_id')
            ->whereNotNull('staff_id')
            ->when($staffId !== 'All', function ($query) use ($staffId) {
                return $query->where('staff_id', $staffId);
            })
            ->whereIn('client_sub_service_id', $clientSubServiceIds)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->get();
    
        if ($workTimes->isEmpty()) {
            return response()->json(['details' => []]);
        }
    
        $groupedByDate = [];
    
        foreach ($workTimes as $workTime) {
            $date = Carbon::parse($workTime->created_at)->format('d F Y');
            $serviceName = optional($workTime->clientSubService->subService)->name ?? '';
            $type = $workTime->type;
    
            if (!isset($groupedByDate[$date])) {
                $groupedByDate[$date] = [
                    'date' => $date,
                    'duration' => 0,
                    'services' => [],
                    'additional' => [],
                ];
            }
    
            $groupedByDate[$date]['duration'] += $workTime->duration;
            $groupedByDate[$date]['services'][] = $serviceName;
            $groupedByDate[$date]['type'][] = $type;
        }
    
        $responseDetails = [];
        foreach ($groupedByDate as $item) {
            $responseDetails[] = [
                'date' => $item['date'],
                'duration' => $item['duration'],
                'service_name' => implode(', ', array_unique($item['services'])),
                'type' => implode(', ', array_unique($item['type'])),
            ];
        }
    
        return response()->json([
            'client_id' => $clientId,
            'staff_id' => $staffId,
            'client_name' => optional($workTimes->first()->clientSubService->client)->name ?? '',
            'details' => $responseDetails,
        ]);
    }

    public function fetchHourlyWorkTimeDetails(Request $request)
    {
        $clientId = $request->client_id;
        $staffId = $request->staff_id;
        $date = $request->date;
        $date = Carbon::parse($date)->format('d-m-Y');
    
        $workTimes = WorkTime::whereNotNull('client_sub_service_id')
            ->whereHas('clientSubService', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->whereNotNull('staff_id')
            ->when($staffId !== 'All', function ($query) use ($staffId) {
                  return $query->where('staff_id', $staffId);
              })
            ->where('is_break', 0)
            ->where('start_date', $date)
            ->with('staff', 'clientSubService.subService')
            ->get(['id', 'staff_id', 'client_sub_service_id', 'start_time', 'end_time', 'duration', 'is_break', 'type', 'start_date']);
    
        if ($workTimes->isEmpty()) {
            return response()->json(['details' => []]);
        }
    
        $groupedWorkTimes = $workTimes->groupBy(function ($workTime) {
            return $workTime->staff_id . '-' . $workTime->clientSubService->subService->id . '-' . $workTime->type;
        });
    
        $responseDetails = [];
        
        foreach ($groupedWorkTimes as $group) {
            $firstWorkTime = $group->first();
            $totalDuration = $group->sum('duration');
    
            $staff = $firstWorkTime->staff;
    
            $responseDetails[] = [
                'staff_id' => optional($staff)->id_number ?? '',
                'staff_name' => optional($staff)->first_name . ' ' . optional($staff)->last_name,
                'duration' => $totalDuration,
                'type' => $firstWorkTime->type,
                'service_name' => optional($firstWorkTime->clientSubService->subService)->name ?? '',
                'service_note' => optional($firstWorkTime->clientSubService)->note ?? '',
            ];
        }
    
        return response()->json(['details' => $responseDetails]);
    }

    public function fetchClientWorkTimeDetails(Request $request)
    {
        $staffId = $request->staff_id;
        $clientId = $request->client_id;
        $dateRange = $request->date;
        $dates = explode(' to ', $dateRange);
        
        $startDate = Carbon::parse(trim($dates[0]))->format('d-m-Y');
        $endDate = Carbon::parse(trim($dates[1]))->format('d-m-Y');
        
        $workTimes = WorkTime::where('staff_id', $staffId)
            ->whereBetween('start_date', [$startDate, $endDate]) // This should now match your database format
            ->whereNotNull('client_sub_service_id')
            ->whereHas('clientSubService', function ($query) use ($clientId) {
                $query->whereNotNull('client_id');
                if ($clientId !== 'All') {
                    $query->where('client_id', $clientId);
                }
            })
            ->whereNotNull('staff_id')
            ->where('is_break', 0)
            ->with('staff', 'clientSubService.subService', 'clientSubService.client')
            ->get(['id', 'staff_id', 'client_sub_service_id', 'start_date', 'start_time', 'end_time', 'duration', 'is_break', 'type']);
        
        if ($workTimes->isEmpty()) {
            return response()->json(['details' => []]);
        }
        
        $responseDetails = [];

        foreach ($workTimes as $workTime) {
            $hours = number_format($workTime->duration / 3600, 2);
            
            $staff = $workTime->staff;
            $clientName = optional($workTime->clientSubService->client)->name ?? 'Unknown Client';
            $serviceName = optional($workTime->clientSubService->subService)->name ?? '';
            
            $serviceNameOutput = '';
            $additionalWork = '';

            if ($workTime->type == 2) {
                $additionalWork = $serviceName;
                if ($workTime->clientSubService && $workTime->clientSubService->note) {
                    $additionalWork .= ' (' . $workTime->clientSubService->note . ')';
                }
            } else {
                $serviceNameOutput = $serviceName;
            }

            $responseDetails[] = [
                'start_date' => Carbon::parse($workTime->start_date)->format('j F Y'),
                'ref_id' => $workTime->staff_id,
                'client_name' => $clientName,
                'duration' => $hours,
                'service_name' => $serviceNameOutput,
                'additionalWork' => $additionalWork,
            ];
        }
        
        return response()->json(['details' => $responseDetails]);
    }

    public function clientAcquisitionReport()
    {
      return view('admin.reports.client_acquisition');
    }

    public function clientFeeReport()
    {
        $clients = Client::where('status', 1)
            ->with('accountancyFee')
            ->select('id', 'name', 'refid')
            ->latest()
            ->get();
            
        return view('admin.reports.fees', compact('clients'));
    }

    public function generateFeesReport(Request $request)
    {
        $request->validate([
            'columns' => 'required|array',
            'columns.*' => 'in:client_name,annual_agreed_fees,monthly_standing_order,monthly_amount,next_review,comment,fees_discussion',
            'standing_order' => 'nullable|in:all,yes,no'
        ]);

        $query = Client::where('status', 1)
            ->with('accountancyFee')
            ->select('id', 'name', 'refid');

        if ($request->standing_order && $request->standing_order !== 'all') {
            $hasStandingOrder = $request->standing_order === 'yes';
            $query->whereHas('accountancyFee', function($q) use ($hasStandingOrder) {
                $q->where('monthly_standing_order', $hasStandingOrder);
            });
        }

        $clients = $query->get();

        $reportData = [];
        foreach ($clients as $client) {
            $row = [];
            $fee = $client->accountancyFee;

            foreach ($request->columns as $column) {
                switch ($column) {
                    case 'client_name':
                        $row['client_name'] = $client->name;
                        break;
                    case 'annual_agreed_fees':
                        $row['annual_agreed_fees'] = $fee ? '£' . number_format($fee->annual_agreed_fees, 2) : 'N/A';
                        break;
                    case 'monthly_standing_order':
                        $row['monthly_standing_order'] = $fee ? ($fee->monthly_standing_order ? 'Yes' : 'No') : 'N/A';
                        break;
                    case 'monthly_amount':
                        $row['monthly_amount'] = $fee ? '£' . number_format($fee->monthly_amount, 2) : 'N/A';
                        break;
                    case 'next_review':
                        if ($fee && $fee->next_review) {
                            try {
                                $date = Carbon::createFromFormat('Y-m-d', $fee->next_review);
                                $row['next_review'] = $date->format('d-m-Y');
                            } catch (\Exception $e) {
                                $row['next_review'] = $fee->next_review;
                            }
                        } else {
                            $row['next_review'] = 'N/A';
                        }
                        break;
                    case 'comment':
                        $row['comment'] = $fee ? $fee->comment : 'N/A';
                        break;
                    case 'fees_discussion':
                        $row['fees_discussion'] = $fee ? $fee->fees_discussion : 'N/A';
                        break;
                }
            }
            $reportData[] = $row;
        }

        $totalClients = $clients->count();
        $withStandingOrder = $clients->filter(fn($c) => $c->accountancyFee && $c->accountancyFee->monthly_standing_order)->count();
        $withoutStandingOrder = $totalClients - $withStandingOrder;

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'columns' => $request->columns,
            'stats' => [
                'total_clients' => $totalClients,
                'with_standing_order' => $withStandingOrder,
                'without_standing_order' => $withoutStandingOrder,
            ]
        ]);
    }
    
}
