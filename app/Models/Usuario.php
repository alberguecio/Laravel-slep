<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Model implements JWTSubject
{
    protected $table = 'usuarios';
    
    protected $fillable = [
        'nombre',
        'email',
        'password_hash',
        'rol',
        'estado',
        'cargo',
        'permisos',
        'ultimo_acceso'
    ];

    protected $hidden = [
        'password_hash'
    ];

    protected $casts = [
        'permisos' => 'array',
        'ultimo_acceso' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = Hash::make($value);
    }

    public function verifyPassword($password)
    {
        return Hash::check($password, $this->password_hash);
    }

    public function hasPermission($module, $action = 'read')
    {
        if (!$this->permisos) {
            return false;
        }

        // Verificar permiso directo
        if (isset($this->permisos[$module][$action]) && $this->permisos[$module][$action]) {
            return true;
        }

        return false;
    }

    public function hasNestedPermission(...$permissionPath)
    {
        if (!$this->permisos) {
            return false;
        }

        $permisos = $this->permisos;
        
        foreach ($permissionPath as $key) {
            if (!isset($permisos[$key])) {
                return false;
            }
            
            if (is_bool($permisos[$key])) {
                return $permisos[$key];
            }
            
            $permisos = $permisos[$key];
        }
        
        return is_bool($permisos) ? $permisos : false;
    }

    public function isAdmin()
    {
        return $this->rol === 'admin';
    }

    // Métodos para JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}

