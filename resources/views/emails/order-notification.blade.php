<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $eventLabel }} — {{ $order->order_number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #1e293b; background: #f8fafc; margin: 0; padding: 20px; }
        .container { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.07); }
        .header { background: linear-gradient(135deg, #4338ca, #6366f1); color: #fff; padding: 24px; }
        .header h1 { margin: 0 0 4px; font-size: 20px; }
        .header p { margin: 0; opacity: 0.9; font-size: 14px; }
        .badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-top: 8px; }
        .section { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; }
        .section:last-child { border-bottom: none; }
        .section h2 { margin: 0 0 12px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        td { padding: 8px 0; vertical-align: top; }
        td.label { color: #64748b; width: 40%; }
        td.value { font-weight: 500; }
        .size-block { background: #f1f5f9; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
        .size-block h3 { margin: 0 0 8px; font-size: 14px; color: #4338ca; }
        .measurements-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px 16px; font-size: 13px; }
        .measurements-grid span { color: #64748b; }
        .footer { padding: 16px 24px; background: #f8fafc; text-align: center; font-size: 12px; color: #94a3b8; }
        .amount { font-size: 18px; font-weight: 700; color: #059669; }
        .balance { color: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $eventLabel }}</h1>
            <p>{{ $shop->name }} — Order {{ $order->order_number }}</p>
            <span class="badge">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
        </div>

        <div class="section">
            <h2>Order Details</h2>
            <table>
                <tr><td class="label">Order Number</td><td class="value">{{ $order->order_number }}</td></tr>
                <tr><td class="label">Status</td><td class="value">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td></tr>
                <tr><td class="label">Order Date</td><td class="value">{{ $order->order_date?->format('d M Y') }}</td></tr>
                @if($order->due_date)
                <tr><td class="label">Due Date</td><td class="value">{{ $order->due_date->format('d M Y') }}</td></tr>
                @endif
                @if($order->design)
                <tr><td class="label">Design</td><td class="value">{{ $order->design->name }}</td></tr>
                @endif
                @if($order->garmentType)
                <tr><td class="label">Garment Type</td><td class="value">{{ $order->garmentType->name }}</td></tr>
                @endif
                <tr><td class="label">Total Amount</td><td class="value amount">{{ $shop->currency }} {{ number_format((float) $order->total_amount, 2) }}</td></tr>
                <tr><td class="label">Paid Amount</td><td class="value">{{ $shop->currency }} {{ number_format((float) $order->paid_amount, 2) }}</td></tr>
                <tr><td class="label">Balance</td><td class="value {{ $order->balance > 0 ? 'balance' : '' }}">{{ $shop->currency }} {{ number_format($order->balance, 2) }}</td></tr>
                <tr><td class="label">Payment Status</td><td class="value">{{ ucfirst($order->payment_status) }}</td></tr>
                @if($order->notes)
                <tr><td class="label">Notes</td><td class="value">{{ $order->notes }}</td></tr>
                @endif
            </table>
        </div>

        @if($client)
        <div class="section">
            <h2>Customer Details</h2>
            <table>
                <tr><td class="label">Name</td><td class="value">{{ $client->name }}</td></tr>
                @if($client->phone)
                <tr><td class="label">Phone</td><td class="value">{{ $client->phone }}</td></tr>
                @endif
                @if($client->email)
                <tr><td class="label">Email</td><td class="value">{{ $client->email }}</td></tr>
                @endif
                @if($client->address)
                <tr><td class="label">Address</td><td class="value">{{ $client->address }}</td></tr>
                @endif
                @if($client->gender)
                <tr><td class="label">Gender</td><td class="value">{{ ucfirst($client->gender) }}</td></tr>
                @endif
                @if($client->notes)
                <tr><td class="label">Customer Notes</td><td class="value">{{ $client->notes }}</td></tr>
                @endif
            </table>
        </div>
        @endif

        @if($order->measurements_snapshot && count($order->measurements_snapshot) > 0)
        <div class="section">
            <h2>Order Measurements Snapshot</h2>
            <div class="size-block">
                <div class="measurements-grid">
                    @foreach($order->measurements_snapshot as $key => $value)
                    <div><span>{{ $key }}:</span> <strong>{{ $value }} {{ $shop->measurement_unit }}</strong></div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if($stitchingSizes->isNotEmpty())
        <div class="section">
            <h2>Customer Stitching Sizes</h2>
            @foreach($stitchingSizes as $size)
            <div class="size-block">
                <h3>{{ $size->label ?? 'Size Record' }} @if($size->standard_size)({{ $size->standard_size }})@endif</h3>
                @if($size->sections)
                    @foreach($size->sections as $section)
                    <p style="margin: 8px 0 4px; font-weight: 600; font-size: 13px;">{{ $section['name'] ?? 'Section' }}</p>
                    <div class="measurements-grid">
                        @foreach(($section['measurements'] ?? []) as $key => $value)
                        <div><span>{{ $key }}:</span> <strong>{{ $value }} {{ $shop->measurement_unit }}</strong></div>
                        @endforeach
                    </div>
                    @endforeach
                @endif
                @if($size->notes)
                <p style="margin-top: 8px; font-size: 12px; color: #64748b;">{{ $size->notes }}</p>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @if($measurements->isNotEmpty())
        <div class="section">
            <h2>Customer Measurements</h2>
            @foreach($measurements as $measurement)
            <div class="size-block">
                <h3>{{ $measurement->label ?? ($measurement->garmentType?->name ?? 'Measurement') }}</h3>
                <div class="measurements-grid">
                    @foreach(($measurement->measurements ?? []) as $key => $value)
                    <div><span>{{ $key }}:</span> <strong>{{ $value }} {{ $shop->measurement_unit }}</strong></div>
                    @endforeach
                </div>
                @if($measurement->notes)
                <p style="margin-top: 8px; font-size: 12px; color: #64748b;">{{ $measurement->notes }}</p>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        <div class="footer">
            Sent by {{ $shop->name }} Tailor Management System<br>
            {{ now()->format('d M Y, h:i A') }}
        </div>
    </div>
</body>
</html>
