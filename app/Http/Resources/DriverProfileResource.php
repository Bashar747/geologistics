<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'license_number' => $this->license_number,
            'license_expiry' => $this->license_expiry,
            'status' => $this->status,
            'rating_avg' => (float) $this->rating_avg,
            'joined_at' => $this->joined_at,
        ];
    }
}