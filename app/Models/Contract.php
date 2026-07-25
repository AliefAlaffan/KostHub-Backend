<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'room_id', 'created_by', 'start_date', 'end_date',
        'rent_amount', 'deposit_amount', 'billing_cycle', 'status',
        'renewed_from_contract_id',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function room() { return $this->belongsTo(Room::class); }
}