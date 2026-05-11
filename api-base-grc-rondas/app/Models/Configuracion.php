<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuraciones';

    protected $fillable = ['clave', 'valor', 'tipo', 'descricion'];

    public static function get($clave, $default = null)
    {
        $config = self::where('clave', $clave)->first();
        return $config ? $config->valor : $default;
    }

    public static function set($clave, $valor, $tipo = 'text', $descripcion = null)
    {
        return self::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor, 'tipo' => $tipo, 'descripcion' => $descripcion]
        );
    }
}
