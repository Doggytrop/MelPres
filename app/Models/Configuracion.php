<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'grupo',
        'descripcion',
    ];

    public static function get(string $clave, $defecto = null)
    {
        $config = static::where('clave', $clave)->first();

        if (!$config) return $defecto;

        return match($config->tipo) {
            'boolean' => (bool) $config->valor,
            'integer' => (int)  $config->valor,
            default   => $config->valor,
        };
    }

    public static function set(string $clave, $valor): void
    {
        static::where('clave', $clave)->update(['valor' => $valor]);
    }
}