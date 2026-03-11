<?php 
    function fetch_orders($conn) {
        $sql = "SELECT 
                    o.id AS orders_id,
                    o.reference_numb,
                    o.status,
                    o.ship_date,
                    o.trailer_name,
                    i.id AS item_id,
                    i.ficha,
                    i.sku,
                    i.description,
                    i.quantity,
                    i.quantity_unit,
                    i.footage_quantity,
                    i.uom_primary,
                    i.piece_count,
                    i.length_inches,
                    i.width_inches,
                    i.height_inches,
                    i.weight_lbs,
                    i.assembly,
                    i.rate
                FROM orders o
                LEFT JOIN orders_items i ON o.id = i.order_id
                ORDER BY o.id";
    
        $result = $conn->query($sql);
    
        if ($result->num_rows > 0) {
            $orders_array = [];
            while ($row = $result->fetch_assoc()) {
                $order_id = $row['orders_id'];
    
                // Group items under their parent order
                if (!isset($orders_array[$order_id])) {
                    $orders_array[$order_id] = [
                        'orders_id'     => $order_id,
                        'reference_numb'=> $row['reference_numb'],
                        'status'        => $row['status'],
                        'ship_date'     => $row['ship_date'],
                        'trailer_name'  => $row['trailer_name'],
                        'items'         => []
                    ];
                }
    
                // Add item if it exists
                if ($row['item_id']) {
                    $orders_array[$order_id]['items'][] = [
                        'item_id'        => $row['item_id'],
                        'ficha'          => $row['ficha'],
                        'sku'            => $row['sku'],
                        'description'    => $row['description'],
                        'quantity'       => $row['quantity'],
                        'quantity_unit'  => $row['quantity_unit'],
                        'footage_quantity'=> $row['footage_quantity'],
                        'uom_primary'    => $row['uom_primary'],
                        'piece_count'    => $row['piece_count'],
                        'length_inches'  => $row['length_inches'],
                        'width_inches'   => $row['width_inches'],
                        'height_inches'  => $row['height_inches'],
                        'weight_lbs'     => $row['weight_lbs'],
                        'assembly'       => $row['assembly'],
                        'rate'           => $row['rate']
                    ];
                }
            }
    
            return ['success' => true, 'data' => array_values($orders_array)];
        } else {
            return ['success' => false, 'error' => 'No orders found'];
        }
    }

    function ship_order($conn, $order_id) {

        $url    = "https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api_orders.php";
        $opts   = ['http' => ['method' => 'GET', 'header' => "x-api-key: test\r\n", 'ignore_errors' => true]];
        $result = json_decode(file_get_contents($url, false, stream_context_create($opts)), true);
    
        if (empty($result) || !is_array($result)) {
            return ['success' => false, 'error' => "Could not fetch orders from external API"];
        }
    
        // Find the reference_numb for this order_id
        $ref    = null;
        $status = null;
        foreach ($result as $row) {
            if (!is_array($row)) continue;
            if ((string)($row['id'] ?? '') === (string)$order_id) {
                $ref    = $row['reference_numb'];
                $status = $row['status'];
                break;
            }
        }
    
        if (!$ref) {
            return ['success' => false, 'error' => "Order #$order_id not found in external API"];
        }
    
        if ($status === 'shipped') {
            return ['success' => false, 'error' => "Order #$order_id is already shipped"];
        }
    
        // Collect ALL items for this reference_numb
        $items = [];
        foreach ($result as $row) {
            if (!is_array($row)) continue;
            if (($row['reference_numb'] ?? '') === $ref) {
                $inventory_id    = $row['inventory_id']    ?? null;
                $quantity = (int)($row['quantity'] ?? 0);
                if ($order_id) {
                    $items[] = ['order_id' => $order_id, 'quantity' => $quantity];
                }
            }
        }
    
        if (empty($items)) {
            return ['success' => false, 'error' => "No items found for order #$order_id"];
        }
    
        // Check inventory before touching anything
        foreach ($items as $item) {
            $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE order_id = ?");
            $stmt->bind_param("s", $item['order_id']);
            $stmt->execute();
            $inventory = $stmt->get_result()->fetch_assoc();
    
            if (!$inventory) {
                return ['success' => false, 'error' => "No inventory found for order ID #{$item['order_id']}"];
            }
    
            if ($inventory['quantity'] < $item['quantity']) {
                return [
                    'success' => false,
                    'error'   => "Insufficient stock for order ID #{$item['order_id']}. Available: {$inventory['quantity']}, Requested: {$item['quantity']}"
                ];
            }
        }
    
        // Transaction: deduct inventory
        $conn->begin_transaction();
    
        try {
            foreach ($items as $item) {
                $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE order_id = ?");
                $stmt->bind_param("is", $item['quantity'], $item['order_id']);
                if (!$stmt->execute()) throw new Exception("Inventory update failed for order ID #{$item['order_id']}");
            }
    
            $conn->commit();
            return ['success' => true, 'message' => "Order #$order_id shipped. Inventory updated."];
    
        } catch (Exception $e) {
            $conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    function edit_orders() {
        // Placeholder 
        return ['success' => false, 'error' => 'Edit orders functionality not implemented yet'];
    }

?>