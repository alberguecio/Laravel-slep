<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('item_montos_configuracion')) {
            Schema::create('item_montos_configuracion', function (Blueprint $table) {
                $table->id();
                $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
                $table->foreignId('monto_configuracion_id')->constrained('montos_configuracion')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['item_id', 'monto_configuracion_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('item_montos_configuracion');
    }
};

