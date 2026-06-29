<?php

namespace App\Jobs;

use App\Mail\OrderNotificationMail;
use App\Models\Order;
use App\Models\Shop;
use App\Support\ShopSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendOrderNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $event,
    ) {}

    public function handle(): void
    {
        $order = Order::with([
            'client.stitchingSizes',
            'client.measurements.garmentType',
            'design',
            'garmentType',
        ])->find($this->orderId);

        if (! $order) {
            return;
        }

        $shop = Shop::find($order->shop_id);

        if (! $shop) {
            return;
        }

        $adminEmail = ShopSettings::adminEmail($shop);

        if (! $adminEmail) {
            return;
        }

        Mail::to($adminEmail)->send(new OrderNotificationMail($order, $shop, $this->event));
    }
}
