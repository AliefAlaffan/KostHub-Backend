<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $fillable = ['property_id', 'name', 'description', 'base_price', 'capacity'];

    public function property() { return $this->belongsTo(Property::class); }
    public function rooms() { return $this->hasMany(Room::class); }
}