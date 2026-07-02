# COMANDOS RÁPIDOS - Despliegue FEROCA

## 🚀 Despliegue Rápido en Linux/Producción

```bash
#!/bin/bash
# Ejecutar en el servidor de producción

# 1. Navegar a directorios
cd /var/www/rondas/api-base-grc-rondas

# 2. Actualizar dependencias
composer install --no-dev --optimize-autoloader

# 3. Ejecutar migraciones
php artisan migrate --force

# 4. Cachear configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Permisos
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 6. Verificar status
php artisan tinker --execute="dd(config('app.env'), config('app.debug'))"
# Debería mostrar:
# "production"
# false
```

---

## 💻 Compilación de Frontend (Windows/Laragon)

```powershell
# En PowerShell desde la carpeta front-grc-rondas

cd C:\laragon\www\feroca\front-grc-rondas

# Instalar dependencias
npm install

# Compilar para producción
npm run build

# Listar archivos compilados
ls dist/

# Copiar manualmente a servidor (o automatizar con script)
```

---

## 🔄 Ciclo de Desarrollo Local

### Opción A: Desarrollo Tradicional

**Terminal 1 - Backend**
```bash
cd C:\laragon\www\feroca\api-base-grc-rondas
php artisan serve  # Corre en http://localhost:8000
```

**Terminal 2 - Frontend**
```bash
cd C:\laragon\www\feroca\front-grc-rondas
npm run dev  # Corre en http://localhost:5173
```

### Opción B: Con Laragon (Todo en UI)

1. Abre Laragon
2. Clic derecho en `feroca` > Open in Browser
3. Laragon sirve ambas carpetas automáticamente

---

## 🧹 Limpiar Cache (Ambientes)

### Backend
```bash
cd C:\laragon\www\feroca\api-base-grc-rondas

# Cache de aplicación
php artisan cache:clear

# Config cache
php artisan config:clear

# Routes cache
php artisan route:clear

# Views cache
php artisan view:clear

# Todo junto
php artisan optimize:clear
```

### Frontend
```bash
cd C:\laragon\www\feroca\front-grc-rondas

# Borrar dist (si existe)
rmdir /s /q dist

# Limpiar node_modules (last resort)
rmdir /s /q node_modules
npm install
```

---

## 📦 Compilación Condicional

### Para Desarrollo
```bash
cd C:\laragon\www\feroca\front-grc-rondas
npm run dev  # Watch mode con Hot Module Replacement
```

### Para Producción Local (testing)
```bash
npm run build
serve -s dist -l 5000
# Luego abre http://localhost:5000
```

### Para Producción Real
```bash
npm run build
# Copiar dist/* a /var/www/rondas/ en servidor
```

---

## 🗺️ Variables de Entorno por Ambiente

### Desarrollo (.env.development)
```
VITE_API_URL=http://127.0.0.1:8000/api
```

### Producción Local (.env o .env.test)
```
VITE_API_URL=http://localhost:8000/api
```

### Producción Real (.env.production)
```
VITE_API_URL=https://rondas.regioncajamarca.gob.pe/api
```

---

## 🔍 Debugging / Verificación

### Frontend en Navegador
```javascript
// Abre DevTools (F12), Console, pega:
console.log(import.meta.env.VITE_API_URL)
// Debería mostrar la URL correcta
```

### Backend
```bash
# Ver variable de entorno
php artisan config:get app.env

# Ver si está en production
php artisan tinker --execute="dd(app('config')['app']['env'])"

# Ejecutar query de prueba
php artisan tinker
>>> User::count()  # Debería retornar un número
```

### API Health Check
```bash
# Desde terminal
curl -X GET http://localhost:8000/api

# Desde navegador
https://rondas.regioncajamarca.gob.pe/api
# Debería retornar JSON con indicadores de health
```

---

## 📊 Monitoreo en Producción

### Ver logs en tiempo real
```bash
# Backend
tail -f /var/www/rondas/api-base-grc-rondas/storage/logs/laravel.log

# Nginx
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# Apache
tail -f /var/log/apache2/error.log
```

### Ver estado de BD
```bash
php artisan tinker
>>> DB::connection()->getPdo()->exec("select count(*) as c from users")
>>> DB::connection()->selectOne("select now()")
```

---

## 🆘 Comandos de Emergencia

### Si la BD falla
```bash
# Ejecutar migraciones frescas (⚠️ Borra datos)
php artisan migrate:fresh --seed

# Solo ejecutar migraciones pendientes
php artisan migrate

# Ver status
php artisan migrate:status
```

### Si el cache está corrupto
```bash
php artisan optimize:clear  # Para todo
# O específicamente:
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Si el frontend no carga
```bash
# Rebuild completo
npm install
npm run build
rm -rf dist/
npm run build

# O servir en desarrollo
npm run dev
```

### Restaurar .env a desarrollo
```bash
# Copiar de ejemplo
cp .env.example .env

# O usar .development local
cat > .env << EOF
APP_ENV=local
APP_DEBUG=true
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
...
EOF
```

---

## ✅ Check List Rápido Post-Despliegue

```
□ npm run build completó sin errores
□ dist/ tiene archivos (index.html, assets, etc)
□ Backend: APP_ENV=production
□ Backend: APP_DEBUG=false
□ Backend: SANCTUM_STATEFUL_DOMAINS correcto
□ php artisan config:cache ejecutado
□ Permisos de storage correctos (775)
□ Base de datos migrada
□ HTTPS/SSL funcionando
□ https://rondas.regioncajamarca.gob.pe/ carga (sin errores)
□ Login funciona
□ Dashboard visible
□ F12 Console sin errores rojos
□ F12 Network: GET /api/dashboard/index responde 200
□ Logs sin errores críticos
```

---

## 🔗 Útiles

**Archivos de configuración críticos:**
- `.env` (Backend - Variables globales)
- `.env.production` (Frontend - Variables de build)
- `vite.config.js` (Frontend - Build config)
- `config/cors.php` (Backend - CORS policy)
- `routes/api.php` (Backend - API endpoints)
- `vite.config.js` + Nginx/Apache config (Servidor web)

**Carpetas críticas a backing up:**
- `storage/` (Logs, archivos subidos)
- `database/` (Migraciones, seeders)
- Archive `.env` en lugar seguro


