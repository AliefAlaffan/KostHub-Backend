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
        $data = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
        ]);

        $contract = Contract::findOrFail($data['contract_id']);
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
        return response()->json(
            $invoice->load('items', 'payments', 'contract.tenant.user', 'contract.room.property')
        );
    }
}