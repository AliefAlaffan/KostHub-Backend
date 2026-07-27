<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id', 'amount', 'method', 'proof_image', 'payment_date',
        'status', 'verified_by', 'verified_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'verified_at' => 'datetime'];
    }

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
}