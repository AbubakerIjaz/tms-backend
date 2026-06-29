<?php

namespace App\Jobs;

use App\Mail\AccountTransactionMail;
use App\Models\Shop;
use App\Models\Transaction;
use App\Support\ShopSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAccountNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $transactionId,
    ) {}

    public function handle(): void
    {
        $transaction = Transaction::with(['client', 'order'])->find($this->transactionId);

        if (! $transaction) {
            return;
        }

        $shop = Shop::find($transaction->shop_id);

        if (! $shop) {
            return;
        }

        $adminEmail = ShopSettings::adminEmail($shop);

        if (! $adminEmail) {
            return;
        }

        Mail::to($adminEmail)->send(new AccountTransactionMail($transaction, $shop));
    }
}
