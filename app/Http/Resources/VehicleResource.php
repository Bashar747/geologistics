<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'plate_number' => $this->plate_number,
        'model' => $this->model,
        'type' => $this->type,
        'status' => $this->status,
        'last_location' => $this->last_location,
        'current_driver' => $this->whenLoaded('currentAssignment', function () {
            return $this->currentAssignment?->driver
                ? new UserResource($this->currentAssignment->driver)
                : null;
        }),
        'created_at' => $this->created_at,
    ];
}
}