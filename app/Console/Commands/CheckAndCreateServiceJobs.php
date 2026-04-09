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
        $currentDate = Carbon::now()->startOfDay();

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
            try {
                $frequency = $clientService->service_frequency;
                $nextDueDate = Carbon::createFromFormat('d-m-Y', $clientService->next_due_date)->startOfDay();
                
                $isRelevant = false;

                if ($nextDueDate->lessThanOrEqualTo($currentDate)) {
                    $isRelevant = true;
                } else {
                    $horizonDate = match ($frequency) {
                        'Weekly'    => $currentDate->copy()->addWeek(),
                        '2 Weekly'  => $currentDate->copy()->addWeeks(2),
                        '4 Weekly'  => $currentDate->copy()->addWeeks(4),
                        'Monthly'   => $currentDate->copy()->addMonthNoOverflow(),
                        'Quarterly' => $currentDate->copy()->addMonthsNoOverflow(3),
                        'Annually'  => $currentDate->copy()->addYearNoOverflow(),
                        default     => $currentDate,
                    };
                    $isRelevant = $nextDueDate->lessThanOrEqualTo($horizonDate);
                }

                if ($isRelevant) {
                    $newClientService = $clientService->replicate();
                    
                    $newClientService->due_date = $clientService->next_due_date;
                    $newClientService->service_deadline = $clientService->next_service_deadline;
                    $newClientService->legal_deadline = $clientService->next_legal_deadline;

                    $newClientService->next_due_date = $this->getNextDate($clientService->next_due_date, $frequency);
                    $newClientService->next_service_deadline = $this->getNextDate($clientService->next_service_deadline, $frequency);
                    $newClientService->next_legal_deadline = $this->getNextDate($clientService->next_legal_deadline, $frequency);

                    $newClientService->unique_id = date("His") . '-' . $clientService->client_id;
                    $newClientService->task_counter = ($clientService->task_counter ?? 0) + 1;
                    $newClientService->save();

                    $clientService->is_next_date_added = 1;
                    $clientService->save();

                    if ($clientService->clientSubServices) {
                        foreach ($clientService->clientSubServices as $subService) {
                            $newSubService = $subService->replicate();
                            $newSubService->client_service_id = $newClientService->id;
                            $newSubService->deadline = $this->getNextDate($subService->deadline, $frequency);
                            $newSubService->save();
                        }
                    }

                    $this->info("Created new service job for ClientService ID: {$clientService->id}");
                }
            } catch (\Exception $e) {
                $this->error("Error processing ID {$clientService->id}: " . $e->getMessage());
            }
        }

        $this->info("Service job creation process completed!");
    }



    private function getNextDate($dateString, $frequency)
    {
        if (!$dateString) return null;

        $date = Carbon::parse($dateString);

        $nextDate = match ($frequency) {
            'Weekly'    => $date->copy()->addWeek(),
            '2 Weekly'  => $date->copy()->addWeeks(2),
            '4 Weekly'  => $date->copy()->addWeeks(4),
            'Monthly'   => $date->copy()->addMonthNoOverflow(),
            'Quarterly' => $date->copy()->addMonthsNoOverflow(3),
            'Annually'  => $date->copy()->addYearNoOverflow(),
            default     => $date->copy(),
        };

        return $nextDate->format('d-m-Y');
    }


}