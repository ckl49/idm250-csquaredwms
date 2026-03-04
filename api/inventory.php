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


    // comment out the auth and env stuff if you want to test without the API KEY

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // echo json_encode(['success' => true, 'data' => $message]);

        $sql = "SELECT * FROM inventory";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $inventory_array = [];
            while($row = $result->fetch_assoc()) {
                $inventory_array[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $inventory_array]);

        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        }
        
    } elseif ($method === 'POST') {
        // echo json_encode(value: ['success' => true, 'data' => 'POST request received']);
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id']) || !isset($data['ficha']) || !isset($data['sku']) || !isset($data['description']) || !isset($data['uom_primary']) || !isset($data['piece_count']) || !isset($data['length_inches']) || !isset($data['width_inches']) || !isset($data['height_inches']) || !isset($data['weight_lbs']) || !isset($data['assembly']) || !isset($data['rate'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'details' => 'Missing required field(s)']);
            exit;

        } else {
        $id             = $data['inventory_id'];
        $quantity = $data['quantity'];
        $ficha          = $data['ficha'];
        $sku            = $data['sku'];
        $description1    = $data['description1']; 
        $description2    = $data['description2']; 
        $quantity_unit = $data['quantity_unit'];
        $footage_quantity = $data['footage_quantity'];

            $sql = "INSERT INTO inventory (id, quantity, ficha, sku, description1, description2, quantity_unit, footage_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiisssii", $id, $quantity, $ficha, $sku, $description1, $description2, $quantity_unit, $footage_quantity);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => 'New item created successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
            }
        }
    }
?>