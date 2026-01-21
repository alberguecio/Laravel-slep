<?php

namespace App\Console\Commands;

use App\Models\Contrato;
use App\Models\Proyecto;
use Illuminate\Console\Command;

class ActualizarNumerosContratosConAnio extends Command
{
    protected $signature = 'contratos:actualizar-numeros-con-anio {--dry-run : Simula la operación sin realizar cambios}';
    protected $description = 'Actualiza los números de contrato existentes para incluir el año (formato: XXXX-YYYY)';

    public function handle()
    {
        $this->info('=== Actualizar Números de Contrato con Año ===');
        
        $contratos = Contrato::with('proyecto')->get();
        $contratosActualizar = [];
        
        foreach ($contratos as $contrato) {
            if (empty($contrato->numero_contrato)) {
                continue;
            }
            
            $numeroContrato = trim($contrato->numero_contrato);
            
            // Verificar si ya tiene el año al final (formato: XXXX-YYYY)
            if (preg_match('/-\d{4}$/', $numeroContrato)) {
                continue; // Ya tiene el año, no necesita actualización
            }
            
            // Determinar el año del contrato
            $anio = null;
            
            // 1. Intentar obtener el año del proyecto
            if ($contrato->proyecto && $contrato->proyecto->anio_ejecucion) {
                $anio = $contrato->proyecto->anio_ejecucion;
            }
            // 2. Si no, usar el año de fecha_inicio
            elseif ($contrato->fecha_inicio) {
                $anio = $contrato->fecha_inicio->format('Y');
            }
            // 3. Si no, usar el año de fecha_oc
            elseif ($contrato->fecha_oc) {
                $anio = $contrato->fecha_oc->format('Y');
            }
            // 4. Si no, usar el año de created_at
            else {
                $anio = $contrato->created_at->format('Y');
            }
            
            if ($anio) {
                $nuevoNumero = $numeroContrato . '-' . $anio;
                $contratosActualizar[] = [
                    'id' => $contrato->id,
                    'numero_actual' => $numeroContrato,
                    'numero_nuevo' => $nuevoNumero,
                    'anio' => $anio
                ];
            }
        }
        
        if (empty($contratosActualizar)) {
            $this->info('✓ Todos los contratos ya tienen el año en el número de contrato.');
            return Command::SUCCESS;
        }
        
        $this->info("Contratos a actualizar: " . count($contratosActualizar));
        $this->table(
            ['ID', 'Número Actual', 'Número Nuevo', 'Año'],
            array_map(function($c) {
                return [$c['id'], $c['numero_actual'], $c['numero_nuevo'], $c['anio']];
            }, $contratosActualizar)
        );
        
        if ($this->option('dry-run')) {
            $this->info('[DRY RUN] No se realizaron cambios. Ejecuta sin --dry-run para aplicar los cambios.');
            return Command::SUCCESS;
        }
        
        if (!$this->confirm('¿Deseas actualizar estos ' . count($contratosActualizar) . ' contratos?')) {
            $this->info('Operación cancelada.');
            return Command::CANCEL;
        }
        
        $this->info('Actualizando contratos...');
        $actualizados = 0;
        $errores = 0;
        
        foreach ($contratosActualizar as $item) {
            try {
                $contrato = Contrato::find($item['id']);
                if ($contrato) {
                    $contrato->numero_contrato = $item['numero_nuevo'];
                    $contrato->save();
                    $actualizados++;
                }
            } catch (\Exception $e) {
                $this->error("Error al actualizar contrato ID {$item['id']}: " . $e->getMessage());
                $errores++;
            }
        }
        
        $this->info("✓ Se actualizaron {$actualizados} contratos exitosamente.");
        if ($errores > 0) {
            $this->warn("⚠ Hubo {$errores} errores durante la actualización.");
        }
        
        $this->info('=== Operación completada ===');
        return Command::SUCCESS;
    }
}
