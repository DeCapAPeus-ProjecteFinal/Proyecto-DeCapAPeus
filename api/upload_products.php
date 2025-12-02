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

// 1. VALIDACIÓN DEL MÉTODO
// Solo permitimos peticiones POST para la subida de archivos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Método no permitido, use POST'], 405);
}

if (!isset($_FILES['productFile'])) {
    respond(['error' => 'No se ha enviado ningún fichero con el campo productFile'], 400);
}

// 2. VALIDACIÓN DEL FICHERO
// Verificamos errores de subida, tamaño máximo (10MB) y extensión permitida
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

// 3. GUARDADO TEMPORAL
// Movemos el fichero a la carpeta uploads/ con un nombre único para procesarlo
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        respond(['error' => 'No se pudo crear el directorio de uploads'], 500);
    }
}

$uniqueName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$destPath = $uploadDir . '/' . $uniqueName;
if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
    $error = error_get_last();
    respond(['error' => 'No se pudo guardar el fichero en el servidor. Detalles: ' . ($error['message'] ?? '')], 500);
}

// 4. LECTURA CON PHPSPREADSHEET
// Cargamos el fichero según su extensión (CSV, XLS, XLSX)
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

// 5. NORMALIZACIÓN DE CABECERAS
// Identificamos las columnas permitiendo variaciones de nombre (ej: precio, preu, price)
$headersRaw = array_shift($rows); // primera fila
$headers = [];
foreach ($headersRaw as $col => $val) {
    $h = trim(mb_strtolower((string) $val));
    $headers[$col] = $h;
}

// Mapeo de nombres de columna esperados (acepta variaciones en ES/CAT/EN)
$keyMap = [
    'sku' => ['sku', 'codi', 'id', 'codigo'],
    'nom' => ['nom', 'nombre', 'name', 'title'],
    'descripcio' => ['descripcio', 'descripcion', 'descripció', 'description', 'desc'],
    'img' => ['img', 'imagen', 'image', 'foto'],
    'preu' => ['preu', 'precio', 'price', 'preu€'],
    'estoc' => ['estoc', 'stock', 'cantidad', 'qty', 'quantitat'],
    'destacado' => ['destacado', 'featured', 'destacat']
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
$skuToIndex = []; // Mapa para localizar índice por SKU

foreach ($existingList as $index => $p) {
    if (isset($p['id']) && is_numeric($p['id']) && (int) $p['id'] > $maxId) {
        $maxId = (int) $p['id'];
    }
    if (!empty($p['sku'])) {
        $skuToIndex[strtolower((string) $p['sku'])] = $index;
    }
}

$imported = 0;
$updated = 0; // Contador de actualizados
$ignored = 0;
$errors = [];
$updates = []; // Array para info de updates
$newProducts = [];

// 6. PROCESAMIENTO DE FILAS
// Iteramos cada fila para validar datos y construir el array de productos nuevos
$rowNum = 1; // header was row 1
foreach ($rows as $r) {
    $rowNum++;
    $item = [];
    $allEmpty = true;
    foreach ($colToKey as $col => $key) {
        $val = isset($r[$col]) ? trim((string) $r[$col]) : '';
        if ($val !== '')
            $allEmpty = false;
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
        $price = (float) $priceNorm;
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
        $stock = (int) $stockNorm;
    } else {
        $stock = 0;
    }

    // SKU: si falta, generar uno automático
    $sku = $item['sku'] ?? '';
    if ($sku === '') {
        $sku = 'IMP-' . time() . '-' . bin2hex(random_bytes(3));
    }

    $skuKey = strtolower($sku);

    $destacadoRaw = $item['destacado'] ?? '';
    $destacado = false;

    if ($destacadoRaw !== '') {
        $v = strtolower($destacadoRaw);
        $destacado = in_array($v, ['1', 'si', 'sí', 'true', 'yes'], true);
    }

    // Comprobar si existe para ACTUALIZAR
    if (isset($skuToIndex[$skuKey])) {
        $idx = $skuToIndex[$skuKey];

        // Si es -1, significa que ya lo hemos añadido como NUEVO en este mismo fichero.
        if ($idx === -1) {
            $ignored++;
            $errors[] = ['row' => $rowNum, 'reason' => 'Duplicado en el mismo fichero (ignorado): ' . $sku];
            continue;
        }

        // Actualizar producto existente de la DB
        $oldProduct = $existingList[$idx];

        // Mantenemos ID original, actualizamos el resto
        $updatedProduct = array_merge($oldProduct, [
            'nom' => $name,
            'descripcio' => $item['descripcio'] ?? $oldProduct['descripcio'],
            'img' => $item['img'] ?? $oldProduct['img'],
            'preu' => $price,
            'estoc' => $stock,
            'destacado' => $destacado
        ]);

        $existingList[$idx] = $updatedProduct;
        $updates[] = ['sku' => $sku, 'nom' => $name];
        $updated++;

        // Marcar como procesado en este fichero para ignorar duplicados posteriores
        $skuToIndex[$skuKey] = -1;

        continue; // Pasamos al siguiente
    }

    // Si es nuevo:
    $maxId++;
    $new = [
        'id' => $maxId,
        'sku' => $sku,
        'nom' => $name,
        'descripcio' => $item['descripcio'] ?? '',
        'img' => $item['img'] ?? '',
        'preu' => $price,
        'estoc' => $stock,
        'destacado' => $destacado
    ];

    $newProducts[] = $new;

    // Marcar este SKU como "recién añadido" para evitar duplicados posteriores en el mismo fichero
    $skuToIndex[$skuKey] = -1;

    $imported++;
}

// 7. ACTUALIZACIÓN DEL JSON
// Hacemos backup, fusionamos datos y guardamos en products.json
// Al guardar en el volumen dockerizado, json-server detectará el cambio automáticamente
if (is_file($dataFile)) {
    $backupDir = __DIR__ . '/../data/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    $bak = $backupDir . '/products.json.bak.' . date('Ymd_His');
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
    'updated' => $updated,
    'updates' => $updates,
    'ignored' => $ignored,
    'errors' => $errors,
    'data_file' => realpath($dataFile)
];

respond($result);