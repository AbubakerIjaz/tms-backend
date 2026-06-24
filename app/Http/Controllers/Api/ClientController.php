<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AppliesListingFilters;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends ShopController
{
    use AppliesListingFilters;

    public function index(Request $request): JsonResponse
    {
        $shopId = $this->shopId($request);
        $search = trim((string) $request->query('search', ''));
        $perPage = $this->listingPerPage($request);
        $sort = $request->query('sort', 'latest');

        $query = Client::forShop($shopId)
            ->withCount(['orders', 'stitchingSizes', 'measurements']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%")
                    ->orWhere('notes', 'ilike', "%{$search}%");
            });
        }

        $this->applyDateRangeFilter($query, $request, 'created_at');

        $query = match ($sort) {
            'name' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            default => $query->latest(),
        };

        return response()->json($query->paginate($perPage));
    }

    public function export(Request $request): StreamedResponse
    {
        $shopId = $this->shopId($request);
        $search = trim((string) $request->query('search', ''));

        $query = Client::forShop($shopId)
            ->with(['stitchingSizes' => fn ($q) => $q->latest('measured_at')])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%");
            });
        }

        $this->applyDateRangeFilter($query, $request, 'created_at');

        $clients = $query->get();
        $filename = 'clients-measurements-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($clients) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, [
                'Client Name', 'Phone', 'Email', 'Gender', 'Address', 'Client Notes',
                'Measurement Label', 'Measured Date', 'Standard Size', 'Measurements', 'Measurement Notes',
            ]);

            foreach ($clients as $client) {
                if ($client->stitchingSizes->isEmpty()) {
                    fputcsv($out, [
                        $client->name,
                        $client->phone,
                        $client->email,
                        $client->gender,
                        $client->address,
                        $client->notes,
                        '', '', '', '', '',
                    ]);

                    continue;
                }

                foreach ($client->stitchingSizes as $size) {
                    fputcsv($out, [
                        $client->name,
                        $client->phone,
                        $client->email,
                        $client->gender,
                        $client->address,
                        $client->notes,
                        $size->label,
                        $size->measured_at?->format('Y-m-d'),
                        $size->standard_size,
                        $this->formatSectionsForExport($size->sections ?? []),
                        $size->notes,
                    ]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function formatSectionsForExport(array $sections): string
    {
        $parts = [];

        foreach ($sections as $section) {
            $name = $section['name'] ?? 'Measurements';
            $fields = [];

            foreach ($section['measurements'] ?? [] as $key => $value) {
                $fields[] = "{$key}={$value}";
            }

            if ($fields) {
                $parts[] = $name.': '.implode(', ', $fields);
            }
        }

        return implode(' | ', $parts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:male,female,other'],
            'notes' => ['nullable', 'string'],
        ]);

        $client = Client::create([
            ...$data,
            'shop_id' => $this->shopId($request),
        ]);

        return response()->json($client, 201);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClient($request, $client);

        $client->load([
            'stitchingSizes' => fn ($q) => $q->latest('measured_at'),
            'orders' => fn ($q) => $q->with(['design:id,name', 'garmentType:id,name'])->latest()->limit(10),
        ]);

        return response()->json($client);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClient($request, $client);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:male,female,other'],
            'notes' => ['nullable', 'string'],
        ]);

        $client->update($data);

        return response()->json($client);
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClient($request, $client);
        $client->delete();

        return response()->json(['message' => 'Client deleted.']);
    }

    private function authorizeClient(Request $request, Client $client): void
    {
        abort_if($client->shop_id !== $this->shopId($request), 404);
    }
}
