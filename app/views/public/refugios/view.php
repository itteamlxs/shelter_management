<?php
// app/views/public/refugios/view.php
include 'app/views/layouts/main.php';

// Asegurarse de que $refugio esté definido y sea un array
if (!isset($refugio) || !is_array($refugio)) {
    $refugio = [];
}

$content = '
<h1>Detalle del Refugio</h1>
<p><strong>ID:</strong> ' . htmlspecialchars($refugio['refugio_id']) . '</p>
<p><strong>Nombre:</strong> ' . htmlspecialchars($refugio['nombre_refugio']) . '</p>
<p><strong>Ubicación:</strong> ' . htmlspecialchars($refugio['ubicacion']) . '</p>
<p><strong>Latitud:</strong> ' . htmlspecialchars($refugio['lat']) . '</p>
<p><strong>Longitud:</strong> ' . htmlspecialchars($refugio['lng']) . '</p>
<p><strong>Fecha de Apertura:</strong> ' . htmlspecialchars($refugio['fecha_apertura']) . '</p>
<p><strong>Capacidad Máxima:</strong> ' . htmlspecialchars($refugio['capacidad_maxima']) . '</p>
<p><strong>Capacidad Ocupada:</strong> ' . htmlspecialchars($refugio['capacidad_ocupada']) . '</p>
<p><strong>Estado:</strong> ' . htmlspecialchars($refugio['estado']) . '</p>
<a href="/shelter-management-system/public/refugios" class="btn btn-secondary">Volver</a>
';

echo $content;