<?php
session_start();
header("Content-Type: application/json");

// Rutas
$COMMENTS_URL = $_SERVER['DOCUMENT_ROOT'] . '/../data/comments.json';
$USERS_URL = $_SERVER['DOCUMENT_ROOT'] . '/../data/users.json';

// Crear archivo si no existe
if (!file_exists($COMMENTS_URL)) {
    file_put_contents($COMMENTS_URL, json_encode(['comentaris' => []], JSON_PRETTY_PRINT));
}

// Funciones
function getAllComments()
{
    global $COMMENTS_URL;
    $data = json_decode(file_get_contents($COMMENTS_URL), true);
    return $data['comentaris'] ?? [];
}

function saveAllComments($comments)
{
    global $COMMENTS_URL;
    file_put_contents($COMMENTS_URL, json_encode(['comentaris' => $comments], JSON_PRETTY_PRINT));
}

// Usuario autenticado
$userId = $_SESSION['user_id'] ?? null;

// Acción
$action = $_GET['action'] ?? null;

/* ───────────────────────────────────────────────
   LISTAR COMENTARIOS
   GET ?action=list&productId=XX
─────────────────────────────────────────────── */
if ($action === "list") {
    $productId = $_GET['productId'] ?? null;
    if (!$productId) {
        echo json_encode(['error' => 'Falta productId']);
        exit;
    }

    // Filtrar comentarios de ese producto
    $comments = array_filter(getAllComments(), fn($c) => $c['product_id'] == $productId);

    // Cargar usuarios
    $usersData = file_exists($USERS_URL) ? json_decode(file_get_contents($USERS_URL), true) : [];

    $userMap = [];
    foreach ($usersData['usuaris'] ?? [] as $u) {
        $userMap[$u['id']] = $u['nom_usuari'];
    }

    // Añadir username y propiedad
    $comments = array_map(function ($c) use ($userMap, $userId) {
        $c['username'] = $userMap[$c['user_id']] ?? "Usuari desconegut";
        $c['esPropietario'] = ($c['user_id'] == $userId);
        return $c;
    }, $comments);

    echo json_encode(array_values($comments));
    exit;
}

/* ───────────────────────────────────────────────
   AÑADIR COMENTARIO
   POST ?action=add
─────────────────────────────────────────────── */
if ($action === "add" && $_SERVER['REQUEST_METHOD'] === "POST") {

    if (!$userId) {
        echo json_encode(['error' => 'Has d’iniciar sessió']);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);

    $productId = $input['productId'] ?? null;
    $text = trim($input['comment'] ?? "");
    $rating = intval($input['rating'] ?? 0);

    if (!$productId || $text === "" || $rating < 1 || $rating > 5) {
        echo json_encode(['error' => 'Dades incompletes']);
        exit;
    }

    $comments = getAllComments();

    $newId = count($comments) ? max(array_column($comments, 'id')) + 1 : 1;

    $newComment = [
        "id"         => $newId,
        "product_id" => intval($productId),
        "user_id"    => $userId,
        "text"       => htmlspecialchars($text),
        "rating"     => $rating,
        "date"       => date("Y-m-d H:i")
    ];

    $comments[] = $newComment;
    saveAllComments($comments);

    echo json_encode(['success' => true, 'comment' => $newComment]);
    exit;
}

/* ───────────────────────────────────────────────
   ELIMINAR COMENTARIO
   POST ?action=delete&id=XX
─────────────────────────────────────────────── */
if ($action === "delete" && $_SERVER['REQUEST_METHOD'] === "POST") {

    if (!$userId) {
        echo json_encode(['error' => 'Has d’iniciar sessió']);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);
    $commentId = $input['id'] ?? null;

    if (!$commentId) {
        echo json_encode(['error' => 'Falta id']);
        exit;
    }

    $comments = getAllComments();

    foreach ($comments as $i => $c) {
        if ($c['id'] == $commentId) {

            if ($c['user_id'] != $userId) {
                echo json_encode(['error' => 'No tens permís per eliminar aquest comentari']);
                exit;
            }

            unset($comments[$i]);
            saveAllComments(array_values($comments));

            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['error' => 'Comentari no trobat']);
    exit;
}

/* ───────────────────────────────────────────────
   EDITAR COMENTARIO
   POST ?action=edit
─────────────────────────────────────────────── */
if ($action === "update" && $_SERVER['REQUEST_METHOD'] === "POST") {

    if (!$userId) {
        echo json_encode(['error' => 'Has d’iniciar sessió']);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);

    $commentId = $input['id'] ?? null;
    $text = trim($input['text'] ?? "");
    $rating = intval($input['rating'] ?? 1);

    if (!$commentId || $text === "" || $rating < 1 || $rating > 5) {
        echo json_encode(['error' => 'Dades incompletes']);
        exit;
    }

    $comments = getAllComments();

    foreach ($comments as &$c) {
        if ($c['id'] == $commentId) {

            if ($c['user_id'] != $userId) {
                echo json_encode(['error' => 'No tens permís per editar aquest comentari']);
                exit;
            }

            $c['text'] = htmlspecialchars($text);
            $c['rating'] = $rating;

            saveAllComments($comments);

            echo json_encode(['success' => true, 'comment' => $c]);
            exit;
        }
    }

    echo json_encode(['error' => 'Comentari no trobat']);
    exit;
}

// Acción desconocida
echo json_encode(['error' => 'Acció no vàlida']);
