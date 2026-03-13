<?php 
   function ship_order($conn, $order_id, $reference) {

    $url    = "https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api_orders.php";
    $opts   = ['http' => ['method' => 'GET', 'header' => "x-api-key: test\r\n", 'ignore_errors' => true]];
    $result = json_decode(file_get_contents($url, false, stream_context_create($opts)), true);

    if (empty($result) || !is_array($result)) {
        return ['success' => false, 'error' => "Could not fetch orders from external API"];
    }

    $rows = isset($result['data']) && is_array($result['data'])
              ? $result['data']
              : $result;

    $ref    = null;
    $status = null;
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        if ((string)($row['reference_numb'] ?? '') === (string)$reference) {
            $ref    = $row['reference_numb'];
            $status = $row['status'];
            break;
        }
    }

    if (!$ref) {
        return ['success' => false, 'error' => "Reference #$reference not found in external API"];
    }

    if ($status === 'shipped') {
        return ['success' => false, 'error' => "Reference #$reference is already shipped"];
    }

    // Collect all items for this reference
    $items = [];
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        if ((string)($row['reference_numb'] ?? '') === (string)$reference) {
            $items[] = ['sku' => trim((string)($row['sku'] ?? ''))];
        }
    }

    if (empty($items)) {
        return ['success' => false, 'error' => "No items found for reference #$reference"];
    }

    // Check all SKUs exist in inventory before touching anything
    foreach ($items as $item) {
        $stmt = $conn->prepare("SELECT id FROM inventory WHERE sku = ?");
        $stmt->bind_param("s", $item['sku']);
        $stmt->execute();
        $found = $stmt->get_result()->fetch_assoc();

        if (!$found) {
            return ['success' => false, 'error' => "SKU {$item['sku']} not found in inventory"];
        }
    }

    // Transaction: remove SKUs from inventory
    $conn->begin_transaction();
    try {
        foreach ($items as $item) {
            $stmt = $conn->prepare("DELETE FROM inventory WHERE sku = ? LIMIT 1");
            $stmt->bind_param("s", $item['sku']);
            if (!$stmt->execute()) {
                throw new Exception("Failed to remove SKU {$item['sku']} from inventory");
            }
        }
        $conn->commit();
        return [
            'success' => true,
            'message' => "Reference #$reference shipped. " . count($items) . " unit(s) removed from inventory."
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>