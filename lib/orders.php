<?php 
    function fetch_orders($conn) {
        $sql = "SELECT id AS orders_id, status, ficha, description1, description2, quantity, quantity_unit, footage_quantity FROM orders";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $orders_array = [];
            while($row = $result->fetch_assoc()) {
                $orders_array[] = $row;
            }
            return ['success' => true, 'data' => $orders_array];
        } else {
            return ['success' => false, 'error' => 'No orders found'];
        }
    }

    function confirm_order($conn, $order_id) {

  // 1. FETCH ORDER DETAILS
        $sql = "SELECT ficha, quantity, status FROM orders WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if (!$order) {
            return ['success' => false, 'error' => "Order #$order_id not found"];
        }

        if ($order['status'] === 'confirmed') {
            return ['success' => false, 'error' => "Order #$order_id is already confirmed"];
        }

        $ficha    = $order['ficha'];
        $quantity = $order['quantity'];

        // 2. CHECK INVENTORY
        $sql = "SELECT id, quant_instock FROM inventory WHERE ficha = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ficha);
        $stmt->execute();
        $result = $stmt->get_result();
        $inventory = $result->fetch_assoc();

        if (!$inventory) {
            return ['success' => false, 'error' => "No inventory item found for ficha #$ficha"];
        }

        if ($inventory['quant_instock'] < $quantity) {
            return [
                'success' => false,
                'error'   => "Insufficient stock. Available: {$inventory['quant_instock']}, Requested: $quantity"
            ];
        }

        // 3. TRANSACTION: Deduct from inventory and confirm order
        $conn->begin_transaction();

        try {
            // Deduct from inventory
            $sql = "UPDATE inventory SET quant_instock = quant_instock - ? WHERE ficha = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $quantity, $ficha);
            if (!$stmt->execute()) throw new Exception("Inventory update failed: " . $stmt->error);

            // Mark order as confirmed
            $sql = "UPDATE orders SET status = 'confirmed' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            if (!$stmt->execute()) throw new Exception("Order status update failed: " . $stmt->error);

            $conn->commit();

            return [
                'success' => true,
                'message' => "Order #$order_id confirmed. Inventory for ficha #$ficha reduced by $quantity."
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