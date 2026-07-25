<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function view(User $user, Room $room): bool
    {
        if ($user->isTenant()) return true;
        return in_array($room->property_id, $user->accessiblePropertyIds());
    }

    public function update(User $user, Room $room): bool
    {
        return in_array($user->role, ['admin', 'staff'])
            && in_array($room->property_id, $user->accessiblePropertyIds());
    }

    public function delete(User $user, Room $room): bool
    {
        return $this->update($user, $room);
    }
}