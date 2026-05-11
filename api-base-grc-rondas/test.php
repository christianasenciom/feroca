<?php

require 'vendor/autoload.php';

use App\Services\ReniecService;

echo "=== INICIANDO PRUEBA ===\n";

$reniec = new ReniecService();
echo "Servicio creado\n";

try {
    $resultado = $reniec->consultarDNI('26683145');
    echo "Resultado:\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "=== FIN PRUEBA ===\n";