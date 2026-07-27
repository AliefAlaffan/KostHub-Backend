<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarkOverdueInvoicesAndApplyFines implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Invoice::whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->chunkById(100, function ($invoices) {
                foreach ($invoices as $invoice) {
                    // Denda hanya ditambahkan SEKALI - cek dulu apakah sudah ada
                    $fineAlreadyApplied = $invoice->items()->where('category', 'fine')->exists();

                    if (!$fineAlreadyApplied) {
                        $percentage = 2; // 2% dari total tagihan
                        $fineAmount = round($invoice->total_amount * ($percentage / 100), 2);

                        $invoice->items()->create([
                            'category' => 'fine',
                            'description' => "Denda keterlambatan ({$percentage}%)",
                            'amount' => $fineAmount,
                        ]);

                        $invoice->update(['total_amount' => $invoice->items()->sum('amount')]);
                    }

                    $invoice->update(['status' => 'overdue']);
                }
            });
    }
}