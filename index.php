<?php
// Check if this is an API request
$request_uri = $_SERVER['REQUEST_URI'];
if (strpos($request_uri, '/backend/api/') === 0) {
    // Let the API handle itself
    return false;
}

// For the main landing page, redirect to frontend
header('Location: /frontend/index.html');
exit;
?>