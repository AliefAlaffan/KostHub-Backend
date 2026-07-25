<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'property_id', 'room_type_id', 'room_number', 'floor',
        'price', 'size_m2', 'status', 'description',
    ];

    public function property() { return $this->belongsTo(Property::class); }
    public function roomType() { return $this->belongsTo(RoomType::class); }
}