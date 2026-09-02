<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationLog;
use App\Models\Vehicle;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Events\VehicleLocationUpdated;

class VehicleController extends Controller
{
   
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Vehicle::with(['currentAssignment.driver:id,name,phone']);

        if ($user->role === 'driver') {
            $vehicleId = $user->vehicleAssignments()
                ->where('is_active', true)
                ->value('vehicle_id');

            $query->where('id', $vehicleId);
        }

        return response()->json($query->paginate(15));
    }

   
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => ['required', 'string', 'max:50', 'unique:vehicles,plate_number'],
            'model' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data verification error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $vehicle = Vehicle::create([
            'plate_number' => $request->plate_number,
            'model' => $request->model,
            'type' => $request->type,
            'status' => 'idle',
        ]);

        return response()->json([
            'message' => 'Vehicle created successfully',
            'vehicle' => $vehicle,
        ], 201);
    }

  
    public function show(Request $request, Vehicle $vehicle)
    {
        $this->authorizeAccess($request, $vehicle);

        return response()->json(
            $vehicle->load(['currentAssignment.driver:id,name,phone', 'shipments' => function ($q) {
                $q->whereIn('status', ['assigned', 'picked_up'])->latest()->limit(5);
            }])
        );
    }


    public function update(Request $request, Vehicle $vehicle)
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => ['sometimes', 'string', 'max:50', 'unique:vehicles,plate_number,' . $vehicle->id],
            'model' => ['sometimes', 'nullable', 'string', 'max:100'],
            'type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'in:idle,in_transit,maintenance,offline'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data verification error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $vehicle->update($validator->validated());

        return response()->json([
            'message' => 'Vehicle updated successfully',
            'vehicle' => $vehicle->fresh(),
        ]);
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted successfully']);
    }

   
    public function updateLocation(Request $request, Vehicle $vehicle)
    {
        $user = $request->user();

        if ($user->role !== 'driver') {
            return response()->json(['message' => 'Only drivers can update vehicle location'], 403);
        }

        $activeVehicleId = $user->vehicleAssignments()
            ->where('is_active', true)
            ->value('vehicle_id');

        if ($vehicle->id !== $activeVehicleId) {
            return response()->json(['message' => 'This vehicle is not assigned to you'], 403);
        }

        $validator = Validator::make($request->all(), [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'integer', 'between:0,359'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data verification error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $point = Point::makeGeodetic($request->lat, $request->lng);

        DB::transaction(function () use ($vehicle, $point, $request) {
           
            $vehicle->update(['last_location' => $point]);

           
            LocationLog::create([
                'vehicle_id' => $vehicle->id,
                'location' => $point,
                'speed' => $request->speed,
                'heading' => $request->heading,
                'recorded_at' => now(),
            ]);
        });
             broadcast(new VehicleLocationUpdated($vehicle->fresh()));
        return response()->json([
            'message' => 'Vehicle location updated successfully',
            'vehicle' => $vehicle->fresh(),
        ]);
    }

    private function authorizeAccess(Request $request, Vehicle $vehicle): void
    {
        $user = $request->user();

        if ($user->role === 'driver') {
            $activeVehicleId = $user->vehicleAssignments()
                ->where('is_active', true)
                ->value('vehicle_id');

            if ($vehicle->id !== $activeVehicleId) {
                abort(403, 'You are not assigned to this vehicle');
            }
        }
    }
}