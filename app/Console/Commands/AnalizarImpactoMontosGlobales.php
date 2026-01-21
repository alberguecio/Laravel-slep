<?php

namespace App\Console\Commands;

use App\Models\MontoConfiguracion;
use App\Models\Proyecto;
use App\Models\Contrato;
use App\Models\Item;
use Illuminate\Console\Command;

class AnalizarImpactoMontosGlobales extends Command
{
    protected $signature = 'presupuesto:analizar-impacto {--anio= : Año específico para analizar}';
    protected $description = 'Analiza el impacto de cambiar los montos globales en presupuesto sobre proyectos y contratos';

    public function handle()
    {
        $this->info('=== Análisis de Impacto de Montos Globales ===');
        $this->newLine();
        
        $anioFiltro = $this->option('anio') ? (int)$this->option('anio') : null;
        
        if ($anioFiltro) {
            $this->info("Analizando para el año: {$anioFiltro}");
        } else {
            $this->info("Analizando todos los años");
        }
        $this->newLine();
        
        // 1. Mostrar montos globales actuales
        $this->info('📊 MONTOS GLOBALES ACTUALES:');
        $this->line('─────────────────────────────────────────────────────────');
        $montosGlobales = MontoConfiguracion::orderBy('orden')->get();
        $totalGlobal = 0;
        foreach ($montosGlobales as $monto) {
            $this->line(sprintf('  %-40s $ %s', $monto->nombre, number_format($monto->monto, 0, ',', '.')));
            $totalGlobal += $monto->monto;
        }
        $this->line('─────────────────────────────────────────────────────────');
        $this->line(sprintf('  %-40s $ %s', 'TOTAL GLOBALES', number_format($totalGlobal, 0, ',', '.')));
        $this->newLine();
        
        // 2. Analizar por Item
        $items = Item::with('montosConfiguracion')->get();
        
        $this->info('📋 ANÁLISIS POR ITEM:');
        $this->line('─────────────────────────────────────────────────────────');
        
        foreach ($items as $item) {
            // Monto presupuesto del item (suma de montos asociados)
            $montoPresupuesto = $item->montosConfiguracion->sum('monto') ?? 0;
            
            // Monto asignado en proyectos del item
            $queryProyectos = Proyecto::where('item_id', $item->id);
            if ($anioFiltro) {
                $queryProyectos->where('anio_ejecucion', $anioFiltro);
            }
            $montoAsignado = $queryProyectos->sum('monto_asignado') ?? 0;
            
            // Monto real en contratos del item
            $queryContratos = Contrato::whereHas('proyecto', function($q) use ($item) {
                $q->where('item_id', $item->id);
            });
            if ($anioFiltro) {
                $queryContratos->where(function($q) use ($anioFiltro) {
                    $q->whereYear('fecha_inicio', $anioFiltro)
                      ->orWhereYear('fecha_oc', $anioFiltro)
                      ->orWhereYear('created_at', $anioFiltro);
                });
            }
            $montoContratado = $queryContratos->sum('monto_real') ?? 0;
            
            // Saldo disponible
            $saldoDisponible = $montoPresupuesto - $montoAsignado;
            
            // Análisis
            $estado = '✅ OK';
            $advertencias = [];
            
            if ($montoAsignado > $montoPresupuesto) {
                $estado = '⚠️  EXCEDE';
                $advertencias[] = "Los proyectos tienen más asignado ({$this->formato($montoAsignado)}) que el presupuesto disponible ({$this->formato($montoPresupuesto)})";
            }
            
            if ($montoContratado > $montoAsignado) {
                $estado = '⚠️  EXCEDE';
                $advertencias[] = "Los contratos tienen más monto ({$this->formato($montoContratado)}) que lo asignado a proyectos ({$this->formato($montoAsignado)})";
            }
            
            if ($saldoDisponible < 0) {
                $estado = '❌ NEGATIVO';
                $advertencias[] = "El saldo disponible es negativo ({$this->formato($saldoDisponible)})";
            }
            
            $this->line("📌 {$item->nombre}");
            $this->line("   Presupuesto:     {$this->formato($montoPresupuesto)}");
            $this->line("   Asignado:        {$this->formato($montoAsignado)}");
            $this->line("   Contratado:      {$this->formato($montoContratado)}");
            $this->line("   Saldo:           {$this->formato($saldoDisponible)} {$estado}");
            
            if (!empty($advertencias)) {
                foreach ($advertencias as $adv) {
                    $this->warn("   ⚠️  {$adv}");
                }
            }
            $this->newLine();
        }
        
        // 3. Análisis específico de Mantención
        $this->info('🔧 ANÁLISIS ESPECÍFICO: MANTENCIÓN');
        $this->line('─────────────────────────────────────────────────────────');
        
        $montoGeneral = MontoConfiguracion::where('codigo', 'subvencion_general')->first();
        $montoMantencion = MontoConfiguracion::where('codigo', 'subvencion_mantenimiento')->first();
        $montoVTF = MontoConfiguracion::where('codigo', 'mantencion_vtf')->first();
        
        $presupuestoMantencion = ($montoGeneral->monto ?? 0) + ($montoMantencion->monto ?? 0) + ($montoVTF->monto ?? 0);
        
        $itemsMantencion = Item::get()->filter(function($item) {
            $nombre = mb_strtolower($item->nombre ?? '');
            $nombreNormalizado = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombre);
            return strpos($nombreNormalizado, 'convenio') !== false && strpos($nombreNormalizado, 'mantencion') !== false;
        });
        
        $itemIds = $itemsMantencion->pluck('id');
        
        $queryMontoAsignado = Proyecto::whereIn('item_id', $itemIds);
        if ($anioFiltro) {
            $queryMontoAsignado->where('anio_ejecucion', $anioFiltro);
        }
        $montoAsignadoMantencion = $queryMontoAsignado->sum('monto_asignado') ?? 0;
        
        $queryContratadoMantencion = Contrato::whereHas('proyecto', function($q) use ($itemIds) {
            $q->whereIn('item_id', $itemIds);
        });
        if ($anioFiltro) {
            $queryContratadoMantencion->where(function($q) use ($anioFiltro) {
                $q->whereYear('fecha_inicio', $anioFiltro)
                  ->orWhereYear('fecha_oc', $anioFiltro)
                  ->orWhereYear('created_at', $anioFiltro);
            });
        }
        $montoContratadoMantencion = $queryContratadoMantencion->sum('monto_real') ?? 0;
        
        $saldoMantencion = $presupuestoMantencion - $montoAsignadoMantencion;
        
        $this->line("Presupuesto Mantención: {$this->formato($presupuestoMantencion)}");
        $this->line("  - Subvención General:      {$this->formato($montoGeneral->monto ?? 0)}");
        $this->line("  - Subvención Mantención:   {$this->formato($montoMantencion->monto ?? 0)}");
        $this->line("  - Mantención VTF:          {$this->formato($montoVTF->monto ?? 0)}");
        $this->line("Monto Asignado Proyectos:    {$this->formato($montoAsignadoMantencion)}");
        $this->line("Monto Contratado:            {$this->formato($montoContratadoMantencion)}");
        $this->line("Saldo Disponible:            {$this->formato($saldoMantencion)}");
        
        if ($montoAsignadoMantencion > $presupuestoMantencion) {
            $this->warn("⚠️  ADVERTENCIA: Los proyectos tienen más asignado que el presupuesto disponible");
            $this->warn("   Diferencia: {$this->formato($montoAsignadoMantencion - $presupuestoMantencion)}");
        }
        
        $this->newLine();
        
        // 4. Recomendaciones
        $this->info('💡 RECOMENDACIONES:');
        $this->line('─────────────────────────────────────────────────────────');
        
        if ($montoAsignadoMantencion > $presupuestoMantencion) {
            $this->line('1. ⚠️  Si aumentas los montos globales, el saldo disponible aumentará');
            $this->line('2. ⚠️  Si disminuyes los montos globales, puede haber saldos negativos');
            $this->line('3. ⚠️  Los montos asignados a proyectos NO cambiarán automáticamente');
            $this->line('4. ✅ Considera actualizar manualmente los montos_asignado de proyectos si cambias los montos globales');
        } else {
            $this->line('1. ✅ Los montos están balanceados');
            $this->line('2. ⚠️  Si cambias los montos globales, los saldos cambiarán pero los proyectos no');
            $this->line('3. ✅ Los contratos existentes NO se afectarán');
        }
        
        $this->newLine();
        $this->info('=== Análisis completado ===');
        
        return Command::SUCCESS;
    }
    
    private function formato($monto)
    {
        return '$ ' . number_format($monto, 0, ',', '.');
    }
}
