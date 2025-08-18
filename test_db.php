<?php
// test_db.php
require __DIR__ . '/vendor/autoload.php'; // Necesita composer require vlucas/phpdotenv

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✅ Conexión exitosa a la BD <b>$db</b><br><br>";

    // Verificar tablas
    $stmt = $pdo->query("SHOW TABLES;");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($tables) {
        echo "Tablas encontradas:<br>";
        foreach ($tables as $table) {
            echo "- $table <br>";
        }
    } else {
        echo "⚠️ No se encontraron tablas en la BD.";
    }

} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage();
}
