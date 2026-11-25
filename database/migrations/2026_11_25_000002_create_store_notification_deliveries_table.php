<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->unsignedBigInteger('record_id');
            $table->timestamp('queued_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['kind', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_notification_deliveries');
    }
};
