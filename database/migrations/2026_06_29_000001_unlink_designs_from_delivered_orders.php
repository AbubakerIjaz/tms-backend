<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Order::query()
            ->where('status', 'delivered')
            ->each(function (Order $order) {
                DB::table('order_items')
                    ->where('order_id', $order->id)
                    ->whereNotNull('design_id')
                    ->update(['design_id' => null]);

                if ($order->design_id !== null) {
                    $order->update(['design_id' => null]);
                }
            });
    }

    public function down(): void
    {
        // Design links cannot be restored after delivery unlink.
    }
};
