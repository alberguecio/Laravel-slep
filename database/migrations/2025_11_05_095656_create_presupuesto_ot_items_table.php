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
        Schema::create('presupuesto_ot_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_ot_id')->constrained('presupuesto_ot')->onDelete('cascade');
            $table->integer('item');
            $table->string('partida', 500);
            $table->string('numero_partida', 50)->nullable();
            $table->string('unidad', 50);
            $table->decimal('cantidad', 15, 2);
            $table->decimal('precio', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuesto_ot_items');
    }
};
