

<?php 
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    include '../db.php';
    include '../auth.php';
    check_api_key($env);

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // get data from other team
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id']) || !isset($data['order_number']) || !isset($data['truck_number']) || !isset($data['expected_delivery'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'details' => 'Missing required field(s)']);
            exit;

        } else {
            $id             = $data['id'];
            $order_number    = $data['order_number'];
            $truck_number    = $data['truck_number']; 
            $expected_delivery  = $data['expected_delivery']; 
        
            
            $sql = "INSERT INTO mpl (id, order_number, truck_number, expected_delivery) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiis", $id, $order_number, $truck_number, $expected_delivery);
    
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => 'New item created successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
            }
        }
    }

?>