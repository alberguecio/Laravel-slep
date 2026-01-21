<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';
    
    protected $fillable = [
        'nombre'
    ];

    public function proyectos()
    {
        return $this->hasMany(Proyecto::class);
    }

    public function montosConfiguracion()
    {
        return $this->belongsToMany(MontoConfiguracion::class, 'item_montos_configuracion', 'item_id', 'monto_configuracion_id');
    }
}

