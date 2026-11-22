<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('instagram', 500)->nullable();
            $table->string('facebook', 500)->nullable();
            $table->string('tiktok', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_contacts');
    }
};
