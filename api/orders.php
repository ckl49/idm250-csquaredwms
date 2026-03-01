<?php
    session_start();
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        echo json_encode(['error' => $errstr, 'file' => $errfile, 'line' => $errline]);
        exit;
    });

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    include '../db.php';
    include '../lib/orders.php';
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

    if ($method === 'GET') {
        $sql    = "SELECT * FROM orders";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $orders_array = [];
            while($row = $result->fetch_assoc()) {
                $orders_array[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $orders_array]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No orders found']);
        }

    } elseif ($method === 'POST') {
        $data   = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? 'create';

        if ($action === 'ship') {
            $order_id = isset($data['order_id']) ? (int)$data['order_id'] : null;

            if (!$order_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Bad Request', 'details' => 'order_id is required']);
                exit;
            }

            $result = ship_order($conn, $order_id);

            if ($result['success']) {
                $encoded_payload = json_encode([
                    'order_id' => $order_id,
                    'status'   => 'shipped'
                ]);

                $options = [
                    'http' => [
                        'method'  => 'POST',
                        'header'  => "Content-Type: application/json\r\n" .
                                     "x-api-key: " . $env['X_API_KEY'] . "\r\n",
                        'content' => $encoded_payload
                    ]
                ];

                $context  = stream_context_create($options);
                $response = file_get_contents('https://theirsite.com/api/orders.php', false, $context);
            }

            http_response_code($result['success'] ? 200 : 422);
            echo json_encode($result);

        } else {
            // CREATE order
            if (!isset($data['reference_numb']) || !isset($data['ship_date']) || !isset($data['trailer_name']) || !isset($data['items'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Bad Request', 'details' => 'Missing required field(s)']);
                exit;
            }

            $reference_numb = $data['reference_numb'];
            $ship_date      = $data['ship_date'];
            $trailer_name   = $data['trailer_name'];
            $items          = $data['items'];

            $sql  = "INSERT INTO orders (reference_numb, ship_date, trailer_name) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $reference_numb, $ship_date, $trailer_name);

            if ($stmt->execute()) {
                $order_id = $conn->insert_id;

                foreach ($items as $item) {
                    $ficha            = $item['ficha'] ?? null;
                    $sku              = $item['sku'] ?? null;
                    $description      = $item['description'] ?? null;
                    $quantity         = $item['quantity'] ?? null;
                    $quantity_unit    = $item['quantity_unit'] ?? null;
                    $footage_quantity = $item['footage_quantity'] ?? null;
                    $uom_primary      = $item['uom_primary'] ?? null;
                    $piece_count      = $item['piece_count'] ?? null;
                    $length_inches    = $item['length_inches'] ?? null;
                    $width_inches     = $item['width_inches'] ?? null;
                    $height_inches    = $item['height_inches'] ?? null;
                    $weight_lbs       = $item['weight_lbs'] ?? null;
                    $assembly         = $item['assembly'] ?? null;
                    $rate             = $item['rate'] ?? null;

                    $sql  = "INSERT INTO orders_items (order_id, ficha, sku, description, quantity, quantity_unit, footage_quantity, uom_primary, piece_count, length_inches, width_inches, height_inches, weight_lbs, assembly, rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iiissiisiddddsd", $order_id, $ficha, $sku, $description, $quantity, $quantity_unit, $footage_quantity, $uom_primary, $piece_count, $length_inches, $width_inches, $height_inches, $weight_lbs, $assembly, $rate);
                    $stmt->execute();
                }

                http_response_code(201);
                echo json_encode(['success' => true, 'data' => 'New order created successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
            }
        }

    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'error' => 'ID is required for update']);
            exit;
        }

        $sql  = "UPDATE orders SET reference_numb = ?, ship_date = ?, trailer_name = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $data['reference_numb'], $data['ship_date'], $data['trailer_name'], $data['id']);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }

    } elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = $data['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID is required for deletion']);
            exit;
        }

        $sql  = "DELETE FROM orders WHERE id = ?";
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