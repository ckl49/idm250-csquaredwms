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


    function edit_orders() {

    }

?>