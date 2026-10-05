<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }

    public function set(
        string $key,
        mixed $value,
        string $type = 'string',
        string $group = 'general'
    ): Setting {
        return Setting::setValue(
            $key,
            $value,
            $type,
            $group
        );
    }

    public function pricing(): array
    {
        return [
            'base_price' => $this->get('base_price', 10),
            'price_per_km' => $this->get('price_per_km', 1.5),
            'currency' => $this->get('currency', 'USD'),
        ];
    }

    public function eta(): array
    {
        return [
            'average_speed_kmh' => $this->get(
                'average_speed_kmh',
                50
            ),
        ];
    }
}
