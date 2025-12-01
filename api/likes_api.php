<?php
// api/likes_api.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$likesFile = __DIR__ . '/../data/likes.json';

// Inicializar archivo si no existe
if (!is_file($likesFile)) {
    file_put_contents($likesFile, json_encode(['likes' => []]));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'count';
    $productId = (int)($_GET['productId'] ?? 0);
    if ($productId === 0) {
        respond(['error' => 'productId es requerido'], 400);
    }

    $data = json_decode(file_get_contents($likesFile), true) ?: ['likes' => []];
    $likes = $data['likes'] ?? [];

    if ($action === 'count') {
        // Obtener número total de likes
        $productLikes = array_filter($likes, fn($like) => $like['product_id'] == $productId);
        $count = count($productLikes);

        respond(['productId' => $productId, 'likes' => $count]);
    } elseif ($action === 'check') {
        // Verificar si el usuario actual ha dado like
        $userId = $_COOKIE['user_id'] ?? null;
        if (!$userId) {
            respond(['liked' => false]);
        }

        $hasLiked = false;
        foreach ($likes as $like) {
            if ($like['product_id'] == $productId && $like['user_id'] == $userId) {
                $hasLiked = true;
                break;
            }
        }

        respond(['productId' => $productId, 'liked' => $hasLiked]);
    }
} elseif ($method === 'POST') {
    // Añadir o quitar like
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = (int)($input['productId'] ?? 0);
    $userId = $_COOKIE['user_id'] ?? null;

    if ($productId === 0) {
        respond(['error' => 'productId es requerido'], 400);
    }

    if (!$userId) {
        respond(['error' => 'Usuario no autenticado'], 401);
    }

    $data = json_decode(file_get_contents($likesFile), true) ?: ['likes' => []];
    $likes = $data['likes'];

    // Verificar si el usuario ya dio like a este producto
    $existingIndex = -1;
    foreach ($likes as $i => $like) {
        if ($like['product_id'] == $productId && $like['user_id'] == $userId) {
            $existingIndex = $i;
            break;
        }
    }

    if ($existingIndex !== -1) {
        // Ya dio like, removerlo (toggle)
        unset($likes[$existingIndex]);
        $likes = array_values($likes); // Reindexar
        $action = 'removed';
    } else {
        // Añadir nuevo like
        $newId = 1;
        if (!empty($likes)) {
            $ids = array_column($likes, 'id');
            $newId = max($ids) + 1;
        }

        $newLike = [
            'id' => $newId,
            'product_id' => $productId,
            'user_id' => (int)$userId,
            'date' => date('Y-m-d H:i:s')
        ];

        $likes[] = $newLike;
        $action = 'added';
    }

    $data['likes'] = $likes;
    file_put_contents($likesFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Recalcular el total de likes para el producto
    $productLikes = array_filter($likes, fn($like) => $like['product_id'] == $productId);
    $count = count($productLikes);

    respond(['success' => true, 'productId' => $productId, 'action' => $action, 'likes' => $count]);
} else {
    respond(['error' => 'Método no permitido'], 405);
}

function respond($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
