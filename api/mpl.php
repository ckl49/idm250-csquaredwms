<?php
ini_set('display_errors', 1); error_reporting(E_ALL);

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include '../db.php';
include '../lib/mpl.php';
include '../auth.php';

$env = file_exists('../.env') ? parse_ini_file('../.env') : [];

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

// ── GET: fetch live from external API ────────────────────────────────────────
if ($method === 'GET') {
    $url    = "https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api-mpl.php";
    $opts   = ['http' => ['method' => 'GET', 
               'header' => "x-api-key: " . ($env['X_API_KEY']) . "\r\n",
               'ignore_errors' => true]];
    $result = json_decode(file_get_contents($url, false, stream_context_create($opts)), true);

    if (!empty($result) && is_array($result)) {
        echo json_encode(['success' => true, 'data' => $result['data']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No MPL records found']);
    }

// ── POST ─────────────────────────────────────────────────────────────────────
} elseif ($method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'create';

    if ($action === 'receive') {
        $mpl_id = isset($data['mpl_id']) ? (int)$data['mpl_id'] : null;

        if (!$mpl_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'details' => 'mpl_id is required']);
            exit;
        }

        error_log("Calling receive_mpl with mpl_id: " . $mpl_id);
        $result = receive_mpl($conn, $mpl_id);

        // Notify external API only if receive succeeded
        if ($result['success']) {
            $api_key = $env['X_API_KEY'] ?? 'test';
        
            $options = [
                'http' => [
                    'method'        => 'POST',  
                    'header'        => "Content-Type: application/json\r\n" .
                                       "x-api-key: $api_key\r\n",
                    'content'       => json_encode([
                        'id'     => $mpl_id,
                        'status' => 'accepted'  // their API accepts 'pending' or 'accepted'
                    ]),
                    'ignore_errors' => true
                ]
            ];
        
            file_get_contents(
                'https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api-mpl.php',
                false,
                stream_context_create($options)
            );
        }

        http_response_code($result['success'] ? 200 : 422);
        echo json_encode($result);

    } else {
        // CREATE
        if (!isset($data['reference_numb']) || !isset($data['trailer_name']) || !isset($data['ship_date'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'details' => 'Missing required field(s): reference_numb, trailer_name, ship_date']);
            exit;
        }

        $reference_numb = $data['reference_numb'];
        $trailer_name   = $data['trailer_name'];
        $ship_date      = $data['ship_date'];
        $items          = $data['items'] ?? [];

        $stmt = $conn->prepare("INSERT INTO mpl (reference_numb, trailer_name, ship_date, status) VALUES (?, ?, ?, 'draft')");
        $stmt->bind_param("sss", $reference_numb, $trailer_name, $ship_date);

        if ($stmt->execute()) {
            foreach ($items as $item) {
                $item_id = $item['item_id'] ?? null;
                $stmt2   = $conn->prepare("UPDATE mpl SET item_id = ? WHERE reference_numb = ? AND item_id IS NULL LIMIT 1");
                $stmt2->bind_param("is", $item_id, $reference_numb);
                $stmt2->execute();
            }

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'MPL created successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        }
    }

// ── DELETE ────────────────────────────────────────────────────────────────────
} elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = $data['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID is required for deletion']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM mpl WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => "MPL $id deleted successfully"]);
        } else {
            echo json_encode(['success' => false, 'error' => "No MPL found with ID $id"]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}