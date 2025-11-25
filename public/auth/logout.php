<?php
session_start();

// Eliminar cookie
setcookie('user_id', '', time() - 3600, "/");

// Destruir sesión
session_destroy();

// Mensaje de salida
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cerrando sesión</title>
    <!-- Redirigir después de 2 segundos -->
    <meta http-equiv="refresh" content="2;url=http://localhost/auth/login.php">
    <style>
        body { font-family: sans-serif; text-align: center; padding-top: 50px; }
        .mensaje { color: #006600; font-size: 1.2em; }
    </style>
</head>
<body>
    <p class="mensaje">Sesión cerrada correctamente. Redirigiendo a login...</p>
</body>
</html>';
exit;
