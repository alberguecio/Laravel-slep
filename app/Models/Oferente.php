<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Oferente extends Model
{
    protected $table = 'oferentes';
    
    protected $fillable = [
        'nombre',
        'rut',
        'direccion',
        'telefono',
        'email'
    ];

    public function proyectos()
    {
        return $this->hasMany(Proyecto::class);
    }

    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class);
    }

    public function ordenesTrabajo()
    {
        return $this->hasMany(OrdenTrabajo::class);
    }
}

