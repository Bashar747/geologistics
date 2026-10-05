<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        if (in_array($user->role, ['admin', 'dispatcher'])) {
            return true;
        }
        if ($user->role === 'customer') {
            return $payment->shipment && $payment->shipment->customer_id === $user->id;
        }
        return false;
    }

    public function update(User $user, Payment $payment): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }
}
