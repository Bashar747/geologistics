<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\AuditLogger;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        AuditLogger::log(
            'payment.processed',
            'Payment',
            $payment->id,
            'Payment of ' . $payment->amount . ' via ' . $payment->method
        );
    }

    public function updated(Payment $payment): void
    {
        if ($payment->isDirty('status') && $payment->status === 'refunded') {
            AuditLogger::log('payment.refunded', 'Payment', $payment->id);
        }
    }
}