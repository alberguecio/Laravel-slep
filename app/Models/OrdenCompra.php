<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    protected $table = 'ordenes_compra';
    
    protected $fillable = [
        'numero',
        'proyecto_id',
        'contrato_id',
        'oferente_id',
        'monto_total',
        'monto_mercado_publico',
        'estado',
        'fecha',
        'descripcion',
        'factura',
        'monto_factura',
        'fecha_factura',
        'fecha_recepcion_factura',
        'mes_estimado_pago',
        'rcs_numero',
        'rcs_fecha',
        'rcs_tipo_jefatura',
        'rcs_jefatura_firma',
        'rcf_numero',
        'rcf_fecha',
        'rcf_tipo_jefatura',
        'rcf_jefatura_firma'
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'monto_mercado_publico' => 'decimal:2',
        'monto_factura' => 'decimal:2',
        'fecha' => 'date',
        'fecha_factura' => 'date',
        'fecha_recepcion_factura' => 'date',
        'rcs_fecha' => 'date',
        'rcf_fecha' => 'date'
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }

    public function oferente()
    {
        return $this->belongsTo(Oferente::class);
    }

    public function ordenesTrabajo()
    {
        return $this->hasMany(OrdenTrabajo::class);
    }
}

