<?php

namespace App\Support;

class MeasurementHelper
{
    /**
     * Normalize free-form key-value measurements (tailor's own field names).
     *
     * @param  array<string, mixed>  $measurements
     * @return array<string, string|float>
     */
    public static function normalize(array $measurements): array
    {
        $result = [];

        foreach ($measurements as $key => $value) {
            $key = trim((string) $key);
            if ($key === '' || $value === null || $value === '') {
                continue;
            }

            $result[$key] = is_numeric($value) ? (float) $value : (string) $value;
        }

        return $result;
    }

    /**
     * @param  array<int, array{name?: string, measurements?: array<string, mixed>}>  $sections
     * @return array<int, array{name: string, measurements: array<string, string|float>}>
     */
    public static function normalizeSections(array $sections): array
    {
        return collect($sections)
            ->map(function ($section, $index) {
                $name = trim((string) ($section['name'] ?? ''));
                $measurements = self::normalize($section['measurements'] ?? []);

                return [
                    'name' => $name !== '' ? $name : 'Section '.($index + 1),
                    'measurements' => $measurements,
                ];
            })
            ->filter(fn ($section) => $section['name'] !== '' || count($section['measurements']) > 0)
            ->values()
            ->all();
    }
}
