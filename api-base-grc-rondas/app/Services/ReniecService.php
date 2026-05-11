<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Configuracion;

class ReniecService
{
    protected $url;
    protected $dniUsuario;
    protected $password;
    protected $rucUsuario;

    public function __construct()
    {
        // 🔥 USAR CONFIGURACIÓN DE LA BASE DE DATOS
        $this->url = Configuracion::get('RENIEC_REST_URL', 'https://ws2.pide.gob.pe/Rest/RENIEC/Consultar');
        $this->dniUsuario = Configuracion::get('RENIEC_DNI_USUARIO', '41884337');
        $this->password = Configuracion::get('RENIEC_PASSWORD', 'C0nsultDni100426');
        $this->rucUsuario = Configuracion::get('RENIEC_RUC_USUARIO', '20453744168');

        Log::info('ReniecService inicializado', [
            'url' => $this->url,
            'usuario' => $this->dniUsuario
        ]);
    }

    /**
     * Determinar género basado en el nombre
     */
    private function determinarGeneroPorNombre($nombres): string
    {
        if (empty($nombres)) return '';

        $nombresFemeninos = [
            'MARIA', 'JUANA', 'ROSA', 'CARMEN', 'ANA', 'GLORIA', 'ELENA', 'LUCIA',
            'PATRICIA', 'TERESA', 'DORA', 'FELIPA', 'MANUELA', 'VICTORIA', 'LILIANA',
            'MARGARITA', 'MERCEDES', 'RAQUEL', 'SANDRA', 'ISABEL', 'VERONICA', 'JULIA',
            'ANGELA', 'SILVIA', 'PAULA', 'CLAUDIA', 'RUTH', 'ESTHER', 'ELIZABETH',
            'KATHERINE', 'STEPHANY', 'FIORELLA', 'YOVANY', 'MARCELA', 'MARTHA', 'SOFIA',
            'VALERIA', 'CAMILA', 'DANIELA', 'ANDREA', 'NICOLE', 'ADRIANA', 'FERNANDA',
            'GABRIELA', 'CAROLINA', 'ALEJANDRA', 'ANTONELA', 'BRENDA', 'CECILIA',
            'LIZBETH', 'MILAGROS', 'NANCY', 'OLGA', 'YOVANY', 'QUERUBINA', 'REBECA',
            'SUSANA', 'TATIANA', 'URSULA', 'VANESA', 'WENDY', 'XIMENA', 'YANINA',
            'ZULEMA', 'ALICIA', 'BEATRIZ', 'CONSUELO', 'DIANA', 'EDITH', 'FLOR',
            'GRACIELA', 'HELEN', 'INES', 'JACQUELINE', 'KAREN', 'LORENA', 'MONICA',
            'NORMA', 'OFELIA', 'PILAR', 'ROCIO', 'SELENA', 'TANIA', 'URBANA',
            'VIVIANA', 'WILMA', 'XIOMARA', 'YOLANDA', 'ZORAIDA', 'ADELINA', 'BERNARDA',
            'CATALINA', 'DOMITILA', 'ERMELINDA', 'FRANCISCA', 'GERTRUDIS', 'HORTENSIA',
            'IRENE', 'JOVITA', 'KATIA', 'LEONOR', 'MARLENE', 'NOEMI', 'OLIMPIA',
            'PETRONILA', 'QUINTINA', 'RUFINA', 'SABINA', 'TEODORA', 'UBALDINA',
            'VALENTINA', 'WALDIRA', 'XAVIERA', 'YESSICA', 'ZENOBIA', 'ALBINA',
            'BENILDA', 'CASILDA', 'DAMARIS', 'ESPERANZA', 'FAUSTINA', 'GUMERSINDA',
            'HERMINIA', 'INDIRA', 'JULIANA', 'KARINA', 'LAURA', 'MATILDE', 'NATALIA',
            'ORFELINA', 'PAULINA', 'RAMONA', 'SALOME', 'TEODORA', 'UBALDA', 'VICENTA',
            'WALTERA', 'XENIA', 'YAKELINE', 'ZENAYDA', 'ABIGAIL', 'BETTY', 'CELIA',
            'DALIA', 'ELVIRA', 'FLORENCIA', 'GILDA', 'HERMELINDA', 'IDALIA', 'JENNY',
            'KELI', 'LUZ', 'MIRIAM', 'NILDA', 'OTILIA', 'PRISCILLA', 'QUEREN',
            'ROXANA', 'SALVADORA', 'TERESA', 'UBENILDA', 'VILMA', 'WANDA', 'XENIA',
            'YUDITH', 'ZELIA', 'ADRIANA', 'BLANCA', 'CINTHYA', 'DIONICIA', 'ELIANA',
            'FLORINDA', 'GRIMA', 'HILDA', 'IRMA', 'JAZMIN', 'KATTY', 'LEONILA',
            'MAGDALENA', 'NATIVIDAD', 'ORLANDINA', 'PAZ', 'RAFAELA', 'SANTOSA',
            'TEOFILA', 'URDANIA', 'VIOLETA', 'WILLIAMS', 'XAVIERA', 'YENY', 'ZOILA', 'DUANY'
        ];

        $nombresMasculinos = [
            'JUAN', 'JOSE', 'CARLOS', 'PEDRO', 'LUIS', 'MIGUEL', 'JORGE', 'RAUL',
            'FERNANDO', 'ALBERTO', 'MANUEL', 'JAVIER', 'DANIEL', 'DAVID', 'ALEX',
            'JHORDY', 'EMILIO', 'REINALDO', 'CHRISTIAN', 'CRISTIAN', 'WILMER',
            'RICARDO', 'ANDRES', 'FRANCISCO', 'JULIO', 'CESAR', 'VICTOR', 'HECTOR',
            'SERGIO', 'ANGEL', 'RAFAEL', 'MARTIN', 'JESUS', 'MARCO', 'ANTONIO',
            'RAMON', 'EDUARDO', 'ENRIQUE', 'FELIX', 'GUSTAVO', 'HERNAN', 'IVAN',
            'JAIME', 'KENNY', 'LEONARDO', 'MARIO', 'NICOLAS', 'OSCAR', 'PABLO',
            'QUINTIN', 'ROBERTO', 'SALVADOR', 'TEODORO', 'ULISES', 'VALENTIN',
            'WALTER', 'XAVIER', 'YONEL', 'ZACARIAS', 'ABEL', 'BENJAMIN', 'CRUZ',
            'DOMINGO', 'ELIAS', 'FABIAN', 'GABRIEL', 'HUMBERTO', 'ISRAEL', 'JULIAN',
            'KELVIN', 'LARRY', 'MAXIMO', 'NELSON', 'ORLANDO', 'PAUL', 'QUISPE',
            'RENZO', 'SANTIAGO', 'TOMAS', 'URBANO', 'VICENTE', 'WILLIAM', 'XENON',
            'YONATHAN', 'ZENON', 'ADOLFO', 'BENITO', 'CLEMENTE', 'DIONICIO',
            'EZEQUIEL', 'FIDEL', 'GERARDO', 'HILARIO', 'IGNACIO', 'JONATHAN',
            'KLEVER', 'LAZARO', 'MAURICIO', 'NARCISO', 'ODON', 'PASCUAL',
            'QUIRINO', 'REYNALDO', 'SALOMON', 'TEODOCIO', 'UZIEL', 'Vladimir',
            'WILFREDO', 'XENIO', 'YURI', 'ZOSIMO', 'ABRAHAM', 'BALDON', 'CELSO',
            'DANTE', 'ELMER', 'FREDY', 'GENARO', 'HAROLD', 'ISAIAS', 'JOEL',
            'KIMBERLY', 'LENIN', 'MELVIN', 'NORMAN', 'OLIVER', 'PERCY', 'QUISOCE',
            'RUDY', 'SANTOS', 'TITO', 'UZIEL', 'VITALIANO', 'WALTER', 'YORDAN',
            'ZENON', 'ADAN', 'BRAYAN', 'CESARIO', 'DANIEL', 'EDISON', 'FRANK',
            'GILBERTO', 'HELI', 'INES', 'JACK', 'KEVIN', 'LUCAS', 'MATEO',
            'NAPOLEON', 'ORESTES', 'PRIMITIVO', 'QUISUAR', 'ROGELIO', 'ROGER',
            'TADEO', 'URIEL', 'VALERIO', 'WENCESLAO', 'RONALD', 'YAMIL', 'ZACARIAS'
        ];

        $nombreUpper = strtoupper($nombres);
        $ultimoCaracter = substr($nombreUpper, -1);
        $excepcionesTerminanA = ['ANDRES', 'ELIAS', 'LUCAS', 'NICOLAS', 'JONAS', 'JORGE', 'ARTURO', 'JULIO', 'JESUS', 'FRANCISCO', 'CARLOS', 'JUAN', 'JOSE'];

        if ($ultimoCaracter === 'A' && strlen($nombreUpper) > 3 && !in_array($nombreUpper, $excepcionesTerminanA)) {
            return 'FEMENINO';
        }

        if ($ultimoCaracter === 'O' && strlen($nombreUpper) > 2 && !in_array($nombreUpper, ['ASUNCIO', 'MARIA'])) {
            return 'MASCULINO';
        }

        foreach ($nombresFemeninos as $fem) {
            if (strpos($nombreUpper, $fem) !== false) {
                return 'FEMENINO';
            }
        }

        foreach ($nombresMasculinos as $mas) {
            if (strpos($nombreUpper, $mas) !== false) {
                return 'MASCULINO';
            }
        }

        return '';
    }

    public function consultarDNI(string $dniConsultar): array
    {
        if (!preg_match('/^\d{8}$/', $dniConsultar)) {
            throw new Exception('El DNI debe tener 8 dígitos');
        }

        $cacheKey = "reniec_dni_{$dniConsultar}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $payload = [
                'PIDE' => [
                    'nuDniConsulta' => $dniConsultar,
                    'nuDniUsuario' => $this->dniUsuario,
                    'nuRucUsuario' => $this->rucUsuario,
                    'password' => $this->password
                ]
            ];

            Log::info('RENIEC: Consultando DNI: ' . $dniConsultar);
            Log::info('RENIEC: Usando credenciales desde BD', [
                'url' => $this->url,
                'usuario' => $this->dniUsuario
            ]);

            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 60
            ])->withHeaders([
                'Content-Type' => 'application/json; charset=UTF-8',
                'User-Agent' => 'Laravel/10.0 PHP/' . PHP_VERSION
            ])->post($this->url . '?out=json', $payload);

            $body = $response->body();
            Log::info('RENIEC: Status: ' . $response->status());

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['consultarResponse']['return'])) {
                    $return = $data['consultarResponse']['return'];
                    $coResultado = $return['coResultado'] ?? '9999';
                    $deResultado = $return['deResultado'] ?? '';

                    if ($coResultado !== '0000') {
                        throw new Exception($deResultado ?: 'Error en consulta RENIEC (Código: ' . $coResultado . ')');
                    }

                    $datosPersona = $return['datosPersona'] ?? [];

                    $nombres = $this->cleanText($datosPersona['prenombres'] ?? '');
                    $genero = $this->determinarGeneroPorNombre($nombres);

                    if (empty($genero)) {
                        $ultimoDigito = substr($dniConsultar, -1);
                        $genero = ($ultimoDigito % 2 == 0) ? 'FEMENINO' : 'MASCULINO';
                    }

                    $resultado = [
                        'dni' => $dniConsultar,
                        'nombres' => $nombres,
                        'apellido_paterno' => $this->cleanText($datosPersona['apPrimer'] ?? ''),
                        'apellido_materno' => $this->cleanText($datosPersona['apSegundo'] ?? ''),
                        'nombre_completo' => $this->cleanText(
                            ($datosPersona['prenombres'] ?? '') . ' ' .
                            ($datosPersona['apPrimer'] ?? '') . ' ' .
                            ($datosPersona['apSegundo'] ?? '')
                        ),
                        'direccion' => $this->cleanText($datosPersona['direccion'] ?? ''),
                        'estado_civil' => $this->cleanText($datosPersona['estadoCivil'] ?? ''),
                        'ubigeo' => $this->cleanText($datosPersona['ubigeo'] ?? ''),
                        'restriccion' => $this->cleanText($datosPersona['restriccion'] ?? ''),
                        'genero' => $genero,
                        'tiene_foto' => isset($datosPersona['foto']) && !empty($datosPersona['foto']),
                        'foto_base64' => $datosPersona['foto'] ?? null,
                        'fuente' => 'reniec_rest'
                    ];

                    Cache::put($cacheKey, $resultado, now()->addDays(30));

                    return $resultado;
                } else {
                    Log::error('RENIEC: Estructura inesperada: ' . json_encode($data));
                    throw new Exception('Estructura de respuesta inesperada');
                }
            }

            throw new Exception('HTTP Error: ' . $response->status() . ' - ' . $body);

        } catch (Exception $e) {
            Log::error('RENIEC Error: ' . $e->getMessage());
            throw new Exception('Error al consultar RENIEC: ' . $e->getMessage());
        }
    }

    protected function cleanText($text): string
    {
        if (empty($text)) return '';
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim(mb_strtoupper($text, 'UTF-8'));
    }
}
