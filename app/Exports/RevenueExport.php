<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RevenueExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $propertyIds;
    protected ?string $from;
    protected ?string $to;

    public function __construct(array $propertyIds, ?string $from = null, ?string $to = null)
    {
        $this->propertyIds = $propertyIds;
        $this->from = $from;
        $this->to = $to;
    }

    public function collection()
    {
        $query = Invoice::whereHas('contract.room', fn ($q) => $q->whereIn('property_id', $this->propertyIds))
            ->with('contract.tenant.user', 'contract.room')
            ->where('status', 'paid');

        if ($this->from) {
            $query->whereDate('due_date', '>=', $this->from);
        }
        if ($this->to) {
            $query->whereDate('due_date', '<=', $this->to);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return ['Periode', 'Penghuni', 'Kamar', 'Total', 'Jatuh Tempo', 'Status'];
    }

    public function map($invoice): array
    {
        return [
            $invoice->period,
            $invoice->contract->tenant->user->name ?? '-',
            $invoice->contract->room->room_number ?? '-',
            $invoice->total_amount,
            $invoice->due_date->format('Y-m-d'),
            $invoice->status,
        ];
    }
}