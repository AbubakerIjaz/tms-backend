<?php

namespace App\Support;

class VoiceMeasurementParser
{
    /** @var array<string, list<string>> */
    private const FIELD_ALIASES = [
        'Length' => ['length', 'lambaai', 'lamba', 'lambai', 'lambayi', 'height', 'tall'],
        'Shoulder' => ['shoulder', 'kandha', 'kandhe', 'shoulders'],
        'Chest' => ['chest', 'chati', 'chhaati', 'chhati', 'sina', 'chhaati'],
        'Waist' => ['waist', 'kamar', 'pet'],
        'Bottom' => ['bottom', 'hem', 'ghera', 'ghaira'],
        'Sleeves' => ['sleeve', 'sleeves', 'bazu', 'baazu', 'baazoo'],
        'Arm Hole' => ['arm hole', 'armhole', 'bazu ghera', 'bazu ghera'],
        'Cuff' => ['cuff', 'mohri', 'muhri', 'cuffs'],
        'Collar Size' => ['collar', 'collar size', 'gala', 'gala size'],
        'In Seam' => ['in seam', 'inseam', 'in-seam', 'andar ki lambai'],
        'Neck' => ['neck', 'gardan'],
        'Hip' => ['hip', 'hips', 'kamar'],
    ];

    /** @var array<string, list<string>> */
    private const SECTION_ALIASES = [
        'Kameez' => ['kameez', 'kamiz', 'kurta', 'shirt top'],
        'Shalwar' => ['shalwar', 'salwar', 'trouser bottom'],
        'Pant' => ['pant', 'pants', 'trouser', 'trousers'],
        'Shirt' => ['shirt'],
        'Waistcoat' => ['waistcoat', 'vest', 'sadri'],
    ];

    /** @var array<string, float> */
    private const NUMBER_WORDS = [
        'zero' => 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4,
        'five' => 5, 'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9,
        'ten' => 10, 'eleven' => 11, 'twelve' => 12, 'thirteen' => 13,
        'fourteen' => 14, 'fifteen' => 15, 'sixteen' => 16, 'seventeen' => 17,
        'eighteen' => 18, 'nineteen' => 19, 'twenty' => 20, 'thirty' => 30,
        'forty' => 40, 'fifty' => 50, 'sixty' => 60, 'seventy' => 70,
        'eighty' => 80, 'ninety' => 90, 'hundred' => 100,
        'aik' => 1, 'ek' => 1, 'do' => 2, 'teen' => 3, 'char' => 4, 'chaar' => 4,
        'paanch' => 5, 'panch' => 5, 'chhe' => 6, 'che' => 6, 'saat' => 7,
        'aath' => 8, 'ath' => 8, 'nau' => 9, 'das' => 10,
        'gyarah' => 11, 'barah' => 12, 'terah' => 13, 'chaudah' => 14,
        'pandrah' => 15, 'solah' => 16, 'satrah' => 17, 'atharah' => 18,
        'unnees' => 19, 'bees' => 20, 'chalees' => 40, 'pachas' => 50,
    ];

    /**
     * @return array{
     *   client_hint: string|null,
     *   label: string|null,
     *   sections: array<int, array{name: string, measurements: array<string, float|string>}>,
     *   warnings: array<int, string>,
     *   transcript: string
     * }
     */
    public static function parse(string $text): array
    {
        $transcript = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        $warnings = [];

        if ($transcript === '') {
            return [
                'client_hint' => null,
                'label' => null,
                'sections' => [],
                'warnings' => ['Say or type measurements like: Kameez length 42 chest 24 shalwar length 40'],
                'transcript' => '',
            ];
        }

        $clientHint = self::extractClientHint($transcript);
        $label = self::extractLabel($transcript);
        $working = self::stripMetaPhrases($transcript);

        $sections = self::parseSections($working, $warnings);

        if (empty($sections)) {
            $fallback = self::parseFlatMeasurements($working);
            if (! empty($fallback)) {
                $sections[] = ['name' => 'Measurements', 'measurements' => $fallback];
            }
        }

        $sections = MeasurementHelper::normalizeSections($sections);

        if (empty($sections)) {
            $warnings[] = 'Could not find any measurements. Example: "Kameez length 42 chest 24 bottom 25"';
        }

        return [
            'client_hint' => $clientHint,
            'label' => $label,
            'sections' => $sections,
            'warnings' => array_values(array_unique($warnings)),
            'transcript' => $transcript,
        ];
    }

    private static function extractClientHint(string $text): ?string
    {
        if (preg_match('/\b(?:for|client|customer|gahak|gahak|mister|mr\.?|miss|mrs\.?)\s+([a-z][a-z\s]{1,40}?)(?=\s+(?:kameez|shalwar|pant|shirt|length|chest|waist|measurement|nap|size)\b|$)/iu', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private static function extractLabel(string $text): ?string
    {
        if (preg_match('/\b(?:label|note|title)\s+([a-z0-9\s\-]{2,40})/iu', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private static function stripMetaPhrases(string $text): string
    {
        $patterns = [
            '/\b(?:add|save|record|create|new|measurement|measurements|nap|size|sizes)\b/iu',
            '/\b(?:for|client|customer|gahak|mister|mr\.?|miss|mrs\.?)\s+[a-z][a-z\s]{1,40}/iu',
            '/\b(?:label|note|title)\s+[a-z0-9\s\-]{2,40}/iu',
        ];

        $result = $text;
        foreach ($patterns as $pattern) {
            $result = preg_replace($pattern, ' ', $result) ?? $result;
        }

        return trim(preg_replace('/\s+/', ' ', $result) ?? '');
    }

    /**
     * @param  list<string>  $warnings
     * @return array<int, array{name: string, measurements: array<string, float>}>
     */
    private static function parseSections(string $text, array &$warnings): array
    {
        $sections = [];
        $lower = strtolower($text);

        foreach (self::SECTION_ALIASES as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                $pos = strpos($lower, $alias);
                if ($pos === false) {
                    continue;
                }

                $chunk = self::sectionChunk($text, $pos, strlen($alias));
                $measurements = self::extractMeasurementsFromChunk($chunk);

                if (! empty($measurements)) {
                    $sections[] = ['name' => $canonical, 'measurements' => $measurements];
                }
                break;
            }
        }

        return $sections;
    }

    private static function sectionChunk(string $text, int $start, int $aliasLen): string
    {
        $lower = strtolower($text);
        $end = strlen($text);
        $chunkStart = $start + $aliasLen;

        foreach (self::SECTION_ALIASES as $aliases) {
            foreach ($aliases as $alias) {
                $next = strpos($lower, $alias, $chunkStart);
                if ($next !== false && $next < $end) {
                    $end = min($end, $next);
                }
            }
        }

        return trim(substr($text, $chunkStart, max(0, $end - $chunkStart)));
    }

    /**
     * @return array<string, float>
     */
    private static function parseFlatMeasurements(string $text): array
    {
        return self::extractMeasurementsFromChunk($text);
    }

    /**
     * @return array<string, float>
     */
    private static function extractMeasurementsFromChunk(string $chunk): array
    {
        $measurements = [];
        $lower = strtolower($chunk);

        foreach (self::FIELD_ALIASES as $field => $aliases) {
            usort($aliases, fn ($a, $b) => strlen($b) <=> strlen($a));

            foreach ($aliases as $alias) {
                $pattern = '/\b'.preg_quote($alias, '/').'\s*(?:is|=|:)?\s*([a-z\-]+(?:\s+[a-z\-]+){0,2}|\d+(?:\.\d+)?)/iu';
                if (! preg_match($pattern, $lower, $match, PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                $value = self::parseNumber($match[1][0]);
                if ($value !== null) {
                    $measurements[$field] = $value;
                    break;
                }
            }
        }

        if (preg_match_all('/(\d+(?:\.\d+)?)/', $chunk, $numbers) && empty($measurements)) {
            $defaults = ['Length', 'Chest', 'Waist', 'Bottom', 'Sleeves'];
            foreach ($numbers[1] as $i => $num) {
                if (! isset($defaults[$i])) {
                    break;
                }
                $measurements[$defaults[$i]] = (float) $num;
            }
        }

        return $measurements;
    }

    private static function parseNumber(string $raw): ?float
    {
        $raw = strtolower(trim($raw));
        if ($raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            return (float) $raw;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)/', $raw, $m)) {
            return (float) $m[1];
        }

        $parts = preg_split('/[\s\-]+/', $raw) ?: [];
        if (count($parts) === 1 && isset(self::NUMBER_WORDS[$parts[0]])) {
            return (float) self::NUMBER_WORDS[$parts[0]];
        }

        if (count($parts) === 2) {
            $a = self::NUMBER_WORDS[$parts[0]] ?? null;
            $b = self::NUMBER_WORDS[$parts[1]] ?? null;
            if ($a !== null && $b !== null) {
                if ($b >= 10 && $b % 10 === 0) {
                    return (float) ($a + $b);
                }

                return (float) ($a * 100 + $b);
            }
        }

        return null;
    }
}
