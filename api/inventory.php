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
        $id             = $data['id'];
        $ficha          = $data['ficha'];
        $sku            = $data['sku'];
        $description    = $data['description']; 
        $uom_primary    = $data['uom_primary']; 
        $piece_count    = $data['piece_count']; 
        $length_inches  = $data['length_inches']; 
        $width_inches   = $data['width_inches']; 
        $height_inches  = $data['height_inches']; 
        $weight_lbs     = $data['weight_lbs']; 
        $assembly       = $data['assembly']; 
        $rate           = $data['rate'];
            
            $sql = "INSERT INTO inventory (id, ficha, sku, description, uom_primary, piece_count, length_inches, width_inches, height_inches, weight_lbs, assembly, rate, time_stamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisssiddddsd", $id, $ficha, $sku, $description, $uom_primary, $piece_count, $length_inches, $width_inches, $height_inches, $weight_lbs, $assembly, $rate);
    
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => 'New item created successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
            }
        }
    }
?>