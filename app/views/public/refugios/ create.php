<?php
// app/views/public/refugios/create.php
include 'app/views/layouts/main.php';

$content = '
<h1>Agregar Refugio</h1>
<form method="POST" action="/shelter-management-system/public/refugios/store">
    <div class="form-group">
        <label for="nombre_refugio">Nombre del Refugio</label>
        <input type="text" class="form-control" id="nombre_refugio" name="nombre_refugio" required>
    </div>
    <div class="form-group">
        <label for="ubicacion">Ubicación</label>
        <input type="text" class="form-control" id="ubicacion" name="ubicacion" required>
    </div>
    <div class="form-group">
        <label for="lat">Latitud</label>
        <input type="text" class="form-control" id="lat" name="lat">
    </div>
    <div class="form-group">
        <label for="lng">Longitud</label>
        <input type="text" class="form-control" id="lng" name="lng">
    </div>
    <div class="form-group">
        <label for="fecha_apertura">Fecha de Apertura</label>
        <input type="date" class="form-control" id="fecha_apertura" name="fecha_apertura" required>
    </div>
    <div class="form-group">
        <label for="capacidad_maxima">Capacidad Máxima</label>
        <input type="number" class="form-control" id="capacidad_maxima" name="capacidad_maxima" required>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
</form>
';

echo $content;