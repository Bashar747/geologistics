<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    // عرض تقييم شحنة معينة
    public function show(Request $request, Shipment $shipment)
    {
        $this->authorizeAccess($request, $shipment);

        if (! $shipment->rating) {
            return response()->json(['message' => 'No rating found for this shipment'], 404);
        }

        return response()->json($shipment->rating);
    }

    // إنشاء تقييم (عميل فقط، بس لشحنته، وبس بعد التسليم)
    public function store(Request $request, Shipment $shipment)
{
    $user = $request->user();

    if ($user->role !== 'customer' || $shipment->customer_id !== $user->id) {
        return response()->json(['message' => 'You are not authorized to rate this shipment'], 403);
    }

    if ($shipment->status !== 'delivered') {
        return response()->json(['message' => 'You can only rate a shipment after it has been delivered'], 422);
    }

    if ($shipment->rating) {
        return response()->json(['message' => 'This shipment has already been rated'], 422);
    }

    $validator = Validator::make($request->all(), [
        'score' => ['required', 'integer', 'between:1,5'],
        'comment' => ['nullable', 'string', 'max:1000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

    $rating = DB::transaction(function () use ($request, $shipment, $user) {
        $rating = Rating::create([
            'shipment_id' => $shipment->id,
            'rated_by' => $user->id,
            'score' => $request->score,
            'comment' => $request->comment,
        ]);

        // نحدّث متوسط تقييم السائق تلقائياً (لو في سائق مخصص فعلياً للمركبة وقت التسليم)
        if ($shipment->vehicle_id) {
            $activeAssignment = $shipment->vehicle
                ->assignments()
                ->where('is_active', true)
                ->first();

            if ($activeAssignment && $activeAssignment->driver->driverProfile) {
                $profile = $activeAssignment->driver->driverProfile;

                $newAverage = Rating::whereHas('shipment', function ($q) use ($activeAssignment) {
                    $q->where('vehicle_id', $activeAssignment->vehicle_id);
                })->avg('score');

                $profile->update(['rating_avg' => round($newAverage, 2)]);
            }
        }

        return $rating;
    });

    return response()->json([
        'message' => 'Rating submitted successfully',
        'rating' => $rating,
    ], 201);
}

    private function authorizeAccess(Request $request, Shipment $shipment): void
{
    $user = $request->user();

    if (in_array($user->role, ['admin', 'dispatcher'], true)) {
        return;
    }

    if ($user->role === 'customer') {
        abort_unless(
            $shipment->customer_id === $user->id,
            403,
            'You are not authorized to access this rating'
        );

        return;
    }

    if ($user->role === 'driver') {
        $hasActiveAssignment = $user->vehicleAssignments()
            ->where('is_active', true)
            ->where('vehicle_id', $shipment->vehicle_id)
            ->exists();

        abort_unless(
            $hasActiveAssignment,
            403,
            'You are not authorized to access this rating'
        );

        return;
    }

    abort(403, 'You are not authorized to access this rating');
}
}