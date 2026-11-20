<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('number')->unique();
            $table->uuid('checkout_key')->unique();
            $table->string('customer_name', 120);
            $table->string('customer_email');
            $table->string('customer_phone', 30);
            $table->text('notes')->nullable();
            $table->string('fulfillment_status')->default('pending')->index();
            $table->string('payment_status')->default('pending')->index();
            $table->unsignedBigInteger('total_cents');
            $table->timestamp('paid_at')->nullable()->index();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('total_cents');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
