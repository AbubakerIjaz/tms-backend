<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesListingFilters;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends ShopController
{
    use AppliesListingFilters;

    public function index(Request $request): JsonResponse
    {
        $shopId = $this->shopId($request);
        $status = $request->query('status');
        $paymentStatus = $request->query('payment_status');
        $search = $request->query('search');
        $perPage = $this->listingPerPage($request);

        $query = Order::forShop($shopId)
            ->with(['client:id,name,phone', 'design:id,name', 'garmentType:id,name']);

        if ($status) {
            $statuses = array_filter(explode(',', $status));
            if (count($statuses) > 1) {
                $query->whereIn('status', $statuses);
            } else {
                $query->where('status', $statuses[0] ?? $status);
            }
        }
        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'ilike', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'ilike', "%{$search}%"));
            });
        }

        $this->applyDateRangeFilter($query, $request, 'order_date');

        return response()->json($query->latest('order_date')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'design_id' => ['nullable', 'exists:designs,id'],
            'garment_type_id' => ['nullable', 'exists:garment_types,id'],
            'status' => ['nullable', 'in:pending,in_progress,ready,delivered,cancelled'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', 'in:paid,pending'],
            'order_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'measurements_snapshot' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'record_payment' => ['boolean'],
        ]);

        $shopId = $this->shopId($request);
        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $totalAmount = (float) $data['total_amount'];

        $order = DB::transaction(function () use ($data, $shopId, $paidAmount, $totalAmount) {
            $orderNumber = $this->generateOrderNumber($shopId);

            $order = Order::create([
                ...$data,
                'shop_id' => $shopId,
                'order_number' => $orderNumber,
                'status' => $data['status'] ?? 'pending',
                'paid_amount' => $paidAmount,
                'payment_status' => Order::syncPaymentStatus(
                    $totalAmount,
                    $paidAmount,
                    $data['payment_status'] ?? null
                ),
            ]);

            if (! empty($data['record_payment']) && $paidAmount > 0) {
                Transaction::create([
                    'shop_id' => $shopId,
                    'type' => 'income',
                    'amount' => $paidAmount,
                    'description' => "Payment for order {$orderNumber}",
                    'category' => 'Order Payment',
                    'payment_method' => 'cash',
                    'transaction_date' => $data['order_date'],
                    'client_id' => $data['client_id'],
                    'order_id' => $order->id,
                ]);
            }

            return $order;
        });

        return response()->json(
            $order->load(['client:id,name,phone', 'design:id,name', 'garmentType:id,name']),
            201
        );
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize($request, $order);

        return response()->json(
            $order->load(['client', 'design', 'garmentType'])
        );
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $this->authorize($request, $order);

        $data = $request->validate([
            'design_id' => ['nullable', 'exists:designs,id'],
            'garment_type_id' => ['nullable', 'exists:garment_types,id'],
            'status' => ['sometimes', 'in:pending,in_progress,ready,delivered,cancelled'],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'paid_amount' => ['sometimes', 'numeric', 'min:0'],
            'payment_status' => ['sometimes', 'in:paid,pending'],
            'order_date' => ['sometimes', 'date'],
            'due_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'measurements_snapshot' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $total = (float) ($data['total_amount'] ?? $order->total_amount);
        $paid = (float) ($data['paid_amount'] ?? $order->paid_amount);
        $data['payment_status'] = Order::syncPaymentStatus(
            $total,
            $paid,
            $data['payment_status'] ?? $order->payment_status
        );

        $order->update($data);

        return response()->json(
            $order->load(['client:id,name,phone', 'design:id,name', 'garmentType:id,name'])
        );
    }

    public function destroy(Request $request, Order $order): JsonResponse
    {
        $this->authorize($request, $order);
        $order->delete();

        return response()->json(['message' => 'Order deleted.']);
    }

    public function recordPayment(Request $request, Order $order): JsonResponse
    {
        $this->authorize($request, $order);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'in:cash,card,bank,other'],
            'transaction_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $amount = min((float) $data['amount'], $order->balance);

        DB::transaction(function () use ($order, $data, $amount) {
            $order->increment('paid_amount', $amount);
            $order->refresh();

            $order->update([
                'payment_status' => Order::syncPaymentStatus(
                    (float) $order->total_amount,
                    (float) $order->paid_amount
                ),
            ]);

            Transaction::create([
                'shop_id' => $order->shop_id,
                'type' => 'income',
                'amount' => $amount,
                'description' => "Payment for order {$order->order_number}",
                'category' => 'Order Payment',
                'payment_method' => $data['payment_method'] ?? 'cash',
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'client_id' => $order->client_id,
                'order_id' => $order->id,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return response()->json($order->fresh()->load(['client:id,name,phone', 'design:id,name']));
    }

    public function updatePaymentStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorize($request, $order);

        $data = $request->validate([
            'payment_status' => ['required', 'in:paid,pending'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $updates = ['payment_status' => $data['payment_status']];

        if (array_key_exists('paid_amount', $data)) {
            $updates['paid_amount'] = $data['paid_amount'];
        } elseif ($data['payment_status'] === 'paid') {
            $updates['paid_amount'] = $order->total_amount;
        } elseif ($data['payment_status'] === 'pending') {
            $updates['paid_amount'] = 0;
        }

        $order->update($updates);

        return response()->json($order->fresh()->load(['client:id,name,phone', 'design:id,name']));
    }

    private function generateOrderNumber(int $shopId): string
    {
        $count = Order::forShop($shopId)->count() + 1;

        return 'ORD-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function authorize(Request $request, Order $order): void
    {
        abort_if($order->shop_id !== $this->shopId($request), 404);
    }
}
