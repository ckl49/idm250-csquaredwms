<?php
function fetch_inventory($conn) {
    $sql = "SELECT id AS inventory_id, ficha, sku, quantity, description1, description2, quantity_unit, footage_quantity FROM inventory";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return ['success' => true, 'data' => $rows];
    }

    return ['success' => false, 'error' => 'No inventory records found'];
}
