<?php
session_start();
require $_SERVER['DOCUMENT_ROOT'] . '/../api/json_connect.php';


function limpiar($dato)
{
    return htmlspecialchars(stripslashes(trim($dato)));
}

$userId = $_SESSION['user_id'] ?? $_COOKIE['user_id'] ?? null;

if (!$userId) {
    header("Location: login.php");
    exit;
}

$user = getUser($userId);
$errores = [];
$ok = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 👉 POST names in English
    $userName = limpiar($_POST["username"] ?? "");
    $name     = limpiar($_POST["firstname"] ?? "");
    $surname  = limpiar($_POST["lastname"] ?? "");
    $email    = limpiar($_POST["email"] ?? "");

    // VALIDACIONES

    // Username
    // Username
    if (empty($userName)) {
        $errores["username"] = "Por favor, ingrese un nombre de usuario.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $userName)) {
        $errores["username"] = "El nombre de usuario debe tener 3-20 caracteres, letras, números o _.";
    } elseif ($userName !== $user['nom_usuari'] && getUsers($userName)) {
        $errores["username"] = "Este nombre de usuario ya existe.";
    }

    // Nombre
    if (empty($name)) {
        $errores["firstname"] = "Por favor, ingrese su nombre.";
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]{2,50}$/u', $name)) {
        $errores["firstname"] = "Nombre inválido (solo letras y espacios, 2-50 caracteres).";
    }

    // Apellidos
    if (empty($surname)) {
        $errores["lastname"] = "Por favor, ingrese sus apellidos.";
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]{2,50}$/u', $surname)) {
        $errores["lastname"] = "Apellidos inválidos (solo letras y espacios, 2-50 caracteres).";
    }

    // Email
    if (empty($email)) {
        $errores["email"] = "Por favor, ingrese su email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores["email"] = "Email inválido.";
    }

    // SI TODO OK → actualizar
    if (empty($errores)) {
        $data = [];

        if (!empty($userName)) $data["nom_usuari"] = $userName;
        if (!empty($name))     $data["nom"] = $name;
        if (!empty($surname))  $data["cognoms"] = $surname;
        if (!empty($email))    $data["email"] = $email;

        if (!empty($data)) {
            updateUser($userId, $data);
            $user = getUser($userId);
            $ok = "¡Perfil actualizado correctamente!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Perfil de <?= htmlspecialchars($user['nom_usuari']); ?></title>
    <link rel="stylesheet" href="/styles/styles.css">
</head>

<body>

    <div class="profile-form-container">
        <h2>Perfil de <?= htmlspecialchars($user['nom_usuari']); ?></h2>

        <?php if (!empty($errores)): ?>
            <ul class="profile-form-error">
                <?php foreach ($errores as $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($ok)): ?>
            <div class="profile-success-message"><?= htmlspecialchars($ok) ?></div>
        <?php endif; ?>

        <!-- Datos del perfil -->
        <p><strong>Nombre de Usuario:</strong> <?= htmlspecialchars($user['nom_usuari']); ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
        <p><strong>Nombre:</strong> <?= htmlspecialchars($user['nom']); ?></p>
        <p><strong>Apellidos:</strong> <?= htmlspecialchars($user['cognoms']); ?></p>

        <h3>Actualizar perfil</h3>
        <form method="POST">
            <div class="profile-form-group">
                <label class="profile-form-label" for="username">Nombre de usuario</label>
                <input type="text" id="username" name="username" class="profile-form-input" value="<?= htmlspecialchars($user['nom_usuari']); ?>">
            </div>

            <div class="profile-form-group">
                <label class="profile-form-label" for="firstname">Nombre</label>
                <input type="text" id="firstname" name="firstname" class="profile-form-input" value="<?= htmlspecialchars($user['nom']); ?>">
            </div>

            <div class="profile-form-group">
                <label class="profile-form-label" for="lastname">Apellidos</label>
                <input type="text" id="lastname" name="lastname" class="profile-form-input" value="<?= htmlspecialchars($user['cognoms']); ?>">
            </div>

            <div class="profile-form-group">
                <label class="profile-form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="profile-form-input" value="<?= htmlspecialchars($user['email']); ?>">
            </div>

            <button class="profile-btn" type="submit">Actualizar perfil</button>
        </form>

        <br>
        <a class="profile-link" href="http://localhost/auth/logout.php">Cerrar sesión</a>
        <a class="profile-link" href="http://localhost">Volver al inicio</a> <!-- Canviar mas tarde -->
    </div>

</body>

</html>