<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenTrabajo extends Model
{
    protected $table = 'ordenes_trabajo';
    
    protected $fillable = [
        'comuna_id',
        'establecimiento_id',
        'convenio_id',
        'oferente_id',
        'numero_ot',
        'fecha_ot',
        'fecha_envio_oc',
        'mes',
        'sin_iva',
        'monto',
        'orden_compra',
        'fecha_oc',
        'fecha_recepcion',
        'factura',
        'fecha_factura',
        'observacion',
        'orden_compra_id',
        'contrato_id',
        'estado',
        'tipo',
        'medida'
    ];

    protected $casts = [
        'sin_iva' => 'decimal:2',
        'monto' => 'decimal:2',
        'fecha_ot' => 'date',
        'fecha_envio_oc' => 'date',
        'fecha_oc' => 'date',
        'fecha_recepcion' => 'date',
        'fecha_factura' => 'date'
    ];

    public function comuna()
    {
        return $this->belongsTo(Comuna::class);
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class);
    }

    public function convenio()
    {
        return $this->belongsTo(Convenio::class);
    }

    public function oferente()
    {
        return $this->belongsTo(Oferente::class);
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }

    public function presupuestoOt()
    {
        return $this->hasOne(PresupuestoOt::class, 'ot_id');
    }
}

