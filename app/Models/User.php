<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'avatar', 'status', 'created_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function propertiesOwned()
    {
        return $this->hasMany(Property::class, 'admin_id');
    }

    public function assignedProperties()
    {
        return $this->belongsToMany(Property::class, 'property_staff', 'user_id', 'property_id')
            ->withPivot('assigned_at');
    }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isStaff(): bool { return $this->role === 'staff'; }
    public function isTenant(): bool { return $this->role === 'tenant'; }

    public function accessiblePropertyIds(): array
    {
        if ($this->isAdmin()) return $this->propertiesOwned()->pluck('id')->toArray();
        if ($this->isStaff()) return $this->assignedProperties()->pluck('properties.id')->toArray();
        return [];
    }
}