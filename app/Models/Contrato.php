<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $table = 'contratos';
    
    protected $fillable = [
        'proyecto_id',
        'numero_contrato',
        'nombre_contrato',
        'id_licitacion',
        'monto_real',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'duracion_dias',
        'proveedor',
        'observaciones',
        'orden_compra',
        'fecha_oc',
        'archivo_contrato',
        'archivo_bases',
        'archivo_oferta'
    ];

    protected $casts = [
        'monto_real' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_oc' => 'date'
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function ordenesTrabajo()
    {
        return $this->hasMany(OrdenTrabajo::class);
    }

    public function preciosUnitarios()
    {
        return $this->hasMany(PrecioUnitario::class);
    }

    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class);
    }
}

