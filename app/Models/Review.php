<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['property_id', 'tenant_id', 'rating', 'comment', 'owner_reply'];

    public function property() { return $this->belongsTo(Property::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
}