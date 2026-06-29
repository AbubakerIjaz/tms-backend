<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesListingFilters;
use App\Models\Order;
use App\Models\OrderImage;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Services\NotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderController extends ShopController
{
    use AppliesListingFilters;

    private const ORDER_LIST_RELATIONS = [
        'client:id,name,phone',
        'design:id,name,image_path',
        'garmentType:id,name',
        'items.design:id,name',
        'items.garmentType:id,name',
    ];

    private const ORDER_RELATIONS = [
        'client:id,name,phone',
        'design:id,name,image_path',
        'garmentType:id,name',
        'images',
        'items.design:id,name,image_path',
        'items.garmentType:id,name',
    ];

    public function index(Request $request): JsonResponse
    {
        $shopId = $this->shopId($request);
        $status = $request->query('status');
        $paymentStatus = $request->query('payment_status');
        $search = $request->query('search');
        $perPage = $this->listingPerPage($request);

        $query = Order::forShop($shopId)
            ->with(self::ORDER_LIST_RELATIONS)
            ->withCount('items');

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
        $data = $this->validateOrderData($request);

        $shopId = $this->shopId($request);
        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $totalAmount = (float) $data['total_amount'];
        $items = $this->parseItems($request) ?? [];
        $this->validateOrderItems($items);
        $images = $request->file('images', []);
        $recordPayment = $request->boolean('record_payment');
        unset($data['record_payment'], $data['items'], $data['images']);

        $order = DB::transaction(function () use ($data, $shopId, $paidAmount, $totalAmount, $items, $images, $recordPayment) {
            $orderNumber = $this->generateOrderNumber($shopId);

            $primaryDesignId = $data['design_id'] ?? ($items[0]['design_id'] ?? null);
            $primaryGarmentTypeId = $data['garment_type_id'] ?? ($items[0]['garment_type_id'] ?? null);

            $order = Order::create([
                ...$data,
                'design_id' => $primaryDesignId,
                'garment_type_id' => $primaryGarmentTypeId,
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

            $this->syncOrderItems($order, $items);
            $this->storeOrderImages($order, $images);

            if ($recordPayment && $paidAmount > 0) {
                $transaction = Transaction::create([
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

                NotificationDispatcher::transactionCreated($transaction);
            }

            return $order;
        });

        NotificationDispatcher::orderEvent($order, 'created');

        return response()->json($order->load(self::ORDER_RELATIONS), 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize($request, $order);

        return response()->json($order->load(self::ORDER_RELATIONS));
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
            'items' => ['nullable'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
        ]);

        $items = $this->parseItems($request);
        $this->validateOrderItems($items);
        $images = $request->file('images', []);
        unset($data['items'], $data['images']);

        $total = (float) ($data['total_amount'] ?? $order->total_amount);
        $paid = (float) ($data['paid_amount'] ?? $order->paid_amount);
        $data['payment_status'] = Order::syncPaymentStatus(
            $total,
            $paid,
            $data['payment_status'] ?? $order->payment_status
        );

        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $data, $items, $images) {
            $effectiveStatus = $data['status'] ?? $order->status;

            if ($items !== null) {
                $this->syncOrderItems($order, $items, $effectiveStatus);
                if (! isset($data['design_id']) && ! empty($items[0]['design_id']) && $effectiveStatus !== 'delivered') {
                    $data['design_id'] = $items[0]['design_id'];
                }
                if (! isset($data['garment_type_id']) && ! empty($items[0]['garment_type_id'])) {
                    $data['garment_type_id'] = $items[0]['garment_type_id'];
                }
            }

            if (! empty($images)) {
                $this->storeOrderImages($order, $images);
            }

            if ($effectiveStatus === 'delivered') {
                $data['design_id'] = null;
            }

            $order->update($data);

            if ($order->status === 'delivered') {
                $this->unlinkDesignsFromOrder($order);
            }
        });

        $order->refresh();

        if (isset($data['status']) && $data['status'] === 'ready' && $previousStatus !== 'ready') {
            NotificationDispatcher::orderEvent($order, 'ready');
        } elseif (! empty($data) || $items !== null || ! empty($images)) {
            NotificationDispatcher::orderEvent($order, 'updated');
        }

        return response()->json($order->load(self::ORDER_RELATIONS));
    }

    public function destroy(Request $request, Order $order): JsonResponse
    {
        $this->authorize($request, $order);

        foreach ($order->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

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

            $transaction = Transaction::create([
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

            NotificationDispatcher::transactionCreated($transaction);
        });

        $order->refresh();
        NotificationDispatcher::orderEvent($order, 'payment');

        return response()->json($order->fresh()->load(self::ORDER_RELATIONS));
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

        return response()->json($order->fresh()->load(self::ORDER_RELATIONS));
    }

  /**
   * @return array<string, mixed>
   */
    private function validateOrderData(Request $request): array
    {
        return $request->validate([
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
            'items' => ['nullable'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],
        ]);
    }

    /**
     * @param  list<array{design_id?: int|null, garment_type_id?: int|null, label?: string|null, notes?: string|null}>|null  $items
     */
    private function validateOrderItems(?array $items): void
    {
        if ($items === null) {
            return;
        }

        validator(['items' => $items], [
            'items' => ['array'],
            'items.*.design_id' => ['nullable', 'exists:designs,id'],
            'items.*.garment_type_id' => ['nullable', 'exists:garment_types,id'],
            'items.*.label' => ['nullable', 'string', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
        ])->validate();
    }

    /**
     * @return list<array{design_id?: int|null, garment_type_id?: int|null, label?: string|null, notes?: string|null}>|null
     */
    private function parseItems(Request $request): ?array
    {
        if (! $request->has('items')) {
            return null;
        }

        $raw = $request->input('items');

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->filter(fn ($item) => is_array($item) && (
                ! empty($item['design_id']) ||
                ! empty($item['garment_type_id']) ||
                ! empty(trim((string) ($item['label'] ?? '')))
            ))
            ->values()
            ->all();
    }

    /**
     * @param  list<array{design_id?: int|null, garment_type_id?: int|null, label?: string|null, notes?: string|null}>  $items
     */
    private function syncOrderItems(Order $order, array $items, ?string $status = null): void
    {
        $status = $status ?? $order->status;
        $stripDesign = $status === 'delivered';

        $order->items()->delete();

        foreach ($items as $index => $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'design_id' => $stripDesign ? null : ($item['design_id'] ?? null),
                'garment_type_id' => $item['garment_type_id'] ?? null,
                'label' => $item['label'] ?? ('Suit '.($index + 1)),
                'notes' => $item['notes'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    private function unlinkDesignsFromOrder(Order $order): void
    {
        $order->items()->whereNotNull('design_id')->update(['design_id' => null]);

        if ($order->design_id !== null) {
            $order->update(['design_id' => null]);
        }
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $images
     */
    private function storeOrderImages(Order $order, array $images): void
    {
        $sort = (int) $order->images()->max('sort_order') + 1;

        foreach ($images as $file) {
            if (! $file) {
                continue;
            }

            OrderImage::create([
                'order_id' => $order->id,
                'image_path' => $file->store('orders', 'public'),
                'sort_order' => $sort++,
            ]);
        }
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
