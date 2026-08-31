<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OutstandingInvoicesExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $propertyIds;

    public function __construct(array $propertyIds)
    {
        $this->propertyIds = $propertyIds;
    }

    public function collection()
    {
        return Invoice::whereHas('contract.room', fn ($q) => $q->whereIn('property_id', $this->propertyIds))
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->with('contract.tenant.user', 'contract.room')
            ->get();
    }

    public function headings(): array
    {
        return ['Penghuni', 'Kamar', 'Periode', 'Total', 'Jatuh Tempo', 'Status'];
    }

    public function map($invoice): array
    {
        return [
            $invoice->contract->tenant->user->name ?? '-',
            $invoice->contract->room->room_number ?? '-',
            $invoice->period,
            $invoice->total_amount,
            $invoice->due_date->format('Y-m-d'),
            $invoice->status,
        ];
    }
}