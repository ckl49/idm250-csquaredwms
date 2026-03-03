<?php
    session_start();

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    include '../db.php';
    include '../lib/orders.php';
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

    // -------------------------------------------------------
    // Shared helper — forwards JSON requests to external API
    // -------------------------------------------------------
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

    // -------------------------------------------------------
    // GET — proxy read from external API
    // -------------------------------------------------------
    if ($method === 'GET') {
        $result = call_external_api('GET');
        http_response_code(isset($result['error']) ? 502 : 200);
        echo json_encode($result);

    // -------------------------------------------------------
    // POST — ship or create
    // -------------------------------------------------------
    } elseif ($method === 'POST') {
        $data   = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? 'create';

        if ($action === 'ship') {
            $item_ids     = $data['item_ids']     ?? [];
            $reference    = $data['reference']    ?? '';
            $ship_date    = $data['ship_date']    ?? '';
            $trailer_name = $data['trailer_name'] ?? '';

            if (empty($item_ids)) {
                http_response_code(400);
                echo json_encode(['error' => 'Bad Request', 'details' => 'item_ids are required']);
                exit;
            }

            // -------------------------------------------------------
            // TEMPORARY — local inventory deduction and status tracking
            // until the other team merges their API fix.
            //
            // Once merged, DELETE the foreach loop and INSERT block below,
            // then UNCOMMENT the call_external_api block beneath them.
            // -------------------------------------------------------

            // DELETE this foreach loop after merge:
            $errors = [];
            foreach ($item_ids as $id) {
                $id   = intval($id);
                $stmt = $conn->prepare("UPDATE inventory SET quant_instock = quant_instock - 1 WHERE id = ?");
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    $errors[] = "Failed to update inventory for id {$id}";
                }
            }

            // DELETE this INSERT block after merge:
            $stmt = $conn->prepare("INSERT IGNORE INTO orders (reference_numb, status, ship_date, trailer_name) VALUES (?, 'shipped', ?, ?)");
            $stmt->bind_param("sss", $reference, $ship_date, $trailer_name);
            $stmt->execute();

            // UNCOMMENT this block after merge:
            // $external_result = call_external_api('POST', [
            //     'reference'      => $reference,
            //     'date'           => $ship_date,
            //     'truck'          => $trailer_name,
            //     'selected_items' => $item_ids
            // ]);

            http_response_code(empty($errors) ? 200 : 422);
            echo json_encode(empty($errors)
                ? ['success' => true,  'message' => 'Order shipped and inventory updated.']
                : ['success' => false, 'error'   => implode(', ', $errors)]
            );

        } else {
            // CREATE — validate then forward to external API
            if (!isset($data['reference_numb'], $data['ship_date'], $data['trailer_name'], $data['items'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Bad Request', 'details' => 'Missing required field(s)']);
                exit;
            }

            $result = call_external_api('POST', [
                'reference'      => $data['reference_numb'],
                'date'           => $data['ship_date'],
                'truck'          => $data['trailer_name'],
                'selected_items' => array_column($data['items'], 'item_id')
            ]);

            http_response_code(isset($result['success']) && $result['success'] ? 201 : 422);
            echo json_encode($result);
        }

    // -------------------------------------------------------
    // PUT — forward update to external API
    // -------------------------------------------------------
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

    // -------------------------------------------------------
    // DELETE — forward delete to external API
    // -------------------------------------------------------
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