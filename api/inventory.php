<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include '../db.php';
include '../auth.php';

$has_session = isset($_SESSION['username']);
$headers     = getallheaders();
$has_api_key = isset($headers['x-api-key']) || isset($headers['X-Api-Key']);

if ($has_session) {
    // internal — session is enough
} elseif ($has_api_key) {
    check_api_key($env);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: return all inventory ──────────────────────────────────────────────
if ($method === 'GET') {
    $sql    = "SELECT id AS inventory_id, order_id, unit_numb, ficha, sku, quantity, description1, description2, quantity_unit, footage_quantity FROM inventory";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        echo json_encode(['success' => true, 'data' => $rows]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No inventory records found']);
    }

// ── POST: insert new inventory item ───────────────────────────────────────
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $required = ['order_id','ficha', 'unit_numb', 'sku', 'quantity', 'description1', 'quantity_unit', 'footage_quantity'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'details' => "Missing required field: $field"]);
            exit;
        }
    }

    $order_id        = (int)$data['order_id'];
    $ficha            = $data['ficha'];
    $unit_numb        = $data['unit_numb'];
    $sku              = $data['sku'];
    $quantity         = (int)$data['quantity'];
    $description1     = $data['description1'];
    $description2     = $data['description2'] ?? '';
    $quantity_unit    = $data['quantity_unit'];
    $footage_quantity = (float)$data['footage_quantity'];

    $stmt = $conn->prepare(
        "INSERT INTO inventory (order_id, unit_numb, ficha, sku, quantity, description1, description2, quantity_unit, footage_quantity)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issississd", $order_id, $unit_numb,
        $ficha, $sku, $quantity, $description1, $description2, $quantity_unit, $footage_quantity);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Item created', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

// ── PUT: update existing inventory item ───────────────────────────────────
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required field: id']);
        exit;
    }

    $id               = (int)$data['id'];
    $order_id         = isset($data['order_id']) ? (int)$data['order_id'] : null;
    $unit_numb        = $data['unit_numb']      ?? null;
    $ficha            = $data['ficha']            ?? null;
    $sku              = $data['sku']              ?? null;
    $quantity         = isset($data['quantity'])         ? (int)$data['quantity']         : null;
    $description1     = $data['description1']     ?? null;
    $description2     = $data['description2']     ?? null;
    $quantity_unit    = $data['quantity_unit']    ?? null;
    $footage_quantity = isset($data['footage_quantity']) ? (float)$data['footage_quantity'] : null;

    $stmt = $conn->prepare(
        "UPDATE inventory SET order_id = ?, unit_numb = ?,
            ficha = ?, sku = ?, quantity = ?, description1 = ?,
            description2 = ?, quantity_unit = ?, footage_quantity = ?
         WHERE id = ?"
    );
    $stmt->bind_param("isssisssdi", $order_id, $unit_numb,
        $ficha, $sku, $quantity, $description1,
        $description2, $quantity_unit, $footage_quantity, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item updated']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

// ── DELETE ─────────────────────────────────────────────────────────────────
} elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = isset($data['id']) ? (int)$data['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required field: id']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item deleted']);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
