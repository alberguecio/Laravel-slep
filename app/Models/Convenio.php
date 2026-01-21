<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Convenio extends Model
{
    protected $table = 'convenios';
    
    protected $fillable = [
        'nombre',
        'tipo'
    ];

    public function ordenesTrabajo()
    {
        return $this->hasMany(OrdenTrabajo::class);
    }
}

