<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Transaction — {{ $shop->name }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #1e293b; background: #f8fafc; margin: 0; padding: 20px; }
        .container { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.07); }
        .header { padding: 24px; color: #fff; }
        .header.income { background: linear-gradient(135deg, #059669, #10b981); }
        .header.expense { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .header h1 { margin: 0 0 4px; font-size: 20px; }
        .header p { margin: 0; opacity: 0.9; font-size: 14px; }
        .amount-big { font-size: 28px; font-weight: 700; margin-top: 8px; }
        .section { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; }
        .section:last-child { border-bottom: none; }
        .section h2 { margin: 0 0 12px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        td { padding: 8px 0; vertical-align: top; }
        td.label { color: #64748b; width: 40%; }
        td.value { font-weight: 500; }
        .footer { padding: 16px 24px; background: #f8fafc; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $transaction->type }}">
            <h1>{{ $transaction->type === 'income' ? 'Income Received' : 'Expense Recorded' }}</h1>
            <p>{{ $shop->name }}</p>
            <div class="amount-big">{{ $shop->currency }} {{ number_format((float) $transaction->amount, 2) }}</div>
        </div>

        <div class="section">
            <h2>Transaction Details</h2>
            <table>
                <tr><td class="label">Type</td><td class="value">{{ ucfirst($transaction->type) }}</td></tr>
                <tr><td class="label">Amount</td><td class="value">{{ $shop->currency }} {{ number_format((float) $transaction->amount, 2) }}</td></tr>
                <tr><td class="label">Description</td><td class="value">{{ $transaction->description }}</td></tr>
                @if($transaction->category)
                <tr><td class="label">Category</td><td class="value">{{ $transaction->category }}</td></tr>
                @endif
                <tr><td class="label">Payment Method</td><td class="value">{{ ucfirst($transaction->payment_method) }}</td></tr>
                <tr><td class="label">Date</td><td class="value">{{ $transaction->transaction_date?->format('d M Y') }}</td></tr>
                @if($transaction->notes)
                <tr><td class="label">Notes</td><td class="value">{{ $transaction->notes }}</td></tr>
                @endif
            </table>
        </div>

        @if($client)
        <div class="section">
            <h2>Linked Customer</h2>
            <table>
                <tr><td class="label">Name</td><td class="value">{{ $client->name }}</td></tr>
                @if($client->phone)
                <tr><td class="label">Phone</td><td class="value">{{ $client->phone }}</td></tr>
                @endif
                @if($client->email)
                <tr><td class="label">Email</td><td class="value">{{ $client->email }}</td></tr>
                @endif
            </table>
        </div>
        @endif

        @if($order)
        <div class="section">
            <h2>Linked Order</h2>
            <table>
                <tr><td class="label">Order Number</td><td class="value">{{ $order->order_number }}</td></tr>
                <tr><td class="label">Status</td><td class="value">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td></tr>
                <tr><td class="label">Total</td><td class="value">{{ $shop->currency }} {{ number_format((float) $order->total_amount, 2) }}</td></tr>
                <tr><td class="label">Paid</td><td class="value">{{ $shop->currency }} {{ number_format((float) $order->paid_amount, 2) }}</td></tr>
            </table>
        </div>
        @endif

        <div class="footer">
            Sent by {{ $shop->name }} Tailor Management System — Accounts<br>
            {{ now()->format('d M Y, h:i A') }}
        </div>
    </div>
</body>
</html>
