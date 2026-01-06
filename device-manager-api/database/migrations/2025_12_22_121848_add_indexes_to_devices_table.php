<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Índice para filtros por user_id (vai ser muito usado)
            $table->index('user_id');
            
            // Índice composto para filtros comuns
            $table->index(['user_id', 'in_use']);
            $table->index(['user_id', 'location']);
            $table->index(['user_id', 'purchase_date']);
            
            // Índice para soft delete
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['user_id', 'in_use']);
            $table->dropIndex(['user_id', 'location']);
            $table->dropIndex(['user_id', 'purchase_date']);
            $table->dropIndex(['deleted_at']);
        });
    }
};