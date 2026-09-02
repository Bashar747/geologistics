<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'weight_kg' => $this->weight_kg ? (float) $this->weight_kg : null,
            'dimensions' => $this->dimensions,
            'quantity' => $this->quantity,
            'fragile' => (bool) $this->fragile,
        ];
    }
}