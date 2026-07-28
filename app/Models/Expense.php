<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['property_id', 'category', 'amount', 'expense_date', 'description'];

    public function property() { return $this->belongsTo(Property::class); }
}