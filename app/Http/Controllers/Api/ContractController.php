<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Contract::with('tenant.user', 'room.property');

        if ($user->isTenant()) {
            $query->whereHas('tenant', fn ($q) => $q->where('user_id', $user->id));
        } else {
            $propertyIds = $user->accessiblePropertyIds();
            $query->whereHas('room', fn ($q) => $q->whereIn('property_id', $propertyIds));
        }

        return response()->json($query->latest()->get());
    }

    public function show(Request $request, Contract $contract)
    {
        $this->authorizeContractAccess($request, $contract);

        return response()->json($contract->load('tenant.user', 'room.property', 'invoices'));
    }

    /**
     * Perpanjangan: bikin baris contract BARU dengan renewed_from_contract_id,
     * kontrak lama diset 'renewed'. Kamar TETAP occupied (tidak berubah).
     */
    public function renew(Request $request, Contract $contract)
    {
        $this->authorizeStaffAccess($request, $contract);

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'rent_amount' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
        ]);

        $newContract = DB::transaction(function () use ($contract, $data, $request) {
            $contract->update(['status' => 'renewed']);

            return Contract::create([
                'tenant_id' => $contract->tenant_id,
                'room_id' => $contract->room_id,
                'created_by' => $request->user()->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'rent_amount' => $data['rent_amount'] ?? $contract->rent_amount,
                'deposit_amount' => $data['deposit_amount'] ?? $contract->deposit_amount,
                'billing_cycle' => $contract->billing_cycle,
                'status' => 'active',
                'renewed_from_contract_id' => $contract->id,
            ]);
        });

        return response()->json($newContract, 201);
    }

    /**
     * Check-out: boleh sebelum end_date asli. end_date di-update ke tanggal aktual,
     * status jadi 'ended', kamar kembali 'available'.
     */
    public function checkout(Request $request, Contract $contract)
    {
        $this->authorizeStaffAccess($request, $contract);

        $data = $request->validate([
            'checkout_date' => 'required|date',
            'room_condition_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($contract, $data) {
            $contract->update([
                'end_date' => $data['checkout_date'],
                'status' => 'ended',
            ]);

            $contract->room->update(['status' => 'available']);
        });

        return response()->json($contract->fresh(['room']));
    }

    /** Boleh lihat kalau: kontrak miliknya sendiri, atau admin/staff yang property-nya cocok. */
    private function authorizeContractAccess(Request $request, Contract $contract): void
    {
        $user = $request->user();

        if ($user->isTenant()) {
            abort_unless($contract->tenant->user_id === $user->id, 403);
            return;
        }

        $propertyIds = $user->accessiblePropertyIds();
        abort_unless(in_array($contract->room->property_id, $propertyIds), 403);
    }

    /** Renew/checkout cuma boleh admin/staff yang property-nya cocok, bukan customer. */
    private function authorizeStaffAccess(Request $request, Contract $contract): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isStaff(), 403);

        $propertyIds = $user->accessiblePropertyIds();
        abort_unless(in_array($contract->room->property_id, $propertyIds), 403);
    }
}