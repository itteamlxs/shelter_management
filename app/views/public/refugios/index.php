<?php
// app/views/public/refugios/index.php
include 'app/views/layouts/main.php';

// Asegurarse de que $refugios esté definido y sea un array
if (!isset($refugios) || !is_array($refugios)) {
    $refugios = [];
}

$content = '
<h1>Lista de Refugios</h1>
<a href="/shelter-management-system/public/refugios/create" class="btn btn-primary">Agregar Refugio</a>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Ubicación</th>
            <th>Capacidad Máxima</th>
            <th>Capacidad Ocupada</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
';

foreach ($refugios as $refugio) {
    $content .= '
        <tr>
            <td>' . htmlspecialchars($refugio['refugio_id']) . '</td>
            <td>' . htmlspecialchars($refugio['nombre_refugio']) . '</td>
            <td>' . htmlspecialchars($refugio['ubicacion']) . '</td>
            <td>' . htmlspecialchars($refugio['capacidad_maxima']) . '</td>
            <td>' . htmlspecialchars($refugio['capacidad_ocupada']) . '</td>
            <td>' . htmlspecialchars($refugio['estado']) . '</td>
            <td>
                <a href="/shelter-management-system/public/refugios/view?id=' . urlencode($refugio['refugio_id']) . '" class="btn btn-info">Ver</a>
                <a href="/shelter-management-system/public/refugios/edit?id=' . urlencode($refugio['refugio_id']) . '" class="btn btn-warning">Editar</a>
                <a href="/shelter-management-system/public/refugios/delete?id=' . urlencode($refugio['refugio_id']) . '" class="btn btn-danger">Eliminar</a>
            </td>
        </tr>
    ';
}

$content .= '
    </tbody>
</table>
';

echo $content;