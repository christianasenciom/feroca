#!/bin/bash
# Script de Despliegue en Producción - FEROCA
# Este script automatiza el proceso de compilación y despliegue

set -e  # Salir si hay algún error

echo "🚀 Iniciando proceso de despliegue..."
echo "===================================="

# Variables de configuración
FRONTEND_DIR="/path/to/front-grc-rondas"  # Cambiar a la ruta correcta
BACKEND_DIR="/path/to/api-base-grc-rondas"  # Cambiar a la ruta correcta
DEPLOY_DIR="/var/www/rondas"
DIST_DIR="$DEPLOY_DIR/dist"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}1. Verificando directorios...${NC}"
if [ ! -d "$FRONTEND_DIR" ]; then
    echo -e "${RED}Error: Directorio del frontend no encontrado: $FRONTEND_DIR${NC}"
    exit 1
fi
if [ ! -d "$BACKEND_DIR" ]; then
    echo -e "${RED}Error: Directorio del backend no encontrado: $BACKEND_DIR${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Directorios verificados${NC}"

echo -e "${YELLOW}2. Compilando frontend...${NC}"
cd "$FRONTEND_DIR"
npm install
npm run build
echo -e "${GREEN}✓ Frontend compilado${NC}"

echo -e "${YELLOW}3. Limpiando directorio de despliegue anterior...${NC}"
if [ -d "$DIST_DIR" ]; then
    sudo rm -rf "$DIST_DIR"
fi
sudo mkdir -p "$DIST_DIR"

echo -e "${YELLOW}4. Copiando archivos compilados...${NC}"
sudo cp -r "$FRONTEND_DIR/dist/"* "$DIST_DIR/"
sudo chown -R www-data:www-data "$DIST_DIR"
sudo chmod -R 755 "$DIST_DIR"
echo -e "${GREEN}✓ Archivos copiados${NC}"

echo -e "${YELLOW}5. Actualizando dependencias del backend...${NC}"
cd "$BACKEND_DIR"
composer install --no-dev --optimize-autoloader
echo -e "${GREEN}✓ Dependencias actualizadas${NC}"

echo -e "${YELLOW}6. Ejecutando migraciones...${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Migraciones completadas${NC}"

echo -e "${YELLOW}7. Limpiando cache y optimizando...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear
echo -e "${GREEN}✓ Cache limpiado y optimizado${NC}"

echo -e "${YELLOW}8. Configurando permisos...${NC}"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✓ Permisos configurados${NC}"

echo -e "${YELLOW}9. Verificando estado del backend...${NC}"
php artisan tinker --execute="dd(config('app.env'))"
echo -e "${GREEN}✓ Backend en producción${NC}"

echo ""
echo -e "${GREEN}✅ Despliegue completado exitosamente!${NC}"
echo ""
echo "Resumen:"
echo "------"
echo "✓ Frontend compilado y copiado a: $DIST_DIR"
echo "✓ Backend actualizado y optimizado"
echo "✓ Base de datos migrada"
echo ""
echo "Próximos pasos:"
echo "- Verifica que https://rondas.regioncajamarca.gob.pe se carga correctamente"
echo "- Intenta iniciar sesión"
echo "- Verifica que el dashboard es visible"
echo ""
echo "Si hay problemas, verifica los logs:"
echo "tail -f $BACKEND_DIR/storage/logs/laravel.log"

