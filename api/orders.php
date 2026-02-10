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

        if (!isset($data['ficha']) || !isset($data['description1']) || !isset($data['description2']) || !isset($data['quantity']) || !isset($data['quantity_unit']) || !isset($data['footage_quantity'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Bad Request', 'details' => 'Missing required field(s)']);
            exit;

        } else {
        $id             = $data['id'];
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
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'error' => 'ID is required for update']);
            exit;
        }

      
        $sql = "UPDATE orders SET description1 = ?, description2 = ?, quantity = ?, quantity_unit = ?, footage_quantity = ? WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        
    
        $stmt->bind_param("ssisii", $data['description1'], $data['description2'], $data['quantity'],  $data['quantity_unit'],  $data['footage_quantity'],  $data['id']
    );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }

   } elseif ($method === 'DELETE') {
       
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = $data['id'] ?? null;

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