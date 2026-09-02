<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            'status' => $this->status,
            'pickup_location' => $this->pickup_location,
            'dropoff_location' => $this->dropoff_location,
            'estimated_arrival' => $this->estimated_arrival,
            'total_amount' => $this->total_amount ? (float) $this->total_amount : null,
            'customer' => new UserResource($this->whenLoaded('customer')),
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'items' => ShipmentItemResource::collection($this->whenLoaded('items')),
            'status_history' => ShipmentStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'rating' => new RatingResource($this->whenLoaded('rating')),
            'created_at' => $this->created_at,
        ];
    }
}