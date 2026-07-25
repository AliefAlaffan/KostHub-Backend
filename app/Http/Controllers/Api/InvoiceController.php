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
        return response()->json(
            Invoice::with('contract.tenant.user', 'contract.room', 'items')->latest()->get()
        );
    }

    public function store(Request $request, InvoiceGenerationService $service)
    {
        $data = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'period' => 'required|date_format:Y-m',
        ]);

        $contract = Contract::findOrFail($data['contract_id']);
        $invoice = $service->generateForContract($contract, $data['period']);

        if (!$invoice) {
            return response()->json(['message' => 'Kontrak tidak aktif pada periode ini.'], 422);
        }

        return response()->json($invoice->load('items'), 201);
    }

    public function show(Request $request, Invoice $invoice)
    {
        return response()->json($invoice->load('items', 'contract.tenant.user', 'contract.room'));
    }
}