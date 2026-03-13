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
    // POST — ship or create
    // -------------------------------------------------------
    } elseif ($method === 'POST') {
        $data   = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? 'create';

     if ($action === 'ship') {
    $order_id     = $data['order_id']    ?? null;
    $item_ids     = $data['item_ids']    ?? [];
    $reference    = $data['reference']   ?? '';
    $ship_date    = $data['ship_date']   ?? '';
    $trailer_name = $data['trailer_name'] ?? '';

    if (!$order_id || empty($item_ids)) {
        http_response_code(400);
        echo json_encode(['error' => 'Bad Request']);
        exit;
    }

    // 1. Deduct from local inventory
    $local_result = ship_order($conn, $order_id, $reference);

    if (!$local_result['success']) {
        http_response_code(422);
        echo json_encode($local_result);
        exit;
    }

    // 2. Notify the other team's API
    $external_url    = "https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api_orders.php";
    $external_opts   = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nx-api-key: test\r\n",
            'content' => json_encode([
                'id'             => $order_id,
                'reference'      => $reference,
                'date'           => $ship_date,
                'truck'          => $trailer_name,
                'status'         => 'shipped',
                'selected_items' => $item_ids
            ]),
            'ignore_errors' => true
        ]
    ];
    $external_result = json_decode(
        file_get_contents($external_url, false, stream_context_create($external_opts)),
        true
    );

    // 3. Persist shipped status locally
    $stmt = $conn->prepare(
        "INSERT INTO orders (reference_numb, status)
         VALUES (?, 'shipped')
         ON DUPLICATE KEY UPDATE status = 'shipped'"
    );
    $stmt->bind_param("s", $reference);
    $stmt->execute();

    http_response_code(200);
    echo json_encode([
        'success'         => true,
        'message'         => 'Order shipped and inventory updated.',
        'external_result' => $external_result  
    ]);
    exit;
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

        $result = call_external_api('PUT', $external_api_url, $external_api_key, $data);
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

        $result = call_external_api('DELETE', $external_api_url, $external_api_key, $data);
        http_response_code(isset($result['success']) && $result['success'] ? 200 : 422);
        echo json_encode($result);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }
?>