<?php
// index.php en la raíz del proyecto

// Incluir archivo de configuración de la base de datos
require 'config/database.php';

// Definir las rutas posibles
$routes = [
    'refugios' => 'app/routes/refugios-router.php',
    // Aquí puedes agregar más rutas si tienes otros módulos
];

// Obtener la ruta actual sin el prefijo '/'
$currentRoute = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Redirigir a la página de inicio si la ruta no coincide con ninguna ruta definida
if (!array_key_exists($currentRoute, $routes)) {
    $currentRoute = 'refugios';
}

// Incluir el archivo de enrutamiento correspondiente
$routerFile = $routes[$currentRoute];
include $routerFile;