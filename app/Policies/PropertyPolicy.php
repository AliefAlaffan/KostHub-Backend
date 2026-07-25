<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff']);
    }

    public function view(User $user, Property $property): bool
    {
        if ($user->isAdmin()) return $property->admin_id === $user->id;
        if ($user->isStaff()) return in_array($property->id, $user->accessiblePropertyIds());
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Property $property): bool
    {
        return $user->isAdmin() && $property->admin_id === $user->id;
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->update($user, $property);
    }
}