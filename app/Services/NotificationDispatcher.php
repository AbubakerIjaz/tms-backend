<?php

namespace App\Services;

use App\Jobs\SendAccountNotificationJob;
use App\Jobs\SendOrderNotificationJob;
use App\Models\Order;
use App\Models\Shop;
use App\Models\Transaction;
use App\Support\ShopSettings;

class NotificationDispatcher
{
    public static function orderEvent(Order $order, string $event): void
    {
        $shop = Shop::find($order->shop_id);

        if (! $shop || ! ShopSettings::shouldSendOrderEmail($shop, $event)) {
            return;
        }

        SendOrderNotificationJob::dispatch($order->id, $event);
    }

    public static function transactionCreated(Transaction $transaction): void
    {
        $shop = Shop::find($transaction->shop_id);

        if (! $shop || ! ShopSettings::shouldSendTransactionEmail($shop)) {
            return;
        }

        SendAccountNotificationJob::dispatch($transaction->id);
    }
}
