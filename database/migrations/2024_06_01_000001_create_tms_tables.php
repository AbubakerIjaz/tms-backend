<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['tailor', 'boutique', 'both'])->default('tailor');
            $table->string('logo_path')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('currency', 10)->default('PKR');
            $table->enum('measurement_unit', ['inch', 'cm'])->default('inch');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->enum('role', ['admin', 'staff'])->default('admin')->after('password');
            $table->string('phone')->nullable()->after('role');
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['shop_id', 'name']);
        });

        Schema::create('garment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('measurement_fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('client_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('garment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->json('measurements');
            $table->text('notes')->nullable();
            $table->date('measured_at');
            $table->timestamps();
        });

        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('garment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['shop_id', 'slug']);
        });

        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('garment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number');
            $table->enum('status', ['pending', 'in_progress', 'ready', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('order_date');
            $table->date('due_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->json('measurements_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['shop_id', 'order_number']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->string('category')->nullable();
            $table->enum('payment_method', ['cash', 'card', 'bank', 'other'])->default('cash');
            $table->date('transaction_date');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['shop_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('designs');
        Schema::dropIfExists('client_measurements');
        Schema::dropIfExists('garment_types');
        Schema::dropIfExists('clients');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shop_id');
            $table->dropColumn(['role', 'phone']);
        });
        Schema::dropIfExists('shops');
    }
};
