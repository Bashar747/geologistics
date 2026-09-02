<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Models\ShipmentStatusHistory;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Shipment::with(['items', 'customer']);

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        } elseif ($user->role === 'driver') {
            $vehicleId = $user->vehicleAssignments()
                ->where('is_active', true)
                ->value('vehicle_id');

            $query->where('vehicle_id', $vehicleId);
        }

        $shipments = $query->latest()->paginate(15);

        return ShipmentResource::collection($shipments);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_lat' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_lng' => ['required', 'numeric', 'between:-180,180'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.dimensions' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.fragile' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shipment = DB::transaction(function () use ($request) {
            $shipment = Shipment::create([
                'tracking_number' => 'GL-' . strtoupper(Str::random(10)),
                'customer_id' => $request->user()->id,
                'pickup_location' => Point::makeGeodetic($request->pickup_lat, $request->pickup_lng),
                'dropoff_location' => Point::makeGeodetic($request->dropoff_lat, $request->dropoff_lng),
                'status' => 'pending',
            ]);

            foreach ($request->items as $item) {
                $shipment->items()->create($item);
            }

            ShipmentStatusHistory::create([
                'shipment_id' => $shipment->id,
                'status' => 'pending',
                'changed_by' => $request->user()->id,
                'note' => 'Shipment created',
            ]);

            return $shipment;
        });

        return response()->json([
            'message' => 'Shipment created successfully',
            'shipment' => new ShipmentResource($shipment->load('items')),
        ], 201);
    }

    public function show(Request $request, Shipment $shipment)
    {
        $this->authorizeAccess($request, $shipment);

        $shipment->load(['items', 'statusHistory.changedBy', 'customer', 'vehicle.currentAssignment.driver', 'payment', 'rating.ratedBy']);

        return new ShipmentResource($shipment);
    }

    public function update(Request $request, Shipment $shipment)
    {
        $user = $request->user();

        if (! in_array($user->role, ['driver', 'dispatcher', 'admin'], true)) {
            return response()->json(['message' => 'You are not authorized to update this shipment\'s status'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:pending,assigned,picked_up,delivered,cancelled'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $shipment, $user) {
            $shipment->update(['status' => $request->status]);

            ShipmentStatusHistory::create([
                'shipment_id' => $shipment->id,
                'status' => $request->status,
                'changed_by' => $user->id,
                'note' => $request->note,
            ]);
        });

        return response()->json([
            'message' => 'Shipment status updated',
            'shipment' => new ShipmentResource($shipment->fresh(['items', 'statusHistory'])),
        ]);
    }

    public function destroy(Request $request, Shipment $shipment)
    {
        $user = $request->user();

        if ($user->role === 'customer' && $shipment->customer_id !== $user->id) {
            return response()->json(['message' => 'You are not authorized to delete this shipment'], 403);
        }

        if ($shipment->status !== 'pending') {
            return response()->json(['message' => 'Cannot delete a shipment that has already been assigned or progressed'], 422);
        }

        $shipment->delete();

        return response()->json(['message' => 'Shipment deleted']);
    }

    private function authorizeAccess(Request $request, Shipment $shipment): void
    {
        $user = $request->user();

        if ($user->role === 'customer' && $shipment->customer_id !== $user->id) {
            abort(403, 'You are not authorized to access this shipment');
        }

        if ($user->role === 'driver') {
            $activeVehicleId = $user->vehicleAssignments()
                ->where('is_active', true)
                ->value('vehicle_id');

            if ($shipment->vehicle_id !== $activeVehicleId) {
                abort(403, 'You are not authorized to access this shipment');
            }
        }
    }
}