<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use Carbon\Carbon;

class InvoiceGenerationService
{
    public function generateForContract(Contract $contract, string $period): ?Invoice
    {
        if (!in_array($contract->status, ['active', 'ending_soon'])) {
            return null;
        }

        $existing = Invoice::where('contract_id', $contract->id)->where('period', $period)->first();
        if ($existing) return $existing;

        $periodStart = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfMonth();
        $dueDate = $periodStart->copy()->addDays(7);

        $invoice = Invoice::create([
            'contract_id' => $contract->id,
            'period' => $period,
            'total_amount' => 0,
            'due_date' => $dueDate,
            'status' => 'unpaid',
        ]);

        $startDate = Carbon::parse($contract->start_date);
        $isFirstMonth = $startDate->isSameMonth($periodStart) && $startDate->isSameYear($periodStart);

        if ($isFirstMonth && $startDate->day > 1) {
            // Prorata: kontrak mulai di tengah bulan
            $totalDaysInMonth = $periodStart->daysInMonth;
            $remainingDays = $totalDaysInMonth - $startDate->day + 1;
            $prorataAmount = round(($contract->rent_amount / $totalDaysInMonth) * $remainingDays, 2);

            $invoice->items()->create([
                'category' => 'rent',
                'description' => "Sewa prorata ({$startDate->format('d')}–{$periodStart->endOfMonth()->format('d')} {$periodStart->translatedFormat('F Y')})",
                'amount' => $prorataAmount,
            ]);
        } else {
            $invoice->items()->create([
                'category' => 'rent',
                'description' => 'Sewa kamar '.$periodStart->translatedFormat('F Y'),
                'amount' => $contract->rent_amount,
            ]);
        }

        $this->recalculateTotal($invoice);

        return $invoice;
    }

    public function recalculateTotal(Invoice $invoice): void
    {
        $invoice->update(['total_amount' => $invoice->items()->sum('amount')]);
    }
}
