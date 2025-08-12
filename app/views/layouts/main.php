<?php
// app/views/layouts/main.php
include 'partials/header.php'; // Asegúrate de que esta ruta sea correcta

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shelter Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <?php if (isset($content)): ?>
            <?= $content ?>
        <?php endif; ?>
    </div>

    <!-- Incluir jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <!-- Incluir Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<?php include 'partials/footer.php'; // Asegúrate de que esta ruta sea correcta ?>
</body>
</html>