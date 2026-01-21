<?php

namespace App\Console\Commands;

use App\Models\Contrato;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarcarContratos2025Terminados extends Command
{
    protected $signature = 'contratos:marcar-2025-terminados 
                            {--dry-run : Solo mostrar qué contratos se actualizarían sin hacer cambios}
                            {--force : Ejecutar sin confirmación}';
    
    protected $description = 'Marca todos los contratos del año 2025 como terminados';

    public function handle()
    {
        $this->info("=== Marcar Contratos 2025 como Terminados ===\n");
        
        // Buscar contratos de 2025 por diferentes criterios
        $contratos2025 = Contrato::where(function($query) {
            $query->whereYear('fecha_inicio', 2025)
                  ->orWhereYear('fecha_oc', 2025)
                  ->orWhereYear('created_at', 2025);
        })->get();
        
        if ($contratos2025->isEmpty()) {
            $this->warn("No se encontraron contratos del año 2025.");
            return Command::SUCCESS;
        }
        
        // Filtrar solo los que no estén ya terminados
        $contratosParaActualizar = $contratos2025->filter(function($contrato) {
            return trim($contrato->estado ?? '') !== 'Terminado';
        });
        
        if ($contratosParaActualizar->isEmpty()) {
            $this->info("Todos los contratos de 2025 ya están marcados como terminados.");
            return Command::SUCCESS;
        }
        
        $this->info("Contratos encontrados del año 2025: " . $contratos2025->count());
        $this->info("Contratos a actualizar: " . $contratosParaActualizar->count() . "\n");
        
        // Mostrar lista de contratos a actualizar
        $this->table(
            ['ID', 'Nombre', 'Número', 'Estado Actual', 'Fecha Inicio', 'Fecha OC'],
            $contratosParaActualizar->map(function($contrato) {
                return [
                    $contrato->id,
                    $contrato->nombre_contrato ?? '-',
                    $contrato->numero_contrato ?? '-',
                    $contrato->estado ?? 'Sin estado',
                    $contrato->fecha_inicio ? $contrato->fecha_inicio->format('Y-m-d') : '-',
                    $contrato->fecha_oc ? $contrato->fecha_oc->format('Y-m-d') : '-'
                ];
            })->toArray()
        );
        
        if ($this->option('dry-run')) {
            $this->warn("\n[DRY RUN] No se realizaron cambios. Ejecuta sin --dry-run para aplicar los cambios.");
            return Command::SUCCESS;
        }
        
        // Confirmar antes de actualizar
        if (!$this->option('force')) {
            if (!$this->confirm("\n¿Deseas marcar estos " . $contratosParaActualizar->count() . " contratos como terminados?")) {
                $this->info("Operación cancelada.");
                return Command::SUCCESS;
            }
        }
        
        // Actualizar contratos
        $this->info("\nActualizando contratos...");
        
        DB::beginTransaction();
        try {
            $actualizados = 0;
            foreach ($contratosParaActualizar as $contrato) {
                $contrato->estado = 'Terminado';
                $contrato->save();
                $actualizados++;
            }
            
            DB::commit();
            
            $this->info("✓ Se actualizaron {$actualizados} contratos exitosamente.");
            $this->info("\n=== Operación completada ===");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al actualizar contratos: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}




