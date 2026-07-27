<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentVerificationService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:transfer,cash,ewallet',
            'payment_date' => 'required|date',
        ]);

        $payment = $invoice->payments()->create([...$data, 'status' => 'pending']);

        return response()->json($payment, 201);
    }

    public function verify(Request $request, Payment $payment, PaymentVerificationService $service)
    {
        $payment = $service->verify($payment, $request->user());
        return response()->json($payment->fresh('invoice'));
    }

    public function reject(Request $request, Payment $payment, PaymentVerificationService $service)
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);
        $payment = $service->reject($payment, $request->user(), $data['reason']);
        return response()->json($payment->fresh('invoice'));
    }
}