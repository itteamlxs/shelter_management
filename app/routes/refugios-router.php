<?php
// app/routes/refugios-router.php

require_once '../../config/database.php';
require_once '../../app/controllers/RefugiosController.php';

$controller = new RefugiosController($pdo);

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        include '../../app/views/public/refugios/create.php';
        break;
    case 'store':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre_refugio' => $_POST['nombre_refugio'],
                'ubicacion' => $_POST['ubicacion'],
                'lat' => $_POST['lat'],
                'lng' => $_POST['lng'],
                'fecha_apertura' => $_POST['fecha_apertura'],
                'capacidad_maxima' => $_POST['capacidad_maxima']
            ];
            $controller->createRefugio($data);
            header('Location: /shelter-management-system/refugios');
        }
        break;
    case 'edit':
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $refugio = $controller->getRefugioById($id);
            if ($refugio) {
                include '../../app/views/public/refugios/edit.php';
            } else {
                echo "Refugio no encontrado.";
            }
        } else {
            echo "ID no proporcionado.";
        }
        break;
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $id = $_POST['id'];
            $data = [
                'nombre_refugio' => $_POST['nombre_refugio'],
                'ubicacion' => $_POST['ubicacion'],
                'lat' => $_POST['lat'],
                'lng' => $_POST['lng'],
                'fecha_apertura' => $_POST['fecha_apertura'],
                'capacidad_maxima' => $_POST['capacidad_maxima']
            ];
            $controller->updateRefugio($id, $data);
            header('Location: /shelter-management-system/refugios');
        }
        break;
    case 'delete':
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $controller->deleteRefugio($id);
            header('Location: /shelter-management-system/refugios');
        } else {
            echo "ID no proporcionado.";
        }
        break;
    case 'view':
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $refugio = $controller->getRefugioById($id);
            if ($refugio) {
                include '../../app/views/public/refugios/view.php';
            } else {
                echo "Refugio no encontrado.";
            }
        } else {
            echo "ID no proporcionado.";
        }
        break;
    default:
        // Listar refugios
        $refugios = $controller->getAllRefugios();
        include '../../app/views/public/refugios/index.php';
        break;
}