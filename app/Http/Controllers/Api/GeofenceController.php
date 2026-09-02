<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Geofence;
use App\Models\Vehicle;
use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;
use Clickbar\Magellan\Data\Geometries\Polygon;
use Clickbar\Magellan\Database\PostgisFunctions\ST;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeofenceController extends Controller
{
    // عرض كل المناطق الجغرافية
    public function index()
    {
        return response()->json(Geofence::paginate(15));
    }

    // إنشاء منطقة جغرافية جديدة (أدمن/موزّع فقط)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:warehouse,restricted_zone,delivery_area'],
            'points' => ['required', 'array', 'min:3'],
            'points.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'points.*.lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $coordinates = collect($request->points)
            ->map(fn ($p) => Point::make($p['lng'], $p['lat'], srid: 4326))
            ->values()
            ->all();

        // نغلق الحلقة تلقائياً إذا أول نقطة ما هي نفس آخر نقطة
        $points = $request->points;
        $first = $points[0];
        $last = end($points);
        if ($first['lat'] !== $last['lat'] || $first['lng'] !== $last['lng']) {
            $coordinates[] = Point::make($first['lng'], $first['lat'], srid: 4326);
        }

        $ring = LineString::make($coordinates, srid: 4326);

        $geofence = Geofence::create([
            'name' => $request->name,
            'type' => $request->type,
            'area_polygon' => Polygon::make([$ring], srid: 4326),
        ]);

        return response()->json([
            'message' => 'Geofence created successfully',
            'geofence' => $geofence,
        ], 201);
    }

    // عرض منطقة واحدة
    public function show(Geofence $geofence)
    {
        return response()->json($geofence);
    }

    // تعديل منطقة (أدمن/موزّع فقط) - بس الاسم والنوع، مش الشكل الهندسي (لتبسيط الموضوع)
    public function update(Request $request, Geofence $geofence)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:150'],
            'type' => ['sometimes', 'in:warehouse,restricted_zone,delivery_area'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $geofence->update($validator->validated());

        return response()->json([
            'message' => 'Geofence updated successfully',
            'geofence' => $geofence->fresh(),
        ]);
    }

    // حذف منطقة (أدمن/موزّع فقط)
    public function destroy(Geofence $geofence)
    {
        $geofence->delete();

        return response()->json(['message' => 'Geofence deleted']);
    }

    // 🎯 فحص: أي نقطة (lat/lng) جوا أي مناطق؟
    public function checkPoint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $point = Point::makeGeodetic($request->lat, $request->lng);

        $matches = Geofence::where(ST::contains('area_polygon', $point), true)->get();

        return response()->json([
            'inside_geofences' => $matches,
            'count' => $matches->count(),
        ]);
    }

    // 🎯 فحص: مركبة معينة، هلق، جوا أي مناطق (حسب آخر موقع معروف لها)؟
    public function checkVehicle(Vehicle $vehicle)
    {
        if (! $vehicle->last_location) {
            return response()->json(['message' => 'This vehicle has no known location yet'], 422);
        }

        $matches = Geofence::where(ST::contains('area_polygon', $vehicle->last_location), true)->get();

        return response()->json([
            'vehicle_id' => $vehicle->id,
            'inside_geofences' => $matches,
            'count' => $matches->count(),
        ]);
    }
}