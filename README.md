# Sistema de Gestión Presupuestaria - SLEP Chiloé

Sistema web desarrollado en Laravel 11 para la gestión de presupuestos, contratos, órdenes de trabajo y saldos del Servicio Local de Educación Pública de Chiloé.

## 🚀 Características

- Gestión de contratos y proveedores
- Órdenes de trabajo con presupuestos
- Control de saldos y avance financiero
- Generación de actas de recepción conforme
- Importación/exportación de datos
- Sistema de autenticación y autorización

## 📋 Requisitos

- PHP 8.2 o superior
- Composer
- MySQL 8.0 o superior
- Node.js y NPM (para assets)

## 🔧 Instalación

1. Clonar el repositorio
2. Instalar dependencias:
   ```bash
   composer install
   npm install
   ```
3. Configurar `.env`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Configurar base de datos en `.env`
5. Ejecutar migraciones:
   ```bash
   php artisan migrate
   ```
6. Compilar assets:
   ```bash
   npm run build
   ```

## 🔄 Migración a Otro PC

### Requisitos Previos
- XAMPP instalado y MySQL funcionando
- PHP disponible en la línea de comandos
- Archivo `slep_chiloe.sql` en la raíz del proyecto

### Pasos para Migrar

1. **Preparar el nuevo PC:**
   - Instalar XAMPP
   - Copiar todo el proyecto Laravel a la nueva ubicación
   - Asegurarse de que el archivo `slep_chiloe.sql` esté en la raíz del proyecto

2. **Configurar el entorno:**
   ```bash
   # Copiar archivo de entorno
   cp .env.example .env
   
   # Generar clave de aplicación
   php artisan key:generate
   ```

3. **Configurar la base de datos en `.env`:**
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=slep_chiloe
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Iniciar MySQL:**
   - Abre XAMPP Control Panel
   - Haz clic en "Start" junto a MySQL
   - Espera a que aparezca "Running" en verde

5. **Restaurar la base de datos:**
   ```bash
   php restaurar-bd.php
   ```
   Este script:
   - Verifica la conexión a MySQL
   - Crea la base de datos si no existe
   - Importa todos los datos desde `slep_chiloe.sql`
   - Muestra el progreso de la importación

6. **Limpiar caché de Laravel:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

7. **Iniciar el servidor:**
   ```bash
   # Opción 1: Usar el script batch
   iniciar-servidores-simple.bat
   
   # Opción 2: Comando manual
   php artisan serve --host=0.0.0.0 --port=8000
   ```

8. **Verificar que todo funciona:**
   ```bash
   php verificar-bd-y-login.php
   ```
   Este script verifica:
   - Conexión a MySQL
   - Existencia de la base de datos
   - Tablas y datos importados
   - Usuarios en la base de datos

9. **Acceder a la aplicación:**
   - Abre tu navegador
   - Ve a: `http://localhost:8000`

### Scripts Disponibles

- **`restaurar-bd.php`**: Restaura la base de datos desde `slep_chiloe.sql`
- **`iniciar-servidores-simple.bat`**: Inicia el servidor Laravel
- **`verificar-bd-y-login.php`**: Verifica el estado de la base de datos
- **`exportar-bd.bat`**: Exporta la base de datos para crear backups

### Solución de Problemas

**MySQL no inicia:**
- Verifica que el puerto 3306 no esté en uso
- Revisa los logs de MySQL en XAMPP Control Panel
- Asegúrate de que no haya procesos `mysqld.exe` bloqueados

**Error al restaurar la base de datos:**
- Verifica que MySQL esté corriendo: `netstat -ano | findstr ":3306"`
- Asegúrate de que el archivo `slep_chiloe.sql` existe
- Revisa los permisos del usuario MySQL (debe poder crear bases de datos)

**No puedo hacer login:**
- Ejecuta `php verificar-bd-y-login.php` para verificar usuarios
- Limpia la caché: `php artisan config:clear && php artisan cache:clear`
- Verifica que el servidor Laravel esté corriendo

## 🌐 Despliegue

Ver `GUIA_DESPLIEGUE_AWS.md` para instrucciones detalladas de despliegue en AWS.

## 📝 Licencia

MIT
