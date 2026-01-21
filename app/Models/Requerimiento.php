<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Requerimiento extends Model
{
    protected $table = 'requerimientos';
    
    protected $fillable = [
        'comuna_id',
        'establecimiento_id',
        'contrato_id',
        'emergencia',
        'descripcion',
        'estado',
        'usuario_creador_id',
        'usuario_mod_id',
        'fecha_mod',
        'via_solicitud',
        'fecha_email',
        'numero_oficio',
        'fecha_oficio'
    ];

    protected $casts = [
        'fecha_ingreso' => 'datetime',
        'fecha_mod' => 'datetime',
        'fecha_email' => 'date',
        'fecha_oficio' => 'date',
        'emergencia' => 'boolean'
    ];

    public function comuna()
    {
        return $this->belongsTo(Comuna::class);
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class);
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }

    public function usuarioCreador()
    {
        return $this->belongsTo(Usuario::class, 'usuario_creador_id');
    }

    public function usuarioMod()
    {
        return $this->belongsTo(Usuario::class, 'usuario_mod_id');
    }

    public function comentarios()
    {
        return $this->hasMany(RequerimientoComentario::class);
    }
}

