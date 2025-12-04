<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClientService;
use Illuminate\Support\Carbon;
use App\Models\ClientSubService;
use DateTime;

class CheckAndCreateServiceJobs extends Command
{
    protected $signature = 'services:check-and-create';

    protected $description = 'Check for services with continuous flag set to 1 and create new jobs based on the next deadlines';


    public function handle()
    {
        $currentDate = Carbon::now();
    
        $clientServices = ClientService::where('continuous', 1)
            ->with('clientSubServices')
            ->whereNotNull('next_due_date')
            ->whereNotNull('client_id')
            ->where('type', 1)
            ->where('is_next_date_added', 0)
            ->whereRaw("STR_TO_DATE(next_due_date, '%d-%m-%Y') <= STR_TO_DATE(?, '%d-%m-%Y')", [date('d-m-Y')])
            ->orderBy('id', 'desc')
            ->get();

        foreach ($clientServices as $clientService) {
            $frequency = $clientService->service_frequency;
            $nextDueDate = Carbon::createFromFormat('d-m-Y', $clientService->next_due_date);

            $next = DateTime::createFromFormat('d-m-Y', $clientService->next_due_date);
            $today = new DateTime();
              
            if ($next < $today) {
                $isRelevant = true;
            }else {
            if ($frequency == 'Monthly') {
                $startOfNextMonth = $currentDate->copy()->addMonthNoOverflow()->startOfMonth();
                $endOfNextMonth = $currentDate->copy()->addMonthNoOverflow()->endOfMonth();
                $isRelevant = $nextDueDate->between($startOfNextMonth, $endOfNextMonth);
            } elseif ($frequency == 'Weekly') {
                $startOfNextWeek = $currentDate->copy()->startOfWeek();
                $endOfNextWeek = $currentDate->copy()->endOfWeek();
                $isRelevant = $nextDueDate->between($startOfNextWeek, $endOfNextWeek);
            } elseif ($frequency == '2 Weekly') {
                $startOfNextTwoWeeks = $currentDate->copy()->addWeeks(1)->startOfWeek();
                $endOfNextTwoWeeks = $currentDate->copy()->addWeeks(1)->endOfWeek();
                $isRelevant = $nextDueDate->between($startOfNextTwoWeeks, $endOfNextTwoWeeks);
            } elseif ($frequency == '4 Weekly') {
                $startOfNextFourWeeks = $currentDate->copy()->addWeeks(3)->startOfWeek();
                $endOfNextFourWeeks = $currentDate->copy()->addWeeks(3)->endOfWeek();
                $isRelevant = $nextDueDate->between($startOfNextFourWeeks, $endOfNextFourWeeks);
            } elseif ($frequency == 'Quarterly') {
                $startOfNextQuarter = $currentDate->copy()->addMonths(1)->startOfMonth();
                $endOfNextQuarter = $currentDate->copy()->addMonthsNoOverflow(3)->endOfMonth();
                $isRelevant = $nextDueDate->between($startOfNextQuarter, $endOfNextQuarter);
            } elseif ($frequency == 'Annually') {
                $startOfNextYear = $currentDate->copy()->addYearsNoOverflow()->startOfYear();
                $endOfNextYear = $currentDate->copy()->addYearsNoOverflow()->endOfYear();
                $isRelevant = $nextDueDate->between($startOfNextYear, $endOfNextYear);
            }
            }

            $clientService->next_due_date = Carbon::parse($clientService->next_due_date)->format('d-m-Y');
            $clientService->next_service_deadline = Carbon::parse($clientService->next_service_deadline)->format('d-m-Y');
            $clientService->next_legal_deadline = Carbon::parse($clientService->next_legal_deadline)->format('d-m-Y');

            if ($isRelevant) {
                $newClientService = $clientService->replicate();
                if ($frequency == 'Weekly') {
                    $newClientService->due_date =  $clientService->next_due_date;
                    $newClientService->service_deadline =  $clientService->next_service_deadline;
                    $newClientService->legal_deadline =  $clientService->next_legal_deadline;
                    $newClientService->next_due_date = Carbon::parse($clientService->next_due_date)->addWeek()->format('d-m-Y');
                    $newClientService->next_service_deadline = Carbon::parse($clientService->next_service_deadline)->addWeek()->format('d-m-Y');
                    $newClientService->next_legal_deadline = Carbon::parse($clientService->next_legal_deadline)->addWeek()->format('d-m-Y');
                } elseif ($frequency == '2 Weekly') {
                    $newClientService->due_date =  $clientService->next_due_date;
                    $newClientService->service_deadline =  $clientService->next_service_deadline;
                    $newClientService->legal_deadline =  $clientService->next_legal_deadline;
                    $newClientService->next_due_date = Carbon::parse($clientService->next_due_date)->addWeeks(2)->format('d-m-Y');
                    $newClientService->next_service_deadline = Carbon::parse($clientService->next_service_deadline)->addWeeks(2)->format('d-m-Y');
                    $newClientService->next_legal_deadline = Carbon::parse($clientService->next_legal_deadline)->addWeeks(2)->format('d-m-Y');
                } elseif ($frequency == '4 Weekly') {
                    $newClientService->due_date =  $clientService->next_due_date;
                    $newClientService->service_deadline =  $clientService->next_service_deadline;
                    $newClientService->legal_deadline =  $clientService->next_legal_deadline;
                    $newClientService->next_due_date = Carbon::parse($clientService->next_due_date)->addWeeks(4)->format('d-m-Y');
                    $newClientService->next_service_deadline = Carbon::parse($clientService->next_service_deadline)->addWeeks(4)->format('d-m-Y');
                    $newClientService->next_legal_deadline = Carbon::parse($clientService->next_legal_deadline)->addWeeks(4)->format('d-m-Y');
                } elseif ($frequency == 'Monthly') {
                    $newClientService->due_date =  $clientService->next_due_date;
                    $newClientService->service_deadline =  $clientService->next_service_deadline;
                    $newClientService->legal_deadline =  $clientService->next_legal_deadline;
                    $newClientService->next_due_date = Carbon::parse($clientService->next_due_date)->addMonthNoOverflow()->format('d-m-Y');
                    $newClientService->next_service_deadline = Carbon::parse($clientService->next_service_deadline)->addMonthNoOverflow()->format('d-m-Y');
                    $newClientService->next_legal_deadline = Carbon::parse($clientService->next_legal_deadline)->addMonthNoOverflow()->format('d-m-Y');
                } elseif ($frequency == 'Quarterly') {
                    $newClientService->due_date =  $clientService->next_due_date;
                    $newClientService->service_deadline =  $clientService->next_service_deadline;
                    $newClientService->legal_deadline =  $clientService->next_legal_deadline;
                    $newClientService->next_due_date = Carbon::parse($clientService->next_due_date)->addMonthsNoOverflow(3)->format('d-m-Y');
                    $newClientService->next_service_deadline = Carbon::parse($clientService->next_service_deadline)->addMonthsNoOverflow(3)->format('d-m-Y');
                    $newClientService->next_legal_deadline = Carbon::parse($clientService->next_legal_deadline)->addMonthsNoOverflow(3)->format('d-m-Y');
                } elseif ($frequency == 'Annually') {
                    $newClientService->due_date =  $clientService->next_due_date;
                    $newClientService->service_deadline =  $clientService->next_service_deadline;
                    $newClientService->legal_deadline =  $clientService->next_legal_deadline;
                    $newClientService->next_due_date = Carbon::parse($clientService->next_due_date)->addYearsNoOverflow()->format('d-m-Y');
                    $newClientService->next_service_deadline = Carbon::parse($clientService->next_service_deadline)->addYearsNoOverflow()->format('d-m-Y');
                    $newClientService->next_legal_deadline = Carbon::parse($clientService->next_legal_deadline)->addYearsNoOverflow()->format('d-m-Y');
                }
                $newClientService->unique_id = date("His") . '-' . $clientService->client_id;
                $newClientService->task_counter = $clientService->task_counter + 1;
                $newClientService->save();

                $clientService->is_next_date_added = 1;
                $clientService->save();

                if ($clientService->clientSubServices) {
                    foreach ($clientService->clientSubServices as $subService) {
                        $newSubService = $subService->replicate();
                        $newSubService->client_service_id = $newClientService->id;
                
                        $frequency = $clientService->service_frequency;
                        $nextDeadline = Carbon::parse($subService->deadline);
                
                        if ($frequency == 'Weekly') {
                            $newSubService->deadline = $nextDeadline->addWeek()->format('d-m-Y');
                        } elseif ($frequency == '2 Weekly') {
                            $newSubService->deadline = $nextDeadline->addWeeks(2)->format('d-m-Y');
                        } elseif ($frequency == '4 Weekly') {
                            $newSubService->deadline = $nextDeadline->addWeeks(4)->format('d-m-Y');
                        } elseif ($frequency == 'Monthly') {
                            $newSubService->deadline = $nextDeadline->addMonthNoOverflow()->format('d-m-Y');
                        } elseif ($frequency == 'Quarterly') {
                            $newSubService->deadline = $nextDeadline->addMonthsNoOverflow(3)->format('d-m-Y');
                        } elseif ($frequency == 'Annually') {
                            $newSubService->deadline = $nextDeadline->addYearsNoOverflow()->format('d-m-Y');
                        }
                
                        $newSubService->save();
                    }
                }

                $this->info("Created new service job for ClientService ID: {$clientService->id}");

            }
        
        }

        $this->info("Service job creation process completed!");
        
    }
    
}