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
        $user = $request->user();

        if ($user->isTenant()) {
            abort_unless($invoice->contract->tenant->user_id === $user->id, 403);
        } else {
            $propertyIds = $user->accessiblePropertyIds();
            abort_unless(in_array($invoice->contract->room->property_id, $propertyIds), 403);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:transfer,cash,ewallet',
            'payment_date' => 'required|date',
            'proof_image' => 'nullable|image|max:5120',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof_image')) {
            $proofPath = $request->file('proof_image')->store("invoices/{$invoice->id}/proofs", 'public');
        }

        $payment = $invoice->payments()->create([
            ...$data,
            'proof_image' => $proofPath,
            'status' => 'pending',
        ]);

        return response()->json($payment, 201);
    }

    public function verify(Request $request, Payment $payment, PaymentVerificationService $service)
    {
        $this->authorizeStaffAccess($request, $payment);

        $payment = $service->verify($payment, $request->user());
        return response()->json($payment->fresh('invoice'));
    }

    public function reject(Request $request, Payment $payment, PaymentVerificationService $service)
    {
        $this->authorizeStaffAccess($request, $payment);

        $data = $request->validate(['reason' => 'required|string|max:500']);
        $payment = $service->reject($payment, $request->user(), $data['reason']);
        return response()->json($payment->fresh('invoice'));
    }

    /** Cuma admin/staff yang property-nya mencakup invoice dari payment ini. */
    private function authorizeStaffAccess(Request $request, Payment $payment): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isStaff(), 403);

        $propertyId = $payment->invoice->contract->room->property_id;
        $propertyIds = $user->accessiblePropertyIds();
        abort_unless(in_array($propertyId, $propertyIds), 403);
    }
}