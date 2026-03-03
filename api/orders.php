<?php

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    require_once '../db.php';
    require_once '../lib/orders.php';
    // require_once '../auth.php';
    // check_api_key($env);

<<<<<<< Updated upstream
        // comment out the auth and env stuff if you want to test without the API KEY
    
    $method = $_SERVER['REQUEST_METHOD'];

=======
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

    // GETfrom external API
>>>>>>> Stashed changes
    if ($method === 'GET') {
        // echo json_encode(['success' => true, 'data' => $message]);

<<<<<<< Updated upstream
        $sql = "SELECT * FROM orders";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $orders_array = [];
            while($row = $result->fetch_assoc()) {
                $orders_array[] = $row;
=======
    // ship or create
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

            // DELETE the foreach loop
            $errors = [];
            foreach ($item_ids as $id) {
                $id   = intval($id);
                $stmt = $conn->prepare("UPDATE inventory SET quant_instock = quant_instock - 1 WHERE id = ?");
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    $errors[] = "Failed to update inventory for id {$id}";
                }
            }
            // DELETE
            $stmt = $conn->prepare("INSERT IGNORE INTO orders (reference_numb, status, ship_date, trailer_name) VALUES (?, 'shipped', ?, ?)");
            $stmt->bind_param("sss", $reference, $ship_date, $trailer_name);
            $stmt->execute();
            // END DELETE

            // UNCOMMENT:
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
>>>>>>> Stashed changes
            }
            echo json_encode(['success' => true, 'data' => $orders_array]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No orders found']);
        }
        
    } elseif ($method === 'POST') {
        // get data from other team
        $data = json_decode(file_get_contents('php://input'), true);

<<<<<<< Updated upstream
        if (!isset($data['ficha']) || !isset($data['description1']) || !isset($data['description2']) || !isset($data['quantity']) || !isset($data['quantity_unit']) || !isset($data['footage_quantity'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'details' => 'Missing required field(s)']);
            exit;
=======
            $result = call_external_api('POST', [
                'reference'      => $data['reference_numb'],
                'date'           => $data['ship_date'],
                'truck'          => $data['trailer_name'],
                'selected_items' => array_column($data['items'], 'item_id')
            ]);
>>>>>>> Stashed changes

        } else {
        $id             = $data['order_id'];
        $ficha          = $data['ficha'];
        $description1    = $data['description1']; 
        $description2    = $data['description2']; 
        $quantity    = $data['quantity']; 
        $quantity_unit    = $data['quantity_unit']; 
        $footage_quantity  = $data['footage_quantity']; 
        
            $sql = "INSERT INTO orders (ficha, description1, description2, quantity, quantity_unit, footage_quantity) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issisi", $ficha, $description1, $description2, $quantity, $quantity_unit, $footage_quantity);
    
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => 'New item created successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
            }
        }
        } elseif ($method === 'PATCH') {
        // Confirm an order — updates order status + deducts from inventory
        $data     = json_decode(file_get_contents('php://input'), true);
        $order_id = isset($data['order_id']) ? (int)$data['order_id'] : null;

        if (!$order_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'details' => 'order_id is required']);
            exit;
        }

<<<<<<< Updated upstream
        $result = confirm_order($conn, $order_id);

        http_response_code($result['success'] ? 200 : 422);
        echo json_encode($result);
=======
    // forward update to external API
>>>>>>> Stashed changes
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'error' => 'ID is required for update']);
            exit;
        }

      
        $sql = "UPDATE orders SET description1 = ?, description2 = ?, quantity = ?, quantity_unit = ?, footage_quantity = ? WHERE order_id = ?";
        
        $stmt = $conn->prepare($sql);
        
    
        $stmt->bind_param("ssisii", $data['description1'], $data['description2'], $data['quantity'],  $data['quantity_unit'],  $data['footage_quantity'],  $data['id']
    );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }

   } elseif ($method === 'DELETE') {

<<<<<<< Updated upstream
=======
    // delete to external API
    } elseif ($method === 'DELETE') {
>>>>>>> Stashed changes
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = $data['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID is required for deletion']);
            exit;
        }

        $sql = "DELETE FROM orders WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => "Order $id deleted successfully"]);
            } else {
                echo json_encode(['success' => false, 'error' => "No order found with ID $id"]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }
?>