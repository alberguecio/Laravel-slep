<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    protected $table = 'establecimientos';
    
    protected $fillable = [
        'nombre',
        'comuna_id',
        'rbd',
        'tipo',
        'ruralidad',
        'subvencion_mantenimiento',
        'aporte_subvencion_general',
        'matricula',
        'director',
        'telefono',
        'email'
    ];

    protected $casts = [
        'subvencion_mantenimiento' => 'decimal:2',
        'aporte_subvencion_general' => 'decimal:2',
        'matricula' => 'integer'
    ];
    
    // Asegurar que estos campos sean visibles
    protected $visible = ['*'];

    public function comuna()
    {
        return $this->belongsTo(Comuna::class);
    }

    public function ordenesTrabajo()
    {
        return $this->hasMany(OrdenTrabajo::class);
    }

    public function requerimientos()
    {
        return $this->hasMany(Requerimiento::class);
    }
}

