# 🚀 Guía de Despliegue Gratuito para Laravel

Esta guía te muestra las mejores opciones **gratuitas** para desplegar tu aplicación Laravel en línea.

## ⚠️ Importante sobre GitHub Pages

**GitHub Pages NO funciona para Laravel** porque solo sirve sitios estáticos (HTML/CSS/JS). Laravel necesita PHP y base de datos (MySQL o PostgreSQL).

## ⚠️ Importante sobre Render y Bases de Datos

**Render solo ofrece PostgreSQL gratuito**, NO MySQL. Si necesitas MySQL, tendrías que pagar. Pero **no te preocupes**: Laravel funciona perfectamente con PostgreSQL y es muy fácil de configurar. Ya hemos preparado tu código para soportar ambos.

---

## 🎯 Opciones Recomendadas (Gratuitas)

### 1. **Render** ⭐ (Recomendado)

**Ventajas:**
- ✅ Plan gratuito permanente
- ✅ Base de datos MySQL incluida (gratis)
- ✅ Despliegue automático desde GitHub
- ✅ SSL gratuito
- ✅ Muy fácil de configurar

**Limitaciones del plan gratuito:**
- La aplicación se "duerme" después de 15 minutos de inactividad
- Se despierta automáticamente cuando alguien la visita (puede tardar ~30 segundos)
- 750 horas de ejecución por mes

**Pasos para desplegar:**

1. **Crear cuenta en Render:**
   - Ve a https://render.com
   - Regístrate con GitHub (o con Gmail, luego conecta GitHub)
   
   **Si ya te registraste con Gmail:**
   - Ve a tu cuenta en Render
   - Ve a "Account Settings" o "Settings"
   - Busca la sección "GitHub" o "Connected Accounts"
   - Click en "Connect GitHub" o "Link GitHub"
   - Autoriza a Render para acceder a tus repositorios

2. **Crear Base de Datos PostgreSQL:**
   - ⚠️ **Importante:** Render solo ofrece PostgreSQL gratuito, NO MySQL
   - En el dashboard, click en "New +" → "Postgres"
   - Configuración:
     - **Name:** `slep-chiloe-db`
     - **Database:** `slep_chiloe` (o déjalo vacío para generación automática)
     - **User:** Déjalo vacío (se generará automáticamente)
     - **Plan:** Free
   - Click "Create Database"
   - **Guarda las credenciales** (host, usuario, contraseña, nombre de BD, puerto)
   - ⚠️ **Nota:** La BD gratuita expira en 30 días, luego tienes 14 días de gracia

3. **Desplegar la aplicación:**
   - Click en "New +" → "Web Service"
   - Conecta tu repositorio de GitHub
   - Si aparece "Git Deployment Credentials":
     - **Opción recomendada:** Click en "Connect GitHub" o "Authorize GitHub"
     - Esto te llevará a GitHub para autorizar a Render
     - Selecciona "All repositories" o "Only select repositories" (recomiendo "All repositories" para empezar)
     - Click en "Install" o "Authorize"
     - Serás redirigido de vuelta a Render
   - Selecciona el repositorio de tu app
   - Configuración:
     - **Name:** `slep-chiloe-app`
     - **Environment:** `PHP`
     - **Build Command:** `composer install --no-dev --optimize-autoloader`
     - **Start Command:** `php artisan serve --host=0.0.0.0 --port=$PORT`
     - **Plan:** Free

4. **Configurar Variables de Entorno:**
   En la sección "Environment Variables", agrega:
   ```
   APP_NAME=SLEP_Chiloe
   APP_ENV=production
   APP_KEY=(genera con: php artisan key:generate --show)
   APP_DEBUG=false
   APP_URL=https://tu-app.onrender.com
   
   DB_CONNECTION=pgsql
   DB_HOST=(del paso 2 - sin el prefijo postgres://)
   DB_PORT=5432
   DB_DATABASE=(del paso 2)
   DB_USERNAME=(del paso 2)
   DB_PASSWORD=(del paso 2)
   
   JWT_SECRET=(genera una clave aleatoria)
   ```

5. **Desplegar:**
   - Click "Create Web Service"
   - Render construirá y desplegará tu app automáticamente
   - Espera a que termine (5-10 minutos)

6. **Ejecutar migraciones:**
   - Ve a "Shell" en el dashboard de Render
   - Ejecuta: `php artisan migrate --force`
   - Si tienes datos, importa el SQL desde el shell

---

### 2. **Railway** 🚂

**Ventajas:**
- ✅ $5 de créditos gratis por mes (suficiente para apps pequeñas)
- ✅ Base de datos MySQL incluida
- ✅ Despliegue automático desde GitHub
- ✅ No se "duerme" como Render

**Limitaciones:**
- Los créditos se agotan rápido si la app tiene mucho tráfico
- Después de agotar créditos, necesitas pagar

**Pasos:**

1. Ve a https://railway.app
2. Regístrate con GitHub
3. Click "New Project" → "Deploy from GitHub repo"
4. Selecciona tu repositorio
5. Railway detectará automáticamente que es Laravel
6. Agrega una base de datos MySQL desde "New" → "Database" → "MySQL"
7. Configura las variables de entorno (igual que Render)
8. Railway desplegará automáticamente

---

### 3. **Fly.io** ✈️

**Ventajas:**
- ✅ Plan gratuito generoso
- ✅ Muy rápido
- ✅ No se duerme

**Desventajas:**
- Requiere más configuración técnica
- Necesitas crear un archivo `fly.toml`

**Pasos:**

1. Instala Fly CLI: https://fly.io/docs/hands-on/install-flyctl/
2. Ejecuta: `fly launch`
3. Sigue las instrucciones
4. Para MySQL, usa un servicio externo como PlanetScale (gratis) o Railway

---

### 4. **InfinityFree / 000webhost** (Hosting tradicional)

**Ventajas:**
- ✅ Completamente gratis
- ✅ Sin límites de tiempo

**Desventajas:**
- ⚠️ Publicidad en tu sitio (a menos que pagues)
- ⚠️ Menos recursos
- ⚠️ Puede ser más lento
- ⚠️ Necesitas subir archivos manualmente (FTP)

**Pasos:**

1. Regístrate en https://infinityfree.net o https://www.000webhost.com
2. Crea un sitio web
3. Sube tus archivos por FTP
4. Configura la base de datos MySQL desde el panel de control
5. Actualiza el `.env` con las credenciales

---

## 📋 Preparación de tu Código

Antes de desplegar, asegúrate de:

1. **Tener tu código en GitHub:**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin https://github.com/tu-usuario/tu-repo.git
   git push -u origin main
   ```

2. **Verificar que `.env` NO esté en el repositorio:**
   - El archivo `.env` debe estar en `.gitignore` (ya debería estarlo)
   - Las variables de entorno se configuran en la plataforma

3. **Crear un archivo `render.yaml` (opcional, para Render):**
   ```yaml
   services:
     - type: web
       name: slep-chiloe-app
       env: php
       buildCommand: composer install --no-dev --optimize-autoloader
       startCommand: php artisan serve --host=0.0.0.0 --port=$PORT
       envVars:
         - key: APP_ENV
           value: production
         - key: APP_DEBUG
           value: false
   ```

---

## 🔧 Comandos Útiles Post-Despliegue

Una vez desplegado, ejecuta estos comandos desde el shell de la plataforma:

```bash
# Limpiar caché
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Ejecutar migraciones
php artisan migrate --force

# Crear enlace de storage (si usas archivos)
php artisan storage:link

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🎯 Recomendación Final

**Para empezar rápido:** Usa **Render**
- Es la opción más fácil
- Tiene base de datos incluida
- Despliegue automático desde GitHub
- El "sueño" después de 15 minutos no es problema para pruebas

**Para producción seria:** Considera pagar $7/mes en Render o usar Railway con créditos

---

## 📝 Notas Importantes

1. **Base de datos:** Necesitarás importar tu `slep_chiloe.sql` después de crear la BD
2. **Archivos:** Si tu app guarda archivos en `storage/`, considera usar S3 o similar
3. **SSL:** Todas estas plataformas ofrecen SSL gratuito
4. **Dominio:** Puedes conectar tu propio dominio en todas las plataformas

---

## 🆘 Solución de Problemas

**Error de conexión a BD:**
- Verifica que las variables de entorno estén correctas
- Asegúrate de que la BD esté creada y corriendo

**Error 500:**
- Revisa los logs en la plataforma
- Verifica que `APP_KEY` esté configurado
- Ejecuta `php artisan config:clear`

**La app no carga:**
- Verifica que el `startCommand` sea correcto
- Revisa que el puerto sea `$PORT` (variable de entorno de la plataforma)
