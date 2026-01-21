<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comuna extends Model
{
    protected $table = 'comunas';
    
    protected $fillable = [
        'nombre'
    ];

    public function establecimientos()
    {
        return $this->hasMany(Establecimiento::class);
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

