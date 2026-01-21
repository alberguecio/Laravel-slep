<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('requerimientos')) {
            Schema::table('requerimientos', function (Blueprint $table) {
                if (!Schema::hasColumn('requerimientos', 'emergencia')) {
                    $table->boolean('emergencia')->default(false)->after('establecimiento_id');
                }
                if (!Schema::hasColumn('requerimientos', 'contrato_id')) {
                    $table->foreignId('contrato_id')->nullable()->after('emergencia')->constrained('contratos')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('requerimientos')) {
            Schema::table('requerimientos', function (Blueprint $table) {
                if (Schema::hasColumn('requerimientos', 'contrato_id')) {
                    $table->dropForeign(['contrato_id']);
                    $table->dropColumn('contrato_id');
                }
                if (Schema::hasColumn('requerimientos', 'emergencia')) {
                    $table->dropColumn('emergencia');
                }
            });
        }
    }
};

