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
        Contract::whereIn('status', ['active', 'ending_soon'])
            ->chunkById(100, function ($contracts) use ($service) {
                foreach ($contracts as $contract) {
                    $invoice = $service->generateNextCycleIfDue($contract);

                    if ($invoice) {
                        $invoice->contract->tenant->user->notify(
                            new \App\Notifications\InvoiceCreated($invoice)
                        );
                    }
                }
            });
    }
}