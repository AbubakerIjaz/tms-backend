<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('shop_name');
            $table->string('shop_type'); // tailor, boutique, both
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time', 10)->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('pending'); // pending, contacted, scheduled, closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
