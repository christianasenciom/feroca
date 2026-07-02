# RESUMEN DE CAMBIOS - Dashboard en Producción

## 🔴 PROBLEMA ORIGINAL
El dashboard no se mostraba en producción. Las causas raíz fueron:

1. **Variables de entorno incorrectas** - La URL del API no estaba configurada para producción
2. **App en modo desarrollo** - Laravel estaba en `APP_ENV=local` en vez de `production`
3. **SANCTUM mal configurado** - Los dominios permitidos eran solo `localhost`
4. **Vite sin optimizaciones** - El frontend no tenía configuración de build para producción

---

## ✅ CAMBIOS REALIZADOS

### 1. `front-grc-rondas/.env.production`
```diff
- VITE_API_URL=http://192.168.56.10/api
+ VITE_API_URL=https://rondas.regioncajamarca.gob.pe/api
+ VITE_EXPOSE_DEBUG=false
```

**Impacto**: El frontend ahora conoce dónde está el API en producción.

---

### 2. `api-base-grc-rondas/.env`

#### Cambio 1: Entorno y Debug
```diff
- APP_ENV=local
- APP_DEBUG=true
- APP_URL=http://localhost
+ APP_ENV=production
+ APP_DEBUG=false
+ APP_URL=https://rondas.regioncajamarca.gob.pe
```

**Impacto**: 
- Laravel entra en modo producción (mejor rendimiento, menos verbosidad)
- Los errores se registran en logs sin mostrar detalles sensibles al usuario
- URLs se generarán con el dominio correcto

#### Cambio 2: Dominio de Sesión y Autenticación
```diff
- SESSION_DOMAIN=localhost
- SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,localhost:5173,127.0.0.1:5173
+ SESSION_DOMAIN=.regioncajamarca.gob.pe
+ SANCTUM_STATEFUL_DOMAINS=rondas.regioncajamarca.gob.pe
```

**Impacto**: 
- Las cookies de sesión se aceptarán desde el dominio correcto
- La autenticación SANCTUM reconocerá al frontend como origen permitido
- Corrige el error 401 Unauthorized al intentar acceder al dashboard

---

### 3. `api-base-grc-rondas/config/cors.php`

```diff
- 'paths' => ['api/*', '/*','sanctum/csrf-cookie', 'api/web/*'],
- 'allowed_origins' => ['*'],
- 'exposed_headers' => [],
+ 'paths' => ['api/*', 'sanctum/csrf-cookie', 'auth/*'],
+ 'allowed_origins' => [
+     env('APP_ENV') === 'production'
+         ? env('APP_URL', '*')
+         : '*'
+ ],
+ 'exposed_headers' => ['Authorization'],
```

**Impacto**:
- Más seguro: Solo permite solicitudes desde el dominio correcto en producción
- Expone el header Authorization para el manejo de tokens
- Rutas más específicas y claras

---

### 4. `front-grc-rondas/vite.config.js`

```javascript
+ build: {
+   outDir: 'dist',
+   sourcemap: false,
+   minify: 'terser',
+   chunkSizeWarningLimit: 1000
+ },
+ server: {
+   proxy: {
+     '/api': {
+       target: 'http://localhost:8000',
+       changeOrigin: true,
+     }
+   }
+ }
```

**Impacto**:
- Optimiza el build para producción (archivos más pequeños, sin source maps)
- Permite proxying del API en desarrollo local
- Mejor performance

---

## 🧪 CÓMO TESTEAR LOCALMENTE

### Antes de desplegar a producción, prueba esto en local:

#### 1. Compilar el frontend
```powershell
cd C:\laragon\www\feroca\front-grc-rondas
npm install
npm run build
```

Esto generará archivos en `dist/`

#### 2. Cambiar `.env.production` a `.env` para testing
```bash
cd C:\laragon\www\feroca\front-grc-rondas
copy .env.production .env.test
```

Edita `.env.test` si necesitas probar con URLs locales:
```
VITE_API_URL=http://localhost:8000/api
```

#### 3. Servir el build localmente
```bash
cd C:\laragon\www\feroca\front-grc-rondas
npm install -g serve
serve -s dist -l 5000
```

Abre `http://localhost:5000` en el navegador

#### 4. Asegúrate de que el backend está corriendo
```bash
cd C:\laragon\www\feroca\api-base-grc-rondas
php artisan serve  # Runs on http://localhost:8000
```

#### 5. Prueba el flujo completo
- [ ] Abre http://localhost:5000
- [ ] Intenta iniciar sesión
- [ ] Verifica en F12 > Network que `/api/auth/signin` responde 200-201
- [ ] Si el login funciona, deberías ver el dashboard
- [ ] Verifica en F12 > Console que no hay errores

---

## 📋 CHECKLIST PARA PRODUCCIÓN

```
FRONTEND:
□ Ejecutado: npm run build
□ Carpeta dist/ existe y tiene archivos
□ Copiar contenido de dist/ a servidor web raíz
□ Verificar que index.html se sirve en /
□ Verificar que assets se cargan correctamente

BACKEND:
□ .env con APP_ENV=production
□ .env con APP_DEBUG=false
□ .env con APP_URL=https://rondas.regioncajamarca.gob.pe
□ SANCTUM_STATEFUL_DOMAINS=rondas.regioncajamarca.gob.pe
□ Ejecutado: composer install --no-dev
□ Ejecutado: php artisan migrate --force
□ Ejecutado: php artisan config:cache
□ Ejecutado: php artisan route:cache
□ Base de datos migrada y data disponible

SERVIDOR WEB:
□ HTTPS/SSL configurado
□ Redirecciones correctas (HTTP -> HTTPS)
□ Permisos de archivo correctos
□ Proxy de /api ajustado (si es necesario)

VERIFICACIÓN FINAL:
□ https://rondas.regioncajamarca.gob.pe/ carga
□ Login funciona
□ Dashboard es visible
□ Sin errores 401, 403, o 404
□ Logs sin errores críticos
```

---

## 🐛 TROUBLESHOOTING

### Síntoma: "No se ve la página"
**Solución**: 
```bash
# Verifica que dist/index.html existe
ls -la C:\laragon\www\feroca\front-grc-rondas\dist\index.html

# Verifica que el servidor web sirve archivos estáticos
# Configura tu servidor para usar index.html como fallback
```

### Síntoma: "Login no funciona (401)"
**Solución**:
```bash
cd C:\laragon\www\feroca\api-base-grc-rondas
php artisan config:cache  # Recarga configuración
php artisan cache:clear
```

Verifica en `.env`:
```
SANCTUM_STATEFUL_DOMAINS=rondas.regioncajamarca.gob.pe
SESSION_DOMAIN=.regioncajamarca.gob.pe
```

### Síntoma: "Dashboard en blanco después de login"
**Solución**:
1. Abre F12 > Console y busca errores POST/GET a `/api/*`
2. Si ve 401/403, es problema de autenticación (ve arriba)
3. Si ve 404, probablemente la URL del API es incorrecta
4. Verifica `.env.production`:
   ```
   VITE_API_URL=https://rondas.regioncajamarca.gob.pe/api
   ```

### Síntoma: "Los estilos no cargan"
**Solución**:
1. Abre F12 > Network
2. Busca los archivos CSS/JS que faltan
3. Verifica que todos estén en `dist/assets/`
4. Asegúrate que el servidor sirve archivos estáticos correctamente

---

## 📚 ARCHIVOS IMPORTANTES

- ✅ `INSTRUCCIONES_PRODUCCION.md` - Guía completa
- ✅ `deploy.sh` - Script automatizado para Linux
- ✅ `build-feroca.ps1` - Utilidades para Windows
- ✅ Cambios en `.env.production`
- ✅ Cambios en `.env` (backend)
- ✅ Cambios en `config/cors.php`
- ✅ Cambios en `vite.config.js`

---

## 💡 PRÓXIMAS ACCIONES RECOMENDADAS

1. **Testear localmente** siguiendo la sección "CÓMO TESTEAR LOCALMENTE"
2. **Desplegar a staging** si tienes ambiente
3. **Ejecutar deploy.sh** en producción (o los pasos manuales)
4. **Monitorear logs** después del deploy
5. **Configurar backups** para la BD
6. **Configurar alertas** para caídas

---

## 📞 SOPORTE

Si aún tienes problemas:
1. Revisa los logs: `tail -f storage/logs/laravel.log`
2. Verifica en F12 las solicitudes API
3. Comprueba permisos de carpetas: `storage/`, `bootstrap/cache/`
4. Asegúrate de que la BD está migrada: `php artisan migrate:status`

