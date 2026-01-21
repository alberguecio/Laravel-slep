<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresupuestoOt extends Model
{
    protected $table = 'presupuesto_ot';
    
    protected $fillable = [
        'ot_id',
        'usuario_id',
        'fecha'
    ];

    protected $casts = [
        'fecha' => 'date',
        'creado' => 'datetime',
        'actualizado' => 'datetime'
    ];

    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'ot_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function items()
    {
        return $this->hasMany(PresupuestoOtItem::class);
    }
}

