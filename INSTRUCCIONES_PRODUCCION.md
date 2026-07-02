# Guía de Despliegue en Producción - FEROCA

## Problema Identificado
El dashboard no era visible en producción debido a problemas de configuración en:
1. Variables de entorno del frontend
2. Configuración del backend (APP_ENV)
3. Configuración de SANCTUM (autenticación)
4. Posible falta de compilación del frontend

## Soluciones Aplicadas

### 1. **Configuración del Frontend (.env.production)**
✅ Actualizada la URL de API a: `https://rondas.regioncajamarca.gob.pe/api`

### 2. **Configuración del Backend**
✅ Cambio de `APP_ENV=local` a `APP_ENV=production`
✅ Cambio de `APP_DEBUG=true` a `APP_DEBUG=false`
✅ Actualización de `APP_URL` a `https://rondas.regioncajamarca.gob.pe`
✅ Configuración correcta de SANCTUM_STATEFUL_DOMAINS

### 3. **Vite Configuration**
✅ Agregada configuración de build optimizado para producción

---

## Pasos para Desplegar en Producción

### Opción A: Frontend y Backend en el Mismo Servidor

#### Paso 1: Compilar el Frontend
```bash
cd C:\laragon\www\feroca\front-grc-rondas
npm install  # Si es la primera vez
npm run build
```

Esto generará una carpeta `dist/` con los archivos compilados.

#### Paso 2: Copiar archivos compilados
- Copia todo el contenido de la carpeta `dist/` a tu servidor web en la raíz del dominio
- O, si tienes un virtual host específico, copia los archivos al directorio raíz

#### Paso 3: Configurar el servidor web (Apache/Nginx)

**Para Apache:**
El archivo `.htaccess` ya está configurado en `public/` para redireccionar correctamente al API.

**Para Nginx:**
Agrega esta configuración en tu bloque server:
```nginx
# Sirve el frontend Vue
location / {
    root /var/www/rondas/dist;
    try_files $uri $uri/ /index.html;
}

# Redirecciona las solicitudes /api al backend
location /api {
    proxy_pass http://localhost:8000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}

# Sirve el backend Laravel
location /backend {
    proxy_pass http://localhost:8000;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

#### Paso 4: Configurar el Backend
```bash
cd /var/www/rondas/api-base-grc-rondas  # Ruta en producción

# Ejecutar migraciones (primera vez)
php artisan migrate --force

# Limpiar cache
php artisan config:cache
php artisan cache:clear
php artisan route:cache

# Si necesitas seeders
php artisan db:seed --force
```

### Opción B: Frontend en CDN / Servidor Separado

Si prefieres servir el frontend desde un servidor diferente (CDN, servidor separado):

1. Compila en producción:
```bash
npm run build
```

2. El archivo `.env.production` ya tiene la URL correcta del API:
```
VITE_API_URL=https://rondas.regioncajamarca.gob.pe/api
```

3. Sube los archivos de `dist/` a tu CDN o servidor web

4. Asegúrate de que CORS esté habilitado en el backend (verificar rutas/api.php)

---

## Verificar que Todo Funciona

### 1. Verifica que el backend está respondiendo:
```bash
curl -X GET https://rondas.regioncajamarca.gob.pe/api/health
```

### 2. Verifica que la página principal carga:
- Abre https://rondas.regioncajamarca.gob.pe en el navegador
- Deberías ver la página de login

### 3. Verifica en la consola del navegador (F12):
- Abre la pestaña "Network"
- Intenta iniciar sesión
- Verifica que las llamadas a `/api/auth/signin` respondan correctamente (no deben ser 404)
- En la pestaña "Console" no debe haber errores importantes

### 4. Verifica el dashboard:
- Inicia sesión con tus credenciales
- Deberías ver el dashboard con las estadísticas

---

## Posibles Problemas y Soluciones

### Problema: "No se ve nada en la página"
**Causa**: El archivo index.html del frontend no se está sirviendo correctamente
**Solución**: 
- Verifica que `dist/index.html` existe
- Configura tu servidor web para servir `index.html` en rutas que no sean archivos reales (`try_files` en Nginx, `mod_rewrite` en Apache)

### Problema: "Los estilos/scripts no cargan"
**Causa**: Las rutas de los assets están incorrectas
**Solución**:
- Verifica que los archivos en `dist/assets/` se está sirviendo correctamente
- Abre Developer Tools (F12) y ve en qué URL están intentando cargar los archivos

### Problema: "Error al iniciar sesión - 401 o 403"
**Causa**: SANCTUM no está correctamente configurado
**Solución**:
- Verifica que `SANCTUM_STATEFUL_DOMAINS` incluya tu dominio de producción
- Verifica que `SESSION_DOMAIN` esté correcto
- Ejecuta: `php artisan config:cache` en el backend

### Problema: "Error al hacer llamadas a la API - CORS"
**Causa**: CORS no está permitido
**Solución**:
- Verifica el archivo `config/cors.php`
- Asegúrate de que tu dominio de frontend está en la lista de orígenes permitidos

### Problema: "base de datos no conecta"
**Causa**: Variables de BD en `.env` no son correctas para producción
**Solución**:
- Verifica `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` en el `.env` de producción
- Ejecuta: `php artisan db` para verificar

---

## Verificar Logs en Caso de Errores

### Logs del Backend:
```bash
tail -f /var/www/rondas/api-base-grc-rondas/storage/logs/laravel.log
```

### Logs del Servidor Web:

**Apache:**
```bash
tail -f /var/log/apache2/error.log
```

**Nginx:**
```bash
tail -f /var/log/nginx/error.log
```

---

## Checklist Antes de Go Live

- [ ] Compilar frontend con `npm run build`
- [ ] Copiar `dist/` a la raíz del servidor web
- [ ] Actualizar `.env` con datos de producción
- [ ] Ejecutar migraciones: `php artisan migrate --force`
- [ ] Limpiar cache: `php artisan config:cache && php artisan cache:clear`
- [ ] Verificar que SANCTUM_STATEFUL_DOMAINS es correcto
- [ ] Verificar CORS en config/cors.php
- [ ] Probar login funciona
- [ ] Probar dashboard carga correctamente
- [ ] Verificar logs no tienen errores críticos
- [ ] Configurar HTTPS/SSL
- [ ] Activar backups automáticos

---

## Información Importante

**URLs Configuradas:**
- **Frontend**: https://rondas.regioncajamarca.gob.pe
- **API**: https://rondas.regioncajamarca.gob.pe/api
- **Base de datos**: Según `DB_HOST` en `.env`

**Variables Críticas en Producción:**
```
APP_ENV=production
APP_DEBUG=false
SANCTUM_STATEFUL_DOMAINS=rondas.regioncajamarca.gob.pe
SESSION_DOMAIN=.regioncajamarca.gob.pe
VITE_API_URL=https://rondas.regioncajamarca.gob.pe/api
```

---

## Soporte Adicional

Si aún tienes problemas:
1. Revisa los logs del servidor web
2. Abre Developer Tools (F12) en el navegador y verifica los errores
3. Verifica que los puertos necesarios estén abiertos
4. Asegúrate de tener permisos correctos en las carpetas

