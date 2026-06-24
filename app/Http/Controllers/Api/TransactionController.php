<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesListingFilters;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends ShopController
{
    use AppliesListingFilters;

    public function index(Request $request): JsonResponse
    {
        $shopId = $this->shopId($request);
        $type = $request->query('type');
        $search = $this->searchTerm($request);
        $perPage = $this->listingPerPage($request);

        $query = Transaction::forShop($shopId)
            ->with(['client:id,name', 'order:id,order_number']);

        if ($type) {
            $query->where('type', $type);
        }

        $this->applyColumnSearch($query, $search, ['description', 'category', 'notes']);

        $this->applyDateRangeFilter($query, $request, 'transaction_date');

        $paginated = $query->latest('transaction_date')->paginate($perPage);

        $summaryQuery = Transaction::forShop($shopId);
        if ($type) {
            $summaryQuery->where('type', $type);
        }
        $this->applyColumnSearch($summaryQuery, $search, ['description', 'category', 'notes']);
        $this->applyDateRangeFilter($summaryQuery, $request, 'transaction_date');

        $income = (clone $summaryQuery)->where('type', 'income')->sum('amount');
        $expense = (clone $summaryQuery)->where('type', 'expense')->sum('amount');

        return response()->json([
            ...$paginated->toArray(),
            'summary' => [
                'income' => (float) $income,
                'expense' => (float) $expense,
                'balance' => (float) $income - (float) $expense,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'in:cash,card,bank,other'],
            'transaction_date' => ['required', 'date'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $transaction = Transaction::create([
            ...$data,
            'shop_id' => $this->shopId($request),
            'payment_method' => $data['payment_method'] ?? 'cash',
        ]);

        return response()->json($transaction->load(['client:id,name', 'order:id,order_number']), 201);
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize($request, $transaction);

        $data = $request->validate([
            'type' => ['sometimes', 'in:income,expense'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'description' => ['sometimes', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'in:cash,card,bank,other'],
            'transaction_date' => ['sometimes', 'date'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $transaction->update($data);

        return response()->json($transaction->load(['client:id,name', 'order:id,order_number']));
    }

    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize($request, $transaction);
        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted.']);
    }

    private function authorize(Request $request, Transaction $transaction): void
    {
        abort_if($transaction->shop_id !== $this->shopId($request), 404);
    }
}
