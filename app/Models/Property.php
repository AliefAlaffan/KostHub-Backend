<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'admin_id', 'name', 'address', 'city', 'type', 'description',
        'facilities', 'bank_account', 'status',
    ];

    protected function casts(): array
    {
        return ['facilities' => 'array'];
    }

    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
    public function staff() { return $this->belongsToMany(User::class, 'property_staff', 'property_id', 'user_id'); }
    public function roomTypes() { return $this->hasMany(RoomType::class); }
    public function rooms() { return $this->hasMany(Room::class); }
}