<?php
session_start();
require $_SERVER['DOCUMENT_ROOT'] . '/../api/json_connect.php';

function limpiar($dato)
{
    return htmlspecialchars(stripslashes(trim($dato)));
}

$errores = [];
$userName = '';

$redirect_to = $_REQUEST['redirect_to'] ?? '/';

// Validate redirect_to to prevent open redirect vulnerabilities
if (!str_starts_with($redirect_to, '/')) {
    $redirect_to = '/';
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Sanitizar
    $userName = limpiar($_POST["userName"] ?? "");
    $passwd = $_POST["passwd"] ?? "";

    // Validaciones
    if (empty($userName)) {
        $errores["userName"] = "Por favor, ingrese su nombre de usuario.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $userName)) {
        $errores["userName"] = "Nombre de usuario inválido.";
    }

    if (empty($passwd)) {
        $errores["passwd"] = "Por favor, ingrese su contraseña.";
    }

    // Si no hay errores de validación → comprobar usuario
    if (empty($errores)) {
        $users = getUsers($userName);

        if (empty($users)) {
            $errores["userName"] = "Usuario no encontrado.";
        } else {
            $user = $users[0];

            if (password_verify($passwd, $user['contrasenya'])) {

                // Login correcto
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                setcookie('user_id', $user['id'], time() + 3600, "/");

                header("Location: " . $redirect_to);
                exit;
            } else {
                $errores["passwd"] = "Contraseña incorrecta.";
            }
        }
    }

    // Si hay errores → reconstruir query string por si quieres usarlo
    if (!empty($errores)) {
        $query = '?' . http_build_query(['userName' => $userName]);
        // header("Location: login.php" . $query);
        // exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="/styles/styles.css">
</head>

<body>
    <div id="header"></div>
    <div class="login-form-container">
        <h2>Login</h2>

        <?php if (!empty($errores)): ?>
            <ul class="login-form-error">
                <?php foreach ($errores as $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST">
            <div class="login-form-group">
                <label class="login-form-label" for="userName">Nombre de usuario</label>
                <input type="text" id="userName" name="userName" class="login-form-input <?= isset($errores['userName']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($userName); ?>">
            </div>

            <div class="login-form-group">
                <label class="login-form-label" for="passwd">Contraseña</label>
                <input type="password" id="passwd" name="passwd" class="login-form-input <?= isset($errores['passwd']) ? 'is-invalid' : '' ?>">
            </div>

            <button class="login-btn" type="submit">Entrar</button>
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirect_to) ?>">
        </form>

        <p>¿No tienes cuenta? <a class="login-link" href="/auth/register.php">Registrarse</a></p>
        <a class="login-link" href="/">Volver al inicio</a>
    </div>
    <div id="footer"></div>
    <script src="/scripts/include-partials.js"></script>
</body>

</html>