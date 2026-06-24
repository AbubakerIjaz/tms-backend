<?php

namespace App\Http\Controllers\Api;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends ShopController
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
            'shop_name' => ['required', 'string', 'max:255'],
            'shop_type' => ['required', 'in:tailor,boutique,both'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $slug = Str::slug($data['shop_name']);
            $baseSlug = $slug;
            $counter = 1;
            while (Shop::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$counter++;
            }

            $shop = Shop::create([
                'name' => $data['shop_name'],
                'slug' => $slug,
                'type' => $data['shop_type'],
            ]);

            $user = User::create([
                'shop_id' => $shop->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'role' => 'admin',
            ]);

            $this->seedDefaults($shop);

            return [$shop, $user];
        });

        /** @var User $user */
        [$shop, $user] = $result;
        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user->load('shop')),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user->load('shop')),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->formatUser($request->user()->load('shop')),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'user' => $this->formatUser($user->fresh()->load('shop')),
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'shop' => $user->shop ? [
                'id' => $user->shop->id,
                'name' => $user->shop->name,
                'slug' => $user->shop->slug,
                'type' => $user->shop->type,
                'logo_url' => $user->shop->logo_url,
                'phone' => $user->shop->phone,
                'email' => $user->shop->email,
                'address' => $user->shop->address,
                'city' => $user->shop->city,
                'currency' => $user->shop->currency,
                'measurement_unit' => $user->shop->measurement_unit,
                'settings' => $user->shop->settings,
            ] : null,
        ];
    }

    private function seedDefaults(Shop $shop): void
    {
        $defaults = [
            [
                'name' => 'Suit',
                'measurement_fields' => [
                    ['key' => 'chest', 'label' => 'Chest'],
                    ['key' => 'waist', 'label' => 'Waist'],
                    ['key' => 'hips', 'label' => 'Hips'],
                    ['key' => 'shoulder', 'label' => 'Shoulder'],
                    ['key' => 'sleeve', 'label' => 'Sleeve'],
                    ['key' => 'length', 'label' => 'Length'],
                    ['key' => 'neck', 'label' => 'Neck'],
                ],
            ],
            [
                'name' => 'Shirt',
                'measurement_fields' => [
                    ['key' => 'chest', 'label' => 'Chest'],
                    ['key' => 'waist', 'label' => 'Waist'],
                    ['key' => 'shoulder', 'label' => 'Shoulder'],
                    ['key' => 'sleeve', 'label' => 'Sleeve'],
                    ['key' => 'length', 'label' => 'Length'],
                    ['key' => 'neck', 'label' => 'Neck'],
                ],
            ],
            [
                'name' => 'Trouser',
                'measurement_fields' => [
                    ['key' => 'waist', 'label' => 'Waist'],
                    ['key' => 'hips', 'label' => 'Hips'],
                    ['key' => 'inseam', 'label' => 'Inseam'],
                    ['key' => 'outseam', 'label' => 'Outseam'],
                    ['key' => 'thigh', 'label' => 'Thigh'],
                ],
            ],
            [
                'name' => 'Kameez',
                'measurement_fields' => [
                    ['key' => 'length', 'label' => 'Length'],
                    ['key' => 'shoulder', 'label' => 'Shoulder'],
                    ['key' => 'chest', 'label' => 'Chest'],
                    ['key' => 'waist', 'label' => 'Waist'],
                    ['key' => 'bottom', 'label' => 'Bottom'],
                    ['key' => 'sleeves', 'label' => 'Sleeves'],
                    ['key' => 'arm_hole', 'label' => 'Arm Hole'],
                    ['key' => 'cuff', 'label' => 'Cuff'],
                    ['key' => 'collar_size', 'label' => 'Collar Size'],
                ],
            ],
            [
                'name' => 'Shalwar',
                'measurement_fields' => [
                    ['key' => 'length', 'label' => 'Length'],
                    ['key' => 'bottom', 'label' => 'Bottom'],
                    ['key' => 'in_seam', 'label' => 'In Seam'],
                ],
            ],
            [
                'name' => 'Dress',
                'measurement_fields' => [
                    ['key' => 'bust', 'label' => 'Bust'],
                    ['key' => 'waist', 'label' => 'Waist'],
                    ['key' => 'hips', 'label' => 'Hips'],
                    ['key' => 'shoulder', 'label' => 'Shoulder'],
                    ['key' => 'length', 'label' => 'Length'],
                ],
            ],
        ];

        foreach ($defaults as $item) {
            $shop->garmentTypes()->create($item);
        }

        $categories = ['Formal', 'Casual', 'Wedding', 'Traditional', 'Kids'];
        foreach ($categories as $name) {
            $shop->categories()->create([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
        }
    }
}
