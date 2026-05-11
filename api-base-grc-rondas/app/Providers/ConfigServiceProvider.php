<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Configuracion;

class ConfigServiceProvider extends ServiceProvider
{
    public function boot()
    {
        try {
            $configs = Configuracion::all();
            foreach ($configs as $config) {
                config(['reniec.' . $config->clave => $config->valor]);
            }
        } catch (\Exception $e) {
            // Tabla aún no existe o no hay datos
        }
    }

    public function register()
    {
        //
    }
}
