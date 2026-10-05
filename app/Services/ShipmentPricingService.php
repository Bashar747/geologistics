<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

class ShipmentPricingService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {
    }

    public function calculate(Shipment $shipment): array
    {
        if (! $shipment->pickup_location || ! $shipment->dropoff_location) {
            throw new \InvalidArgumentException(
                'Shipment must have pickup and dropoff locations.'
            );
        }

        $distanceMeters = DB::table('shipments')
            ->where('id', $shipment->id)
            ->selectRaw(
                'ST_Distance(pickup_location, dropoff_location) as distance_meters'
            )
            ->value('distance_meters');

        $distanceKm = round(((float) $distanceMeters) / 1000, 2);

        $pricing = $this->settings->pricing();
        $etaSettings = $this->settings->eta();

        $basePrice = (float) $pricing['base_price'];
        $pricePerKm = (float) $pricing['price_per_km'];
        $averageSpeedKmh = (float) $etaSettings['average_speed_kmh'];

        $totalAmount = round(
            $basePrice + ($distanceKm * $pricePerKm),
            2
        );

        $travelMinutes = $averageSpeedKmh > 0
            ? ($distanceKm / $averageSpeedKmh) * 60
            : 0;

        $estimatedArrival = now()->addMinutes(
            (int) ceil($travelMinutes)
        );

        return [
            'distance_km' => $distanceKm,
            'total_amount' => $totalAmount,
            'currency' => $pricing['currency'],
            'average_speed_kmh' => $averageSpeedKmh,
            'estimated_arrival' => $estimatedArrival,
            'travel_minutes' => (int) ceil($travelMinutes),
        ];
    }

    public function apply(Shipment $shipment): Shipment
    {
        $calculation = $this->calculate($shipment);

        $shipment->update([
            'total_amount' => $calculation['total_amount'],
            'estimated_arrival' => $calculation['estimated_arrival'],
        ]);

        return $shipment->refresh();
    }
}
