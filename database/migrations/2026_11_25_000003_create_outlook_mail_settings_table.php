<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlook_mail_settings', function (Blueprint $table) {
            $table->id();
            $table->string('from_address');
            $table->string('from_name');
            $table->string('recipient');
            $table->uuid('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlook_mail_settings');
    }
};
