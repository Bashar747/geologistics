<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class RoutingService
{
  public function route(
    float $fromLat,
    float $fromLng,
    float $toLat,
    float $toLng
): array {
    $url = 'https://graphhopper.com/api/1/route'
        .'?point='.urlencode("{$fromLat},{$fromLng}")
        .'&point='.urlencode("{$toLat},{$toLng}")
        .'&profile=car'
        .'&locale=en'
        .'&calc_points=true'
        .'&points_encoded=false'
        .'&key='.urlencode(config('services.graphhopper.api_key'));

    $response = Http::timeout(15)->get($url);

    if ($response->failed()) {
    return [];
}

    return $response->json();
}
}