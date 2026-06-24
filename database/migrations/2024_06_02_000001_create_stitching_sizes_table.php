<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stitching_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->enum('standard_size', ['S', 'M', 'L', 'XL'])->nullable();
            $table->json('sections');
            $table->text('notes')->nullable();
            $table->date('measured_at');
            $table->timestamps();
            $table->index(['shop_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stitching_sizes');
    }
};
