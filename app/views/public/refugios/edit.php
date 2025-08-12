<?php
// app/views/public/refugios/edit.php
include 'app/views/layouts/main.php';

// Asegurarse de que $refugio esté definido y sea un array
if (!isset($refugio) || !is_array($refugio)) {
    $refugio = [];
}

$content = '
<h1>Editar Refugio</h1>
<form method="POST" action="/shelter-management-system/public/refugios/update?id=' . urlencode($refugio['refugio_id']) . '">
    <div class="form-group">
        <label for="nombre_refugio">Nombre del Refugio</label>
        <input type="text" class="form-control" id="nombre_refugio" name="nombre_refugio" value="' . htmlspecialchars($refugio['nombre_refugio']) . '" required>
    </div>
    <div class="form-group">
        <label for="ubicacion">Ubicación</label>
        <input type="text" class="form-control" id="ubicacion" name="ubicacion" value="' . htmlspecialchars($refugio['ubicacion']) . '" required>
    </div>
    <div class="form-group">
        <label for="lat">Latitud</label>
        <input type="text" class="form-control" id="lat" name="lat" value="' . htmlspecialchars($refugio['lat']) . '">
    </div>
    <div class="form-group">
        <label for="lng">Longitud</label>
        <input type="text" class="form-control" id="lng" name="lng" value="' . htmlspecialchars($refugio['lng']) . '">
    </div>
    <div class="form-group">
        <label for="fecha_apertura">Fecha de Apertura</label>
        <input type="date" class="form-control" id="fecha_apertura" name="fecha_apertura" value="' . htmlspecialchars($refugio['fecha_apertura']) . '" required>
    </div>
    <div class="form-group">
        <label for="capacidad_maxima">Capacidad Máxima</label>
        <input type="number" class="form-control" id="capacidad_maxima" name="capacidad_maxima" value="' . htmlspecialchars($refugio['capacidad_maxima']) . '" required>
    </div>
    <button type="submit" class="btn btn-primary">Actualizar</button>
</form>
';

echo $content;