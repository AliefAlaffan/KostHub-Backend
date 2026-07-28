<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    protected $fillable = [
        'tenant_id', 'room_id', 'category', 'priority', 'description',
        'status', 'assigned_to', 'repair_cost',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function room() { return $this->belongsTo(Room::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
}