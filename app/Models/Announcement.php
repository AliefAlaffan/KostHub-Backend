<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['property_id', 'created_by', 'title', 'content', 'target'];

    public function property() { return $this->belongsTo(Property::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}