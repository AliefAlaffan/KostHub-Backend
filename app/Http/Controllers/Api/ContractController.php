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
        return response()->json(
            Contract::with('tenant.user', 'room.property')->latest()->get()
        );
    }

    public function show(Request $request, Contract $contract)
    {
        return response()->json($contract->load('tenant.user', 'room.property', 'invoices'));
    }

    /**
     * Perpanjangan: bikin baris contract BARU dengan renewed_from_contract_id,
     * kontrak lama diset 'renewed'. Kamar TETAP occupied (tidak berubah).
     */
    public function renew(Request $request, Contract $contract)
    {
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
}