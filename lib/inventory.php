<?php 

    function fetch_inventory($conn) {
        $sql = "SELECT id AS inventory_id, ficha, sku, quant_instock, description, uom_primary, piece_count, length_inches, width_inches, height_inches, weight_lbs, assembly, rate FROM inventory";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $inventory_array = [];
            while($row = $result->fetch_assoc()) {
                $inventory_array[] = $row;
            }
            return ['success' => true, 'data' => $inventory_array];
        } else {
            return ['success' => false, 'error' => 'No inventory items found'];
        }
    }


?>