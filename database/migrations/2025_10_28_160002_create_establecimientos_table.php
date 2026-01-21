<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimientos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->foreignId('comuna_id')->nullable()->constrained('comunas');
            $table->string('rbd', 20)->nullable();
            $table->decimal('subvencion_mantenimiento', 15, 2)->default(0);
            $table->decimal('aporte_subvencion_general', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimientos');
    }
};

