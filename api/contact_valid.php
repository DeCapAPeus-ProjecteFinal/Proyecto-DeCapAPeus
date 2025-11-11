<?php
// Inicializar variables
$nombre = $apellidos = $email = $telefono = $mensaje = "";
$terminos = false;
$errores = [];

// Función para limpiar datos
function limpiar($dato)
{
    return htmlspecialchars(stripslashes(trim($dato)));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = limpiar($_POST["nombre"] ?? "");
    $apellidos = limpiar($_POST["apellidos"] ?? "");
    $email = limpiar($_POST["email"] ?? "");
    $telefono = limpiar($_POST["telefono"] ?? "");
    $mensaje = limpiar($_POST["mensaje"] ?? "");
    $terminos = isset($_POST["terminos"]);

    // Validaciones
    if (empty($nombre)) $errores["nombre"] = "Por favor, ingrese su nombre.";
    if (empty($apellidos)) $errores["apellidos"] = "Por favor, ingrese sus apellidos.";
    if (empty($email)) $errores["email"] = "Por favor, ingrese su email.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores["email"] = "Email no válido.";
    if (empty($telefono)) $errores["telefono"] = "Por favor, ingrese su teléfono.";
    elseif (!preg_match("/^[0-9]{9}$/", $telefono)) $errores["telefono"] = "Teléfono inválido.";
    if (empty($mensaje)) $errores["mensaje"] = "Por favor, ingrese su mensaje.";
    if (!$terminos) $errores["terminos"] = "Debe aceptar los términos.";
}

// Construir query string solo si hay errores
$query = '';
if (!empty($errores)) {
    $datos = [
        'nombre' => $nombre,
        'apellidos' => $apellidos,
        'email' => $email,
        'telefono' => $telefono,
        'mensaje' => $mensaje,
        'terminos' => $terminos ? 'on' : ''
    ];
    $query = '?' . http_build_query($datos);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado Formulario</title>
    <link rel="stylesheet" href="/styles/valid.css">
</head>

<body>
    <div class="result-container">
        <?php if (!empty($errores)): ?>
            <h2 class="error-text">Se encontraron errores en el formulario:</h2>
            <ul class="error-list">
                <?php foreach ($errores as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="http://localhost:5173/src/pages/contact.html<?= $query ?>" class="button">Volver al formulario</a>
        <?php else: ?>
            <h2 class="success">✅ Formulario enviado con éxito.</h2>
        <?php endif; ?>

        <br>
        <a href="http://localhost:5173/" class="button">Ir a la página de inicio</a>
    </div>
</body>

</html>