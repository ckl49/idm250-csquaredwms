<?php
    session_start();

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    include '../db.php';
    include '../auth.php';

    $has_session = isset($_SESSION['username']);
    $headers     = getallheaders();
    $has_api_key = isset($headers['api-key'])    || isset($headers['Api-Key'])
                || isset($headers['x-api-key']) || isset($headers['X-Api-Key']);

    if ($has_session) {
        // internal — session is enough
    } elseif ($has_api_key) {
        check_api_key($env);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    define('EXTERNAL_API', 'https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api_orders.php');
    define('EXTERNAL_KEY', 'test');

    function call_external_api(string $method, ?array $body = null): array {
        $options = [
            'http' => [
                'method'        => $method,
                'header'        => "Content-Type: application/json\r\nx-api-key: " . EXTERNAL_KEY . "\r\n",
                'ignore_errors' => true,
            ]
        ];

        if ($body !== null) {
            $options['http']['content'] = json_encode($body);
        }

        $context  = stream_context_create($options);
        $response = @file_get_contents(EXTERNAL_API, false, $context);

        if ($response === false) {
            return ['success' => false, 'error' => 'Could not reach external API'];
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : ['success' => false, 'error' => 'Invalid response from external API'];
    }

    $method = $_SERVER['REQUEST_METHOD'];

    // GET — from external API
    if ($method === 'GET') {
        $result = call_external_api('GET');
        http_response_code(isset($result['error']) ? 502 : 200);
        echo json_encode($result);

    // POST — ship or create then goes to external API
    } elseif ($method === 'POST') {
        $data   = json_decode(file_get_contents('php://input'), true);

        $action = $data['action'] ?? 'create';

 if ($action === 'ship') {
    $item_ids = $data['item_ids'] ?? [];

    if (empty($item_ids)) {
        http_response_code(400);
        echo json_encode(['error' => 'Bad Request', 'details' => 'item_ids are required']);
        exit;
    }

    // Update status to 'shipped' in your local orders table for each item_id
    $errors = [];

    foreach ($item_ids as $id) {
        $id   = intval($id);
        $stmt = $conn->prepare("UPDATE orders SET status = 'shipped' WHERE id = ?");
        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            $errors[] = "item_id {$id}: " . $stmt->error;
        }
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
    } else {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Order shipped successfully']);
    }
        } else {
            // CREATE — validate then go to external API
            if (!isset($data['reference_numb'], $data['ship_date'], $data['trailer_name'], $data['items'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Bad Request', 'details' => 'Missing required field(s)']);
                exit;
            }

            $result = call_external_api('POST', [
                'action'         => 'create',
                'reference_numb' => $data['reference_numb'],
                'ship_date'      => $data['ship_date'],
                'trailer_name'   => $data['trailer_name'],
                'items'          => $data['items']
            ]);

            http_response_code(isset($result['success']) && $result['success'] ? 201 : 422);
            echo json_encode($result);
        }

    // PUT — forward update to external API
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID is required for update']);
            exit;
        }

        $result = call_external_api('PUT', $data);
        http_response_code(isset($result['success']) && $result['success'] ? 200 : 422);
        echo json_encode($result);

    // DELETE — forward delete to external API
    } elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID is required for deletion']);
            exit;
        }

        $result = call_external_api('DELETE', $data);
        http_response_code(isset($result['success']) && $result['success'] ? 200 : 422);
        echo json_encode($result);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }
?>