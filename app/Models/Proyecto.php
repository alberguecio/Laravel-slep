<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    protected $table = 'proyectos';
    
    protected $fillable = [
        'nombre',
        'tipo',
        'fondo',
        'oferente_id',
        'monto_asignado',
        'estado',
        'fecha_inicio',
        'fecha_cierre',
        'item_id',
        'codigo_idi',
        'anio_ejecucion'
    ];

    protected $casts = [
        'monto_asignado' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_cierre' => 'date'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function oferente()
    {
        return $this->belongsTo(Oferente::class);
    }

    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }

    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class);
    }
}

