<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->string('archivo_contrato', 255)->nullable()->after('observaciones');
            $table->string('archivo_bases', 255)->nullable()->after('archivo_contrato');
            $table->string('archivo_oferta', 255)->nullable()->after('archivo_bases');
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropColumn(['archivo_contrato', 'archivo_bases', 'archivo_oferta']);
        });
    }
};

