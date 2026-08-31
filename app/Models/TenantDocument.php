<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantDocument extends Model
{
    protected $fillable = ['tenant_id', 'doc_type', 'file_path', 'verified'];

    protected function casts(): array
    {
        return ['verified' => 'boolean'];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}