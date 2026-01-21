<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecioUnitario extends Model
{
    protected $table = 'partidas_precios_unitarios_prueba';
    
    protected $fillable = [
        'contrato_id',
        'numero_partida',
        'partida',
        'unidad',
        'precio'
    ];

    protected $casts = [
        'precio' => 'decimal:2'
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }
}

