<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenantOnboardingService
{
    public function onboard(array $data, User $createdBy): array
    {
        return DB::transaction(function () use ($data, $createdBy) {
            // Lock baris kamar dulu - cegah 2 staff check-in ke kamar sama secara bersamaan
            $room = Room::where('id', $data['room_id'])->lockForUpdate()->firstOrFail();

            if ($room->status !== 'available') {
                throw ValidationException::withMessages([
                    'room_id' => ['Kamar sudah terisi.'],
                ]);
            }

            $email = $data['email'] ?? null;
            if (empty($email)) {
                $email = $data['ktp_number'].'@kost.local';
            }

            $plainPassword = Str::password(8, symbols: false);

            $user = User::create([
                'name' => $data['name'],
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'password' => $plainPassword,
                'role' => 'tenant',
                'status' => 'active',
                'created_by' => $createdBy->id,
            ]);

            $tenant = Tenant::create([
                'user_id' => $user->id,
                'ktp_number' => $data['ktp_number'],
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'join_date' => $data['join_date'] ?? now()->toDateString(),
            ]);

            $contract = Contract::create([
                'tenant_id' => $tenant->id,
                'room_id' => $room->id,
                'created_by' => $createdBy->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'rent_amount' => $data['rent_amount'] ?? $room->price,
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'status' => 'active',
            ]);

            $room->update(['status' => 'occupied']);

            return ['user' => $user, 'tenant' => $tenant, 'contract' => $contract, 'plain_password' => $plainPassword];
        });
    }
}