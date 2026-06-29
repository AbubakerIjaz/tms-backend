<?php

namespace App\Support;

use App\Models\Shop;

class ShopSettings
{
    public const MODULE_KEYS = [
        'module_clients',
        'module_orders',
        'module_designs',
        'module_garment_types',
        'module_gallery',
        'module_categories',
        'module_accounts',
        'module_measurements',
        'module_voice_measurements',
    ];

    /** @return array<string, mixed> */
    public static function defaultsForType(string $type): array
    {
        $modules = array_fill_keys(self::MODULE_KEYS, true);

        if ($type === 'boutique') {
            $modules['module_garment_types'] = false;
            $modules['module_measurements'] = false;
            $modules['module_voice_measurements'] = false;
        }

        return array_merge($modules, [
            'urdu_labels_enabled' => false,
            'email_enabled' => false,
            'email_admin_address' => '',
            'email_on_order_created' => true,
            'email_on_order_updated' => true,
            'email_on_order_ready' => true,
            'email_on_payment_received' => true,
            'email_on_transaction' => true,
        ]);
    }

    public static function get(Shop $shop, string $key, mixed $default = null): mixed
    {
        $settings = $shop->settings ?? [];

        if (array_key_exists($key, $settings)) {
            return $settings[$key];
        }

        $defaults = self::defaultsForType($shop->type ?? 'tailor');

        return $defaults[$key] ?? $default;
    }

    public static function isTruthy(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }

    public static function moduleEnabled(Shop $shop, string $moduleKey): bool
    {
        return self::isTruthy(self::get($shop, $moduleKey, true));
    }

    public static function emailEnabled(Shop $shop): bool
    {
        return self::isTruthy(self::get($shop, 'email_enabled', false));
    }

    public static function adminEmail(Shop $shop): ?string
    {
        $override = trim((string) self::get($shop, 'email_admin_address', ''));
        if ($override !== '') {
            return $override;
        }

        $shopEmail = trim((string) ($shop->email ?? ''));

        return $shopEmail !== '' ? $shopEmail : null;
    }

    public static function shouldSendOrderEmail(Shop $shop, string $event): bool
    {
        if (! self::emailEnabled($shop) || ! self::adminEmail($shop)) {
            return false;
        }

        $key = match ($event) {
            'created' => 'email_on_order_created',
            'updated' => 'email_on_order_updated',
            'ready' => 'email_on_order_ready',
            'payment' => 'email_on_payment_received',
            default => null,
        };

        return $key ? self::isTruthy(self::get($shop, $key, true)) : false;
    }

    public static function shouldSendTransactionEmail(Shop $shop): bool
    {
        return self::emailEnabled($shop)
            && self::adminEmail($shop)
            && self::isTruthy(self::get($shop, 'email_on_transaction', true));
    }
}
