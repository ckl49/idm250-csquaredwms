<?php

 header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    include '../db.php';
    include '../auth.php';
    check_api_key($env);

    // turn line 6 and 7 above on and off if you want to try the API KEY

    $method = $_SERVER['REQUEST_METHOD'];


    if ($method === 'GET') {
        // echo json_encode(['success' => true, 'data' => $message]);

        $sql = "SELECT * FROM orders";
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
        // get data from other team
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id']) || !isset($data['ficha']) || !isset($data['description1']) || !isset($data['description2']) || !isset($data['quantity']) || !isset($data['quantity_unit']) || !isset($data['footage_quantity']) || !isset($data['width_inches']) || !isset($data['height_inches']) || !isset($data['weight_lbs']) || !isset($data['assembly']) || !isset($data['rate'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'details' => 'Missing required field(s)']);
            exit;

        } else {
        $ficha          = $data['ficha'];
        $description1    = $data['description1']; 
        $description2    = $data['description2']; 
        $quantity    = $data['quantity']; 
        $quantity_unit    = $data['quantity_unit']; 
        $footage_quantity  = $data['footage_quantity']; 
        
            $sql = "INSERT INTO orders (ficha, description1, description2, quantity, quantity_unit, footage_quantity, time_stamp) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issisi", $ficha, $description1, $description2, $quantity, $quantity_unit, $footage_quantity);
    
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => 'New item created successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
            }
        }
    }
?>