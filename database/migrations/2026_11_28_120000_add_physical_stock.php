<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_units')->default(0)->after('price_cents');
        });

        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedSmallInteger('units');
            $table->timestamps();

            $table->unique(['combo_id', 'product_id']);
        });

        Schema::create('order_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('units');
            $table->timestamps();

            $table->unique(['order_id', 'product_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_restored_at')->nullable()->after('version');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_restored_at');
        });

        Schema::dropIfExists('order_stock_movements');
        Schema::dropIfExists('product_components');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock_units');
        });
    }
};
