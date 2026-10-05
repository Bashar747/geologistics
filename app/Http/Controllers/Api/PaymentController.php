<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    // عرض دفعة شحنة معينة
    public function show(Request $request, Shipment $shipment)
    {
        $this->authorizeAccess($request, $shipment);

        if (! $shipment->payment) {
            return response()->json(['message' => 'No payment found for this shipment'], 404);
        }

        return response()->json($shipment->payment);
    }

    // إنشاء دفعة لشحنة (عميل فقط، وبس لشحنته الخاصة)
    public function store(Request $request, Shipment $shipment)
    {
        $user = $request->user();

        if ($user->role !== 'customer' || $shipment->customer_id !== $user->id) {
            return response()->json(['message' => 'You are not authorized to pay for this shipment'], 403);
        }

        if ($shipment->payment) {
            return response()->json(['message' => 'A payment already exists for this shipment'], 422);
        }
$validator = Validator::make($request->all(), [
    'method' => ['required', 'in:cash,card,wallet'],
]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

   $amount = $shipment->total_amount;

if ($amount === null) {
    return response()->json([
        'message' => 'Shipment amount has not been calculated yet',
    ], 422);
}

$payment = Payment::create([
    'shipment_id' => $shipment->id,
    'amount' => $amount,
    'method' => $request->method,
    // الدفع كاش بيصير "paid" فوراً، وباقي الطرق تنتظر تأكيد
    'status' => $request->method === 'cash' ? 'paid' : 'pending',
    'paid_at' => $request->method === 'cash' ? now() : null,
]);

        

        return response()->json([
            'message' => 'Payment created successfully',
            'payment' => $payment,
        ], 201);
    }

    // تأكيد الدفع (أدمن/موزّع فقط) - لحالات card/wallet يلي بتحتاج تأكيد يدوي أو من بوابة خارجية
    public function confirm(Request $request, Payment $payment)
    {
        if ($payment->status === 'paid') {
            return response()->json(['message' => 'Payment is already confirmed'], 422);
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'payment' => $payment->fresh(),
        ]);
    }

    // استرجاع الدفعة (أدمن/موزّع فقط)
    public function refund(Request $request, Payment $payment)
    {
        if ($payment->status !== 'paid') {
            return response()->json(['message' => 'Only paid payments can be refunded'], 422);
        }

        $payment->update(['status' => 'refunded']);

        return response()->json([
            'message' => 'Payment refunded successfully',
            'payment' => $payment->fresh(),
        ]);
    }

    private function authorizeAccess(Request $request, Shipment $shipment): void
    {
        $user = $request->user();

        if ($user->role === 'customer' && $shipment->customer_id !== $user->id) {
            abort(403, 'You are not authorized to access this payment');
        }
    }
}