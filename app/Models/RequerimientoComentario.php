<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequerimientoComentario extends Model
{
    protected $table = 'requerimiento_comentarios';
    
    public $timestamps = true;
    
    protected $fillable = [
        'requerimiento_id',
        'usuario_id',
        'comentario'
    ];

    public function requerimiento()
    {
        return $this->belongsTo(Requerimiento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}

