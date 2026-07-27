<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentVerificationService
{
    public function verify(Payment $payment, User $verifier): Payment
    {
        return DB::transaction(function () use ($payment, $verifier) {
            // Update HANYA jika status masih pending - cegah 2 admin verifikasi bersamaan
            $affected = Payment::where('id', $payment->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'verified',
                    'verified_by' => $verifier->id,
                    'verified_at' => now(),
                ]);

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'status' => ['Pembayaran ini sudah diproses sebelumnya.'],
                ]);
            }

            $payment->refresh();
            $this->recalculateInvoiceStatus($payment);

            return $payment;
        });
    }

    public function reject(Payment $payment, User $verifier, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $verifier, $reason) {
            $affected = Payment::where('id', $payment->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'verified_by' => $verifier->id,
                    'verified_at' => now(),
                    'rejection_reason' => $reason,
                ]);

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'status' => ['Pembayaran ini sudah diproses sebelumnya.'],
                ]);
            }

            $payment->refresh();
            $this->recalculateInvoiceStatus($payment);

            return $payment;
        });
    }

    private function recalculateInvoiceStatus(Payment $payment): void
    {
        $invoice = $payment->invoice()->lockForUpdate()->first();
        $verifiedTotal = $invoice->verifiedTotal();

        if ($verifiedTotal <= 0) {
            $status = 'unpaid';
        } elseif ($verifiedTotal < $invoice->total_amount) {
            $status = 'partial';
        } else {
            $status = 'paid';
        }

        $invoice->update(['status' => $status]);
    }
}