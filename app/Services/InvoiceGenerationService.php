<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use Carbon\Carbon;

class InvoiceGenerationService
{
    /**
     * Generate invoice untuk siklus BERIKUTNYA milik kontrak ini, kalau memang sudah waktunya.
     * Rolling billing: siklus dihitung dari tanggal mulai kontrak (bukan kalender bulanan),
     * jadi TIDAK PERNAH ada prorata - setiap invoice selalu 1 bulan penuh rent_amount.
     */
    public function generateNextCycleIfDue(Contract $contract): ?Invoice
    {
        if (!in_array($contract->status, ['active', 'ending_soon'])) {
            return null;
        }

        $lastInvoice = Invoice::where('contract_id', $contract->id)
            ->orderByDesc('period_start')
            ->first();

        $cycleStart = $lastInvoice
            ? Carbon::parse($lastInvoice->period_end)->addDay()
            : Carbon::parse($contract->start_date);

        // Belum waktunya generate siklus berikutnya
        if ($cycleStart->isFuture()) {
            return null;
        }

        // Jangan generate melewati tanggal selesai kontrak
        if ($cycleStart->gt(Carbon::parse($contract->end_date))) {
            return null;
        }

        $cycleEnd = $cycleStart->copy()->addMonthNoOverflow()->subDay();
        $dueDate = $cycleStart->copy()->addDays((int) env('INVOICE_DUE_DAYS', 7));

        $periodLabel = $cycleStart->format('d M').' - '.$cycleEnd->format('d M Y');

        $invoice = Invoice::create([
            'contract_id' => $contract->id,
            'period' => $periodLabel,
            'period_start' => $cycleStart->toDateString(),
            'period_end' => $cycleEnd->toDateString(),
            'total_amount' => 0,
            'due_date' => $dueDate,
            'status' => 'unpaid',
        ]);

        // TIDAK PERNAH prorata lagi - selalu 1 bulan penuh karena siklus memang persis 1 bulan
        $invoice->items()->create([
            'category' => 'rent',
            'description' => "Sewa kamar ({$periodLabel})",
            'amount' => $contract->rent_amount,
        ]);

        $this->recalculateTotal($invoice);

        return $invoice;
    }

    public function recalculateTotal(Invoice $invoice): void
    {
        $invoice->update(['total_amount' => $invoice->items()->sum('amount')]);
    }
}