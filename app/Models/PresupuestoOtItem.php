<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresupuestoOtItem extends Model
{
    protected $table = 'presupuesto_ot_item';
    
    protected $fillable = [
        'presupuesto_ot_id',
        'item',
        'partida',
        'unidad',
        'cantidad',
        'precio',
        'total'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function presupuestoOt()
    {
        return $this->belongsTo(PresupuestoOt::class);
    }
}

