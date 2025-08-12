#!/bin/bash

# Nombre del proyecto
PROJECT_NAME="shelter-management-system"

# Crear la estructura de directorios
mkdir -p "$PROJECT_NAME/app/controllers"
mkdir -p "$PROJECT_NAME/app/models"
mkdir -p "$PROJECT_NAME/app/views/layouts"
mkdir -p "$PROJECT_NAME/app/views/partials"
mkdir -p "$PROJECT_NAME/app/views/public"
mkdir -p "$PROJECT_NAME/app/views/private"
mkdir -p "$PROJECT_NAME/app/helpers"
mkdir -p "$PROJECT_NAME/public/css"
mkdir -p "$PROJECT_NAME/public/js"
mkdir -p "$PROJECT_NAME/public/uploads"
mkdir -p "$PROJECT_NAME/config"

# Crear archivos de configuración básicos
touch "$PROJECT_NAME/config/database.php"
touch "$PROJECT_NAME/config/.env"

# Crear archivo composer.json básico
cat <<EOF > "$PROJECT_NAME/composer.json"
{
    "require": {}
}
EOF

# Crear archivo index.php básico
cat <<EOF > "$PROJECT_NAME/index.php"
<?php
// index.php
require 'vendor/autoload.php';
require 'config/database.php';
?>
EOF

# Crear archivo .env de ejemplo
cat <<EOF > "$PROJECT_NAME/config/.env"
DB_HOST=localhost
DB_NAME=shelter_management
DB_USER=root
DB_PASS=
EOF

# Crear archivo database.php de ejemplo
cat <<EOF > "$PROJECT_NAME/config/database.php"
<?php
// config/database.php
\$dbHost = getenv('DB_HOST');
\$dbName = getenv('DB_NAME');
\$dbUser = getenv('DB_USER');
\$dbPass = getenv('DB_PASS');

\$dsn = "mysql:host=\$dbHost;dbname=\$dbName;charset=utf8mb4";
\$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    \$pdo = new PDO(\$dsn, \$dbUser, \$dbPass, \$options);
} catch (\PDOException \$e) {
    throw new \PDOException(\$e->getMessage(), (int)\$e->getCode());
}
?>
EOF

echo "Estructura del proyecto '$PROJECT_NAME' creada exitosamente."