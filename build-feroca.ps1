# Script de Compilación para Desarrollo - FEROCA (Windows/Laragon)
# Este script prepara el proyecto para testing en producción localmente

param(
    [switch]$Build,
    [switch]$Clean,
    [switch]$Help
)

if ($Help) {
    Write-Host "Uso: .\build-feroca.ps1 [opciones]"
    Write-Host ""
    Write-Host "Opciones:"
    Write-Host "  -Build    Compilar el frontend para producción"
    Write-Host "  -Clean    Limpiar caches y archivos temporales"
    Write-Host "  -Help     Mostrar esta ayuda"
    exit
}

$frontendDir = "C:\laragon\www\feroca\front-grc-rondas"
$backendDir = "C:\laragon\www\feroca\api-base-grc-rondas"

Write-Host "🚀 Utilidades FEROCA" -ForegroundColor Green
Write-Host "===================" -ForegroundColor Green
Write-Host ""

if ($Build) {
    Write-Host "📦 Compilando frontend para producción..." -ForegroundColor Yellow

    Push-Location $frontendDir

    if (!(Test-Path "node_modules")) {
        Write-Host "Instalando dependencias..." -ForegroundColor Cyan
        npm install
    }

    Write-Host "Ejecutando build..." -ForegroundColor Cyan
    npm run build

    Write-Host "✅ Frontend compilado en: $frontendDir\dist" -ForegroundColor Green

    Pop-Location
}

if ($Clean) {
    Write-Host "🧹 Limpiando caches..." -ForegroundColor Yellow

    Push-Location $backendDir

    Write-Host "Limpiando cache de Laravel..." -ForegroundColor Cyan
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    Write-Host "✅ Cache limpiado" -ForegroundColor Green

    Pop-Location
}

if (-not $Build -and -not $Clean) {
    Write-Host "No se especificaron opciones. Usa -Help para ver las opciones disponibles."
    Write-Host ""
    Write-Host "Acciones rápidas:" -ForegroundColor Cyan
    Write-Host "  .\build-feroca.ps1 -Build    # Compilar frontend"
    Write-Host "  .\build-feroca.ps1 -Clean    # Limpiar cache"
    Write-Host ""
    Write-Host "Para desarrollo:" -ForegroundColor Cyan
    Write-Host "  1. Backend: php artisan serve"
    Write-Host "  2. Frontend: npm run dev (desde $frontendDir)"
}

