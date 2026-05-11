<?php

namespace App\Models\Publico;

use App\Models\Base\User;
use Carbon\Carbon;
use Wildside\Userstamps\Userstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Rondero extends Model
{
    use HasFactory, Userstamps;
    protected $connection = 'pgsql';
    protected $table = 'public.rondero';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'persona_id',
        'fecha_inicio',
        'fecha_cese',
        'codigo_qr',
        'estado',
        'region_id',
        'provincia_id',
        'distrito_id',
        'sector_zona_id',
        'base_id',
        'codigo_rondero' 
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rondero) {
            if (empty($rondero->codigo_rondero)) {
                $rondero->codigo_rondero = self::generarCodigoRondero();
            }
        });
    }

    /**
     * Generate 8-digit incremental rondero code
     */
    public static function generarCodigoRondero()
    {
        // Obtener el último código existente
        $ultimoRondero = self::orderBy('id', 'desc')->first();
        
        if ($ultimoRondero && $ultimoRondero->codigo_rondero) {
            // Si existe un código, incrementarlo
            $ultimoCodigo = (int) $ultimoRondero->codigo_rondero;
            $nuevoCodigo = $ultimoCodigo + 1;
        } else {
            // Si no hay códigos, empezar desde 1
            $nuevoCodigo = 1;
        }
        
        // Formatear a 8 dígitos con ceros a la izquierda
        return str_pad($nuevoCodigo, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Relaciones
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
    
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
    
    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }
    
    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }
    
    public function sector_zona()
    {
        return $this->belongsTo(Sector::class);
    }
    
    public function base()
    {
        return $this->belongsTo(Base::class);
    }
    
    public function comites()
    {
        return $this->hasMany(Comite::class);
    }
    
    // Método para verificar si el rondero está habilitado
    public function isHabilitado()
    {
        // Si la fecha de cese es nula, el rondero sigue habilitado
        if (is_null($this->fecha_cese)) {
            return true;
        }

        // Compara la fecha de cese con la fecha actual
        return Carbon::now()->lt(Carbon::parse($this->fecha_cese));
    }

    public function programacionTurno()
    {
        return $this->hasMany(ProgramacionTurno::class);
    }

    public function getCodigoRonderoAttribute($value)
    {
        if ($value) {
            return str_pad($value, 8, '0', STR_PAD_LEFT);
        }
        return $value;
    }

    public function setCodigoRonderoAttribute($value)
    {
        if ($value) {
            // Eliminar cualquier caracter no numérico y asegurar 8 dígitos
            $cleanValue = preg_replace('/\D/', '', $value);
            $this->attributes['codigo_rondero'] = str_pad($cleanValue, 8, '0', STR_PAD_LEFT);
        }
    }
}