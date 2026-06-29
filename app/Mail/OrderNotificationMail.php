<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public Shop $shop,
        public string $event,
    ) {}

    public function envelope(): Envelope
    {
        $eventLabel = match ($this->event) {
            'created' => 'New Order',
            'ready' => 'Order Ready',
            'payment' => 'Payment Received',
            default => 'Order Updated',
        };

        return new Envelope(
            subject: "[{$this->shop->name}] {$eventLabel} — {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-notification',
            with: [
                'order' => $this->order,
                'shop' => $this->shop,
                'client' => $this->order->client,
                'event' => $this->event,
                'eventLabel' => match ($this->event) {
                    'created' => 'New Order Placed',
                    'ready' => 'Order Ready for Delivery',
                    'payment' => 'Payment Received',
                    default => 'Order Updated',
                },
                'stitchingSizes' => $this->order->client?->stitchingSizes ?? collect(),
                'measurements' => $this->order->client?->measurements ?? collect(),
            ],
        );
    }
}
