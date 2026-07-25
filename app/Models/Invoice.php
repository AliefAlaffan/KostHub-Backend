<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = ['contract_id', 'period', 'total_amount', 'due_date', 'status'];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public function contract() { return $this->belongsTo(Contract::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
}