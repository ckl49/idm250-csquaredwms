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

        // 1. FETCH ORDER STATUS
        $sql  = "SELECT status FROM orders WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
    
        if (!$order) {
            return ['success' => false, 'error' => "Order #$order_id not found"];
        }
    
        if ($order['status'] === 'shipped') {
            return ['success' => false, 'error' => "Order #$order_id is already shipped"];
        }
    
        // 2. FETCH ALL ITEMS ON THIS ORDER
        $sql  = "SELECT ficha, quantity FROM orders_items WHERE order_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items = $stmt->get_result();
    
        if ($items->num_rows === 0) {
            return ['success' => false, 'error' => "No items found for order #$order_id"];
        }
    
        // 3. CHECK ALL INVENTORY BEFORE TOUCHING ANYTHING
        $items_array = [];
        while ($item = $items->fetch_assoc()) {
            $sql  = "SELECT quant_instock FROM inventory WHERE ficha = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $item['ficha']);
            $stmt->execute();
            $inventory = $stmt->get_result()->fetch_assoc();
    
            if (!$inventory) {
                return ['success' => false, 'error' => "No inventory found for ficha #{$item['ficha']}"];
            }
    
            if ($inventory['quant_instock'] < $item['quantity']) {
                return [
                    'success' => false,
                    'error'   => "Insufficient stock for ficha #{$item['ficha']}. Available: {$inventory['quant_instock']}, Requested: {$item['quantity']}"
                ];
            }
    
            $items_array[] = $item;
        }
    
        // 4. TRANSACTION: Deduct from inventory and ship order
        $conn->begin_transaction();
    
        try {
            foreach ($items_array as $item) {
                $sql  = "UPDATE inventory SET quant_instock = quant_instock - ? WHERE ficha = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $item['quantity'], $item['ficha']);
                if (!$stmt->execute()) throw new Exception("Inventory update failed for ficha #{$item['ficha']}");
            }
    
            // Mark order as shipped
            $sql  = "UPDATE orders SET status = 'shipped' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            if (!$stmt->execute()) throw new Exception("Order status update failed");
    
            $conn->commit();
    
            return [
                'success' => true,
                'message' => "Order #$order_id shipped. Inventory updated."
            ];
    
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