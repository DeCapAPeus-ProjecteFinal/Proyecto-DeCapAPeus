<?php
// Permitir llamadas desde otros puertos (Vite)

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Inicializar variables
$nombre = $apellidos = $email = $telefono = $mensaje = "";
$terminos = false;
$errores = [];
$exito = "";

// Procesar el formulario al hacer submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Función para limpiar datos
    function limpiar($dato)
    {
        return htmlspecialchars(stripslashes(trim($dato)));
    }

    $nombre = limpiar($_POST["nombre"] ?? "");
    $apellidos = limpiar($_POST["apellidos"] ?? "");
    $email = limpiar($_POST["email"] ?? "");
    $telefono = limpiar($_POST["telefono"] ?? "");
    $mensaje = limpiar($_POST["mensaje"] ?? "");
    $terminos = isset($_POST["terminos"]) ? true : false;

    // Validaciones
    if (empty($nombre)) $errores["nombre"] = "Por favor, ingrese su nombre.";
    if (empty($apellidos)) $errores["apellidos"] = "Por favor, ingrese sus apellidos.";
    if (empty($email)) $errores["email"] = "Por favor, ingrese su email.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores["email"] = "Por favor, ingrese un email válido.";
    if (empty($telefono)) $errores["telefono"] = "Por favor, ingrese su teléfono.";
    elseif (!preg_match("/^[0-9]{9}$/", $telefono)) $errores["telefono"] = "El teléfono debe tener 9 dígitos numéricos.";
    if (empty($mensaje)) $errores["mensaje"] = "Por favor, ingrese su mensaje.";
    if (!$terminos) $errores["terminos"] = "Debe aceptar los términos y condiciones.";

    // Si no hay errores, mensaje de éxito
    if (empty($errores)) {
        $exito = "✅ ¡Formulario enviado con éxito!";
        // Limpiar variables
        $nombre = $apellidos = $email = $telefono = $mensaje = "";
        $terminos = false;
    }
}

// Devolver JSON
echo json_encode([
    "errores" => $errores,
    "exito" => $exito
]);
exit;
