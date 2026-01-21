#!/bin/bash

# Script para importar la base de datos a RDS en AWS
# Este script debe ejecutarse en el servidor EC2 o desde tu PC con acceso a RDS

echo "=========================================="
echo "Importar Base de Datos a RDS en AWS"
echo "=========================================="
echo ""

# Obtener variables de entorno de Elastic Beanstalk
if [ -f /opt/elasticbeanstalk/bin/get-config ]; then
    DB_HOST=$(/opt/elasticbeanstalk/bin/get-config environment -k DB_HOST)
    DB_PORT=$(/opt/elasticbeanstalk/bin/get-config environment -k DB_PORT)
    DB_DATABASE=$(/opt/elasticbeanstalk/bin/get-config environment -k DB_DATABASE)
    DB_USERNAME=$(/opt/elasticbeanstalk/bin/get-config environment -k DB_USERNAME)
    DB_PASSWORD=$(/opt/elasticbeanstalk/bin/get-config environment -k DB_PASSWORD)
else
    echo "No se encontró Elastic Beanstalk. Usando variables de entorno manuales..."
    read -p "DB_HOST: " DB_HOST
    read -p "DB_PORT [3306]: " DB_PORT
    DB_PORT=${DB_PORT:-3306}
    read -p "DB_DATABASE: " DB_DATABASE
    read -p "DB_USERNAME: " DB_USERNAME
    read -s -p "DB_PASSWORD: " DB_PASSWORD
    echo ""
fi

echo ""
echo "Configuración:"
echo "  Host: $DB_HOST"
echo "  Puerto: $DB_PORT"
echo "  Base de datos: $DB_DATABASE"
echo "  Usuario: $DB_USERNAME"
echo ""

# Solicitar archivo SQL a importar
if [ -z "$1" ]; then
    read -p "Ruta del archivo SQL a importar: " SQL_FILE
else
    SQL_FILE="$1"
fi

if [ ! -f "$SQL_FILE" ]; then
    echo "ERROR: El archivo $SQL_FILE no existe"
    exit 1
fi

echo ""
echo "Archivo: $SQL_FILE"
echo "Tamaño: $(du -h "$SQL_FILE" | cut -f1)"
echo ""

read -p "¿Continuar con la importación? (s/n): " CONFIRM
if [ "$CONFIRM" != "s" ] && [ "$CONFIRM" != "S" ]; then
    echo "Importación cancelada"
    exit 0
fi

echo ""
echo "Importando datos..."
echo ""

# Verificar si mysql está disponible
if command -v mysql &> /dev/null; then
    MYSQL_CMD="mysql"
elif [ -f /usr/bin/mysql ]; then
    MYSQL_CMD="/usr/bin/mysql"
else
    echo "ERROR: mysql no está instalado"
    echo "Instalando mysql client..."
    
    # Intentar instalar según el sistema
    if command -v dnf &> /dev/null; then
        sudo dnf install -y mysql 2>/dev/null || sudo dnf install -y mariadb 2>/dev/null
    elif command -v yum &> /dev/null; then
        sudo yum install -y mysql 2>/dev/null || sudo yum install -y mariadb 2>/dev/null
    elif command -v apt-get &> /dev/null; then
        sudo apt-get update && sudo apt-get install -y mysql-client
    fi
    
    if command -v mysql &> /dev/null; then
        MYSQL_CMD="mysql"
    else
        echo "ERROR: No se pudo instalar mysql client"
        echo "Usando PHP para importar..."
        
        # Usar PHP PDO para importar
        cat > /tmp/import_db.php << 'PHPEOF'
<?php
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: 3306;
$database = getenv('DB_DATABASE');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$sqlFile = $argv[1];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado a la base de datos\n";
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir en sentencias individuales
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $count = 0;
    foreach ($statements as $statement) {
        if (empty($statement) || preg_match('/^--/', $statement)) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $count++;
            if ($count % 100 == 0) {
                echo "Procesadas $count sentencias...\n";
            }
        } catch (PDOException $e) {
            // Ignorar errores de "Duplicate entry" y "Table already exists"
            if (strpos($e->getMessage(), 'Duplicate entry') === false && 
                strpos($e->getMessage(), 'already exists') === false) {
                echo "Error en sentencia: " . substr($statement, 0, 100) . "...\n";
                echo "  " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nImportación completada. $count sentencias procesadas.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
PHPEOF
        
        export DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
        php /tmp/import_db.php "$SQL_FILE"
        rm /tmp/import_db.php
        exit $?
    fi
fi

# Importar usando mysql
export MYSQL_PWD="$DB_PASSWORD"
$MYSQL_CMD -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" < "$SQL_FILE"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Importación completada exitosamente!"
else
    echo ""
    echo "❌ ERROR: La importación falló"
    exit 1
fi

