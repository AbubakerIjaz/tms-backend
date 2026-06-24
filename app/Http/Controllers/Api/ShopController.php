<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

abstract class ShopController extends Controller
{
    protected function shopId(Request $request): int
    {
        /** @var User $user */
        $user = $request->user();

        return (int) $user->shop_id;
    }
}
