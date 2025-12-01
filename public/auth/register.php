<?php
session_start();
require $_SERVER['DOCUMENT_ROOT'] . '/../api/json_connect.php';

function limpiar($dato)
{
    return htmlspecialchars(stripslashes(trim($dato)));
}

$errores = [];
$ok = "";

// Inicializar valores del formulario
$userName = $email = $name = $surname = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Limpiar datos
    $userName = limpiar($_POST["userName"] ?? "");
    $email = limpiar($_POST["email"] ?? "");
    $name = limpiar($_POST["name"] ?? "");
    $surname = limpiar($_POST["surname"] ?? "");
    $passwd = $_POST["passwd"] ?? "";

    // VALIDACIONES
    if (empty($userName)) {
        $errores["userName"] = "Por favor, ingrese un nombre de usuario.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $userName)) {
        $errores["userName"] = "Debe tener entre 3-20 caracteres y solo letras, números o _.";
    } elseif (getUsers($userName)) {
        $errores["userName"] = "El usuario ya existe.";
    }

    if (empty($email)) {
        $errores["email"] = "Por favor, ingrese su email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores["email"] = "Email no válido.";
    }

    if (empty($passwd)) {
        $errores["passwd"] = "Por favor, ingrese una contraseña.";
    } elseif (strlen($passwd) < 6) {
        $errores["passwd"] = "La contraseña debe tener al menos 6 caracteres.";
    }

    if (empty($name)) {
        $errores["name"] = "Por favor, ingrese su nombre.";
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]{2,50}$/u', $name)) {
        $errores["name"] = "El nombre solo puede contener letras y espacios.";
    }

    if (empty($surname)) {
        $errores["surname"] = "Por favor, ingrese sus apellidos.";
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]{2,50}$/u', $surname)) {
        $errores["surname"] = "Los apellidos solo pueden contener letras y espacios.";
    }

    // SI NO HAY ERRORES → REGISTRAR
    if (empty($errores)) {
        $user = [
            "nom_usuari" => $userName,
            "contrasenya" => password_hash($passwd, PASSWORD_DEFAULT),
            "email" => $email,
            "nom" => $name,
            "cognoms" => $surname,
            "data_registre" => date('c')
        ];

        addUser($user);
        $ok = "¡Usuario registrado correctamente!";

        header("refresh:2;url=login.php");
        exit;
    }

    // SI HAY ERRORES → reconstruir query string
    $query = '?' . http_build_query([
        'userName' => $userName,
        'email' => $email,
        'name' => $name,
        'surname' => $surname
    ]);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="/styles/styles.css">
    <?php if (!empty($ok)) : ?>
        <meta http-equiv="refresh" content="2;url=login.php">
    <?php endif; ?>
</head>

<body>
    <div id="header"></div>
    <div class="register-form-container">
        <h2>Registrar</h2>

        <?php if (!empty($ok)) : ?>
            <div class="register-success-message"><?= htmlspecialchars($ok) ?></div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <ul class="register-form-error">
                <?php foreach ($errores as $campo => $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST">
            <div class="register-form-group">
                <label class="register-form-label" for="userName">Nombre de usuario</label>
                <input type="text" id="userName" name="userName" class="register-form-input <?= isset($errores['userName']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($userName); ?>">
            </div>

            <div class="register-form-group">
                <label class="register-form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="register-form-input <?= isset($errores['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($email); ?>">
            </div>

            <div class="register-form-group">
                <label class="register-form-label" for="passwd">Contraseña</label>
                <input type="password" id="passwd" name="passwd" class="register-form-input <?= isset($errores['passwd']) ? 'is-invalid' : '' ?>">
            </div>

            <div class="register-form-group">
                <label class="form-label" for="name">Nombre</label>
                <input type="text" id="name" name="name" class="register-form-input" value="<?= htmlspecialchars($name); ?>">
            </div>

            <div class="register-form-group">
                <label class="register-form-label" for="surname">Apellidos</label>
                <input type="text" id="surname" name="surname" class="register-form-input" value="<?= htmlspecialchars($surname); ?>">
            </div>

            <button class="register-btn" type="submit">Registrar</button>
        </form>

        <p>¿Ya tienes cuenta? <a class="register-link" href="http://localhost/auth/login.php">Iniciar Sesión</a></p>
        <br>
        <a class="register-link" href="http://localhost">Volver al inicio</a>
    </div>
    <div id="footer"></div>
    <script src="/scripts/include-partials.js"></script>
</body>

</html>