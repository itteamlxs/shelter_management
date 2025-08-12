<?php
// test_db_connection.php

require 'config/database.php';

try {
    // Test the connection
    $stmt = $pdo->query('SELECT VERSION()');
    $version = $stmt->fetchColumn();
    echo "Conexión exitosa. Versión de MySQL: $version\n";
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
}
?>