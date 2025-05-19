<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('two_factor_auths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code')->nullable(); // Код 2FA
            $table->timestamp('code_expires_at')->nullable(); // Время истечения кода
            $table->boolean('is_enabled')->default(false); // Статус 2FA
            $table->string('client_identifier')->nullable(); // Идентификатор клиента (IP или браузер)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_auths');
    }
};
