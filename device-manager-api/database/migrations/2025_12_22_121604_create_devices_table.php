<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id(); // id (chave primária)
            $table->string('name'); // nome do dispositivo
            $table->string('location'); // local onde está o dispositivo
            $table->date('purchase_date'); // data de compra
            $table->boolean('in_use')->default(false); // se está em uso
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // dono do dispositivo
            $table->timestamps(); // created_at e updated_at
            $table->softDeletes(); // deleted_at (para Soft Delete)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};