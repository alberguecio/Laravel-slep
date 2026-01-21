<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MontoConfiguracion extends Model
{
    protected $table = 'montos_configuracion';
    
    protected $fillable = [
        'nombre',
        'codigo',
        'orden',
        'monto'
    ];

    protected $casts = [
        'monto' => 'decimal:2'
    ];

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_montos_configuracion', 'monto_configuracion_id', 'item_id');
    }
}

