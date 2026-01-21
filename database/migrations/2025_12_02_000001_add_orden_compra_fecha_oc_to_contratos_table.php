<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            if (!Schema::hasColumn('contratos', 'orden_compra')) {
                $table->string('orden_compra', 50)->nullable()->after('id_licitacion');
            }
            if (!Schema::hasColumn('contratos', 'fecha_oc')) {
                $table->date('fecha_oc')->nullable()->after('orden_compra');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            if (Schema::hasColumn('contratos', 'fecha_oc')) {
                $table->dropColumn('fecha_oc');
            }
            if (Schema::hasColumn('contratos', 'orden_compra')) {
                $table->dropColumn('orden_compra');
            }
        });
    }
};

