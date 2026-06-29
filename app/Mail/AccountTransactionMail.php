<?php

namespace App\Mail;

use App\Models\Shop;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountTransactionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public Shop $shop,
    ) {}

    public function envelope(): Envelope
    {
        $typeLabel = $this->transaction->type === 'income' ? 'Income' : 'Expense';

        return new Envelope(
            subject: "[{$this->shop->name}] {$typeLabel} — {$this->shop->currency} ".number_format((float) $this->transaction->amount, 2),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-transaction',
            with: [
                'transaction' => $this->transaction,
                'shop' => $this->shop,
                'client' => $this->transaction->client,
                'order' => $this->transaction->order,
            ],
        );
    }
}
