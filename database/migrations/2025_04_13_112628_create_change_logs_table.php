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
        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // Название сущности (User, Role, Permission)
            $table->unsignedBigInteger('entity_id'); // ID записи сущности
            $table->json('before')->nullable(); // Значение до мутации
            $table->json('after')->nullable(); // Значение после мутации
            $table->string('action'); // Действие (create, update, delete)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_logs');
    }
};
