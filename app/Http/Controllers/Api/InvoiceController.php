<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Services\InvoiceGenerationService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Invoice::with('contract.tenant.user', 'contract.room', 'items', 'payments');

        if ($user->isTenant()) {
            $query->whereHas('contract.tenant', fn ($q) => $q->where('user_id', $user->id));
        } else {
            $propertyIds = $user->accessiblePropertyIds();
            $query->whereHas('contract.room', fn ($q) => $q->whereIn('property_id', $propertyIds));
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request, InvoiceGenerationService $service)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isStaff(), 403, 'Hanya admin/staff yang bisa generate invoice.');

        $data = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
        ]);

        $contract = Contract::findOrFail($data['contract_id']);

        $propertyIds = $user->accessiblePropertyIds();
        abort_unless(in_array($contract->room->property_id, $propertyIds), 403);

        $invoice = $service->generateNextCycleIfDue($contract);

        if (!$invoice) {
            return response()->json([
                'message' => 'Belum waktunya generate siklus tagihan berikutnya untuk kontrak ini, atau kontrak sudah berakhir.',
            ], 422);
        }

        return response()->json($invoice->load('items'), 201);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $user = $request->user();

        if ($user->isTenant()) {
            abort_unless($invoice->contract->tenant->user_id === $user->id, 403);
        } else {
            $propertyIds = $user->accessiblePropertyIds();
            abort_unless(in_array($invoice->contract->room->property_id, $propertyIds), 403);
        }

        return response()->json(
            $invoice->load('items', 'payments', 'contract.tenant.user', 'contract.room.property')
        );
    }
}