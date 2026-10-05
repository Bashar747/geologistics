<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;

class PublicTrackingController extends Controller
{
    // تتبع شحنة برقم التتبع بس، بدون تسجيل دخول
    public function show(string $trackingNumber)
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->with([
                'items:id,shipment_id,description,quantity',
                'statusHistory:id,shipment_id,status,created_at',
                'vehicle:id,status,last_location',
            ])
            ->first();

        if (! $shipment) {
            return response()->json(['message' => 'Tracking number not found'], 404);
        }

        
        return response()->json([
            'tracking_number' => $shipment->tracking_number,
            'status' => $shipment->status,
            'pickup_location' => $shipment->pickup_location,
            'dropoff_location' => $shipment->dropoff_location,
            'estimated_arrival' => $shipment->estimated_arrival,
            'items' => $shipment->items,
            'status_history' => $shipment->statusHistory,
         // موقع المركبة اللحظي بعد تخصيص الشحنة وحتى استلامها
            'current_vehicle_location' => in_array($shipment->status, ['assigned', 'picked_up'])
                ? $shipment->vehicle?->last_location
                : null,
        ]);
    }
}