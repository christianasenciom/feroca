<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

echo "=== PROBANDO RENIEC REST ===\n\n";

$payload = [
    'PIDE' => [
        'nuDniConsulta' => '41884337',
        'nuDniUsuario' => '41884337',
        'nuRucUsuario' => '20453744168',
        'password' => 'C0nsultDni100426'
    ]
];

echo "Payload enviado:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

try {
    $response = Http::withOptions(['verify' => false])
        ->withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
        ->post('https://ws2.pide.gob.pe/Rest/RENIEC/Consultar?out=json', $payload);
    
    echo "Status Code: " . $response->status() . "\n\n";
    echo "Respuesta completa:\n";
    echo $response->body() . "\n\n";
    
    // Intentar parsear como JSON
    $json = $response->json();
    if ($json) {
        echo "Respuesta parseada como JSON:\n";
        print_r($json);
    } else {
        echo "No se pudo parsear como JSON\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}