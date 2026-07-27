<?php

namespace App\Jobs;

use App\Models\Contract;
use App\Services\InvoiceGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMonthlyInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(InvoiceGenerationService $service): void
    {
        $period = now()->format('Y-m');

        Contract::whereIn('status', ['active', 'ending_soon'])
            ->chunkById(100, function ($contracts) use ($service, $period) {
                foreach ($contracts as $contract) {
                    $service->generateForContract($contract, $period);
                }
            });
    }
}