<?php 
    session_start();

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    include '../db.php';
    include '../lib/mpl.php';
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
        $sql    = "SELECT * FROM mpl";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $mpl_array = [];
            while ($row = $result->fetch_assoc()) {
                $mpl_array[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $mpl_array]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No MPL records found']);
        }

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

            $result = receive_mpl($conn, $mpl_id);

            if ($result['success']) {
                $encoded_payload = json_encode([
                    'mpl_id' => $mpl_id,
                    'status' => 'received'
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
                $response = file_get_contents('https://theirsite.com/api/mpl.php', false, $context);
            }

            http_response_code($result['success'] ? 200 : 422);
            echo json_encode($result);

        } else {
            // CREATE — receiving MPL from supplier
            if (!isset($data['order_number']) || !isset($data['truck_number']) || !isset($data['expected_delivery'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Bad Request', 'details' => 'Missing required field(s)']);
                exit;
            }

            $order_number      = $data['order_number'];
            $truck_number      = $data['truck_number'];
            $expected_delivery = $data['expected_delivery'];
            $units             = $data['units'] ?? [];

            $sql  = "INSERT INTO mpl (order_number, truck_number, expected_delivery) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $order_number, $truck_number, $expected_delivery);

            if ($stmt->execute()) {
                $mpl_id = $conn->insert_id;

                foreach ($units as $unit) {
                    $ficha       = $unit['ficha'] ?? null;
                    $quantity    = $unit['quantity'] ?? null;
                    $description = $unit['description'] ?? null;

                    $sql  = "INSERT INTO mpl_items (mpl_id, ficha, quantity, description) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iiis", $mpl_id, $ficha, $quantity, $description);
                    $stmt->execute();
                }

                http_response_code(201);
                echo json_encode(['success' => true, 'data' => 'New MPL created successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
            }
        }

    } elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = $data['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID is required for deletion']);
            exit;
        }

        $sql  = "DELETE FROM mpl WHERE id = ?";
        $stmt = $conn->prepare($sql);
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
?>