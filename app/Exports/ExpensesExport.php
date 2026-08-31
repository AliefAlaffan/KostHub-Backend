<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpensesExport implements FromCollection, WithHeadings, WithMapping
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
        $query = Expense::whereIn('property_id', $this->propertyIds);

        if ($this->from) {
            $query->whereDate('expense_date', '>=', $this->from);
        }
        if ($this->to) {
            $query->whereDate('expense_date', '<=', $this->to);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return ['Kategori', 'Jumlah', 'Tanggal', 'Deskripsi'];
    }

    public function map($expense): array
    {
        return [
            $expense->category,
            $expense->amount,
            $expense->expense_date->format('Y-m-d'),
            $expense->description,
        ];
    }
}