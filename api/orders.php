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

    } elseif ($has_api_key) {
        check_api_key($env);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $external_api_url = "https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api_orders.php";
    $external_api_key = $env['X_API_KEY'];

    function call_external_api(string $method, string $url, string $key, ?array $body = null): array {
        $options = [
            'http' => [
                'method'        => $method,
                'header'        => "Content-Type: application/json\r\nx-api-key: " . $key . "\r\n",
                'ignore_errors' => true,
            ]
        ];

        if ($body !== null) {
            $options['http']['content'] = json_encode($body);
        }

        $context  = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return ['success' => false, 'error' => 'Could not reach external API'];
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : ['success' => false, 'error' => 'Invalid response from external API'];
    }

    $method = $_SERVER['REQUEST_METHOD'];

    // -------------------------------------------------------
    // GET — read from external API
    // -------------------------------------------------------
    if ($method === 'GET') {
        $result = call_external_api('GET', $external_api_url, $external_api_key);
        http_response_code(isset($result['error']) ? 502 : 200);
        echo json_encode($result);

    // -------------------------------------------------------
    // POST — ship
    // -------------------------------------------------------
    } elseif ($method === 'POST') {
        $data   = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? 'create';

        if ($action === 'ship') {
            $order_id     = $data['order_id']    ?? null;
            $item_ids     = $data['item_ids']     ?? [];
            $reference    = $data['reference']    ?? '';
            $ship_date    = $data['ship_date']    ?? '';
            $trailer_name = $data['trailer_name'] ?? '';

            if (!$order_id || empty($item_ids)) {
                http_response_code(400);
                echo json_encode(['error' => 'Bad Request', 'details' => 'order_id and item_ids are required']);
                exit;
            }

            // 1. Fetch their inventory_ids for this reference_numb from external API
            $all_orders    = call_external_api('GET', $external_api_url, $external_api_key);
            $inventory_ids = [];
            foreach ($all_orders as $row) {
                if (!is_array($row)) continue;
                if (($row['reference_numb'] ?? '') === $reference) {
                    if (!empty($row['inventory_id'])) {
                        $inventory_ids[] = (int)$row['inventory_id'];
                    }
                }
            }

            if (empty($inventory_ids)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'No inventory IDs found for reference #' . $reference . ' on external API']);
                exit;
            }

            // 2. Notify external API first — send the order with shipped status
            $external_result = call_external_api('POST', $external_api_url, $external_api_key, [
                'reference'      => $reference,
                'date'           => $ship_date,
                'truck'          => $trailer_name,
                'status'         => 'shipped',
                'selected_items' => $inventory_ids
            ]);

            // 3. Only deduct from local inventory if external API accepted it
            if (!isset($external_result['success']) || $external_result['success'] !== true) {
                http_response_code(502);
                echo json_encode([
                    'success' => false,
                    'error'   => 'External API rejected the shipment — inventory not deducted.',
                    'details' => $external_result
                ]);
                exit;
            }

            // 4. External confirmed — now remove units from local inventory
            $local_result = ship_order($conn, $order_id);

            if (!$local_result['success']) {
                // External was notified but local failed — log this as a warning
                http_response_code(207);
                echo json_encode([
                    'success' => false,
                    'error'   => 'External API was notified but local inventory update failed: ' . $local_result['error']
                ]);
                exit;
            }

            // 5. Mark as shipped in local orders table
            $stmt = $conn->prepare(
                "INSERT INTO orders (reference_numb, status)
                 VALUES (?, 'shipped')
                 ON DUPLICATE KEY UPDATE status = 'shipped'"
            );
            $stmt->bind_param("s", $reference);
            $stmt->execute();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Order shipped and inventory updated.']);

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
            exit;
        }

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

        $result = call_external_api('DELETE', $external_api_url, $external_api_key, $data);
        http_response_code(isset($result['success']) && $result['success'] ? 200 : 422);
        echo json_encode($result);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }
?>