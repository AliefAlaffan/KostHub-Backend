<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'ktp_number', 'emergency_contact_name',
        'emergency_contact_phone', 'occupation', 'join_date',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function contracts() { return $this->hasMany(Contract::class); }
    public function activeContract() { return $this->hasOne(Contract::class)->where('status', 'active'); }
    public function maintenanceRequests() { return $this->hasMany(MaintenanceRequest::class); }
}   