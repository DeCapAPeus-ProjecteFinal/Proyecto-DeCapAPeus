<?php
// api/check_comments_update.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$commentsFile = __DIR__ . '/../data/comments.json';

if (!is_file($commentsFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Archivo de comentarios no encontrado']);
    exit;
}

$lastKnownTimestamp = (int)($_GET['lastTimestamp'] ?? 0);

$actualTimestamp = filemtime($commentsFile);

if ($actualTimestamp > $lastKnownTimestamp) {
    // Hubo un cambio, devolver el timestamp actual y un indicador
    echo json_encode([
        'changed' => true,
        'timestamp' => $actualTimestamp,
        'productId' => (int)($_GET['productId'] ?? 0)
    ]);
} else {
    // No hubo cambio
    echo json_encode([
        'changed' => false,
        'timestamp' => $actualTimestamp
    ]);
}
