<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = ['contract_id', 'period', 'period_start', 'period_end', 'total_amount', 'due_date', 'status'];

    protected function casts(): array
{
    return [
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
    ];
}

    public function contract() { return $this->belongsTo(Contract::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    
    public function payments() { return $this->hasMany(Payment::class); }

    public function verifiedTotal(): float
    {
        return (float) $this->payments()->where('status', 'verified')->sum('amount');
    }
}