<?php
// api/upload_products.php
// Recibe un fichero Excel/CSV con productos, lo procesa con PhpSpreadsheet,
// valida filas y actualiza data/products.json. Devuelve JSON con resumen.

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

function respond($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Config
$uploadDir = __DIR__ . '/../uploads';
$dataFile = __DIR__ . '/../data/products.json';
$allowedExt = ['xlsx', 'xls', 'csv'];
$maxSize = 10 * 1024 * 1024; // 10 MB server-side

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Método no permitido, use POST'], 405);
}

if (!isset($_FILES['productFile'])) {
    respond(['error' => 'No se ha enviado ningún fichero con el campo productFile'], 400);
}

$file = $_FILES['productFile'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    respond(['error' => 'Error en la subida', 'code' => $file['error']], 400);
}

if ($file['size'] > $maxSize) {
    respond(['error' => 'El fichero excede el tamaño máximo permitido (10 MB)'], 400);
}

$origName = $file['name'];
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    respond(['error' => 'Extensión no permitida. Usa .xlsx, .xls o .csv'], 400);
}

// Asegurar uploads dir
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        respond(['error' => 'No se pudo crear el directorio de uploads'], 500);
    }
}

$uniqueName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$destPath = $uploadDir . '/' . $uniqueName;
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    respond(['error' => 'No se pudo guardar el fichero en el servidor'], 500);
}

// Cargar el fichero con PhpSpreadsheet según extensión
try {
    if ($ext === 'csv') {
        $reader = new CsvReader();
        // Ajustes razonables para CSV
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
        $reader->setSheetIndex(0);
    } elseif ($ext === 'xls') {
        $reader = new XlsReader();
    } else {
        $reader = new XlsxReader();
    }
    $spreadsheet = $reader->load($destPath);
} catch (Exception $e) {
    respond(['error' => 'Error al leer el fichero: ' . $e->getMessage()], 500);
}

$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, true, true, true);
if (count($rows) < 1) {
    respond(['error' => 'Fichero vacío o sin filas'], 400);
}

// Normalizar encabezados de la primera fila
$headersRaw = array_shift($rows); // primera fila
$headers = [];
foreach ($headersRaw as $col => $val) {
    $h = trim(mb_strtolower((string)$val));
    $headers[$col] = $h;
}

// Mapeo de nombres de columna esperados (acepta variaciones en ES/CAT/EN)
$keyMap = [
    'sku' => ['sku', 'codi', 'id', 'codigo'],
    'nom' => ['nom', 'nombre', 'name', 'title'],
    'descripcio' => ['descripcio', 'descripcion', 'descripció', 'description', 'desc'],
    'img' => ['img', 'imagen', 'image', 'foto'],
    'preu' => ['preu', 'precio', 'price', 'preu€'],
    'estoc' => ['estoc', 'stock', 'cantidad', 'qty', 'quantitat']
];

// Invertir headers map: map column letter to target key if matches
$colToKey = [];
foreach ($headers as $col => $h) {
    foreach ($keyMap as $target => $variants) {
        foreach ($variants as $v) {
            if ($h === $v) {
                $colToKey[$col] = $target;
            }
        }
    }
}

// Requerir al menos nombre y precio/estoc (precio preferiblemente)
if (!in_array('nom', $colToKey, true)) {
    respond(['error' => 'El fichero debe incluir al menos la columna Nombre/Nom/Name'], 400);
}

// Cargar productos existentes
$existing = ['productes' => []];
if (is_file($dataFile)) {
    $jsonRaw = file_get_contents($dataFile);
    $existing = json_decode($jsonRaw, true) ?: ['productes' => []];
}

$existingList = $existing['productes'] ?? [];
$maxId = 0;
$existingSkus = [];
foreach ($existingList as $p) {
    if (isset($p['id']) && is_numeric($p['id']) && (int)$p['id'] > $maxId) $maxId = (int)$p['id'];
    if (!empty($p['sku'])) $existingSkus[strtolower((string)$p['sku'])] = true;
    if (!empty($p['nom'])) $existingNames[strtolower((string)$p['nom'])] = true;
}

$imported = 0;
$ignored = 0;
$errors = [];
$newProducts = [];

$rowNum = 1; // header was row 1
foreach ($rows as $r) {
    $rowNum++;
    $item = [];
    $allEmpty = true;
    foreach ($colToKey as $col => $key) {
        $val = isset($r[$col]) ? trim((string)$r[$col]) : '';
        if ($val !== '') $allEmpty = false;
        $item[$key] = $val;
    }

    if ($allEmpty) {
        $ignored++;
        $errors[] = ['row' => $rowNum, 'reason' => 'Fila vacía'];
        continue;
    }

    // Nombre obligatorio
    $name = $item['nom'] ?? '';
    if ($name === '') {
        $ignored++;
        $errors[] = ['row' => $rowNum, 'reason' => 'Falta nombre'];
        continue;
    }

    // Precio (si existe) -> float
    $priceRaw = $item['preu'] ?? '';
    if ($priceRaw !== '') {
        // Normalizar comas
        $priceNorm = str_replace([',', '€', ' '], ['.', '', ''], $priceRaw);
        if (!is_numeric($priceNorm)) {
            $ignored++;
            $errors[] = ['row' => $rowNum, 'reason' => 'Precio no numérico: ' . $priceRaw];
            continue;
        }
        $price = (float)$priceNorm;
    } else {
        $price = 0.0;
    }

    // Estoc (si existe) -> int
    $stockRaw = $item['estoc'] ?? '';
    if ($stockRaw !== '') {
        $stockNorm = str_replace(['.', ','], ['', ''], $stockRaw);
        if (!is_numeric($stockNorm)) {
            $ignored++;
            $errors[] = ['row' => $rowNum, 'reason' => 'Estoc no numérico: ' . $stockRaw];
            continue;
        }
        $stock = (int)$stockNorm;
    } else {
        $stock = 0;
    }

    // SKU: si falta, generar uno
    $sku = $item['sku'] ?? '';
    if ($sku === '') {
        $sku = 'IMP-' . time() . '-' . bin2hex(random_bytes(3));
    }

    // Evitar duplicados por SKU (case-insensitive)
    if (isset($existingSkus[strtolower($sku)])) {
        $ignored++;
        $errors[] = ['row' => $rowNum, 'reason' => 'Duplicado por SKU: ' . $sku];
        continue;
    }

    $maxId++;
    $new = [
        'id' => $maxId,
        'sku' => $sku,
        'nom' => $name,
        'descripcio' => $item['descripcio'] ?? '',
        'img' => $item['img'] ?? '',
        'preu' => $price,
        'estoc' => $stock
    ];

    $newProducts[] = $new;
    // marcar sku para evitar duplicados dentro del mismo import
    $existingSkus[strtolower($sku)] = true;
    $imported++;
}

// Backup del JSON antes de sobrescribir
if (is_file($dataFile)) {
    $bak = $dataFile . '.bak.' . date('Ymd_His');
    copy($dataFile, $bak);
}

// Fusionar y guardar
$finalList = array_values(array_merge($existingList, $newProducts));
$out = ['productes' => $finalList];

$saved = file_put_contents($dataFile, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
if ($saved === false) {
    respond(['error' => 'No se pudo guardar data/products.json'], 500);
}

$result = [
    'ok' => true,
    'uploaded_file' => basename($destPath),
    'imported' => $imported,
    'ignored' => $ignored,
    'errors' => $errors,
    'data_file' => realpath($dataFile)
];

// Opcional: enviar a json-server si se pasó push=1 en el form
$push = isset($_POST['push']) && ($_POST['push'] === '1' || $_POST['push'] === 'true');
if ($push && $imported > 0) {
    $jsonServerUrl = 'http://json-server:3000/productes';
    $pushResults = ['sent' => 0, 'failed' => 0, 'details' => []];
    foreach ($newProducts as $p) {
        $ch = curl_init($jsonServerUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($p));
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err || ($code < 200 || $code >= 300)) {
            $pushResults['failed']++;
            $pushResults['details'][] = ['sku' => $p['sku'], 'code' => $code, 'error' => $err, 'resp' => $resp];
        } else {
            $pushResults['sent']++;
        }
    }
    $result['pushed_to_json_server'] = $pushResults;
}

respond($result);
