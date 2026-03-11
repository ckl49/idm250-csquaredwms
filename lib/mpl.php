<?php
function fetch_mpl($conn) {
    $url    = "https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api-mpl.php";
    $opts   = ['http' => ['method' => 'GET', 'header' => "x-api-key: test\r\n", 'ignore_errors' => true]];
    $result = json_decode(file_get_contents($url, false, stream_context_create($opts)), true);

    if (empty($result)) {
        return ['success' => false, 'error' => 'No MPL records found'];
    }

    // Group flat rows by reference_numb
    $grouped = [];
    foreach ($result as $row) {
        $ref = $row['reference_numb'];

        if (!isset($grouped[$ref])) {
            $grouped[$ref] = [
                'mpl_id'         => $row['id'],
                'reference_numb' => $row['reference_numb'],
                'ship_date'      => $row['ship_date'],
                'trailer_name'   => $row['trailer_name'],
                'status'         => $row['status'],
                'items'          => []
            ];
        }

        $grouped[$ref]['items'][] = [
            'item_id'          => $row['item_id']          ?? '',
            'ficha'            => $row['ficha']            ?? '',
            'unit_numb'        => $row['unit_numb']        ?? '',
            'description1'     => $row['description1']    ?? '',
            'description2'     => $row['description2']    ?? '',
            'quantity'         => $row['quantity']         ?? '',
            'quantity_unit'    => $row['quantity_unit']    ?? '',
            'footage_quantity' => $row['footage_quantity'] ?? ''
        ];
    }

    return ['success' => true, 'data' => array_values($grouped)];
}


function receive_mpl($conn, $mpl_id) {
    // 1. FETCH THE SPECIFIC MPL ROW FROM EXTERNAL API
    $url    = "https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api-mpl.php";
    $opts   = ['http' => ['method' => 'GET', 'header' => "x-api-key: test\r\n", 'ignore_errors' => true]];
    $result = json_decode(file_get_contents($url, false, stream_context_create($opts)), true);

    if (empty($result) || !is_array($result)) {
        return ['success' => false, 'error' => "Could not fetch MPL data"];
    }

    // Find the reference_numb for this mpl_id and check status
    $ref    = null;
    $status = null;
    foreach ($result as $row) {
        if ((string)$row['id'] === (string)$mpl_id) {
            $ref    = $row['reference_numb'];
            $status = $row['status'];
            break;
        }
    }

    if (!$ref) {
        return ['success' => false, 'error' => "MPL #$mpl_id not found"];
    }

    if ($status === 'received') {
        return ['success' => false, 'error' => "MPL #$mpl_id has already been received"];
    }

    // 2. COLLECT ALL ITEMS FOR THIS reference_numb
    $items = [];
    foreach ($result as $row) {
        if ($row['reference_numb'] === $ref) {
            $items[] = $row;
        }
    }

    if (empty($items)) {
        return ['success' => false, 'error' => "No items found for MPL #$mpl_id"];
    }

    // 3. TRANSACTION: add to inventory
    $conn->begin_transaction();
    $new_skus = [];  // track auto-created SKUs

    try {
        foreach ($items as $item) {
            $order_id         = $item['inventory_id']       ?? '';
            $ficha            = $item['ficha']            ?? '';
            $sku              = $item['unit_numb']        ?? '';
            $quantity         = (int)($item['quantity']   ?? 0);
            $description1     = $item['description1']     ?? 'Imported from MPL';
            $description2     = $item['description2']     ?? '';
            $quantity_unit    = $item['quantity_unit']    ?? '';
            $footage_quantity = (float)($item['footage_quantity'] ?? 0);

            // Auto-insert into products if ficha doesn't exist
            $check = $conn->prepare("SELECT id FROM products WHERE order_id = ?");
            $check->bind_param("i", $order_id);
            $check->execute();
            $existing_product = $check->get_result()->fetch_assoc();

            if (!$existing_product) {
                $stmt = $conn->prepare("INSERT INTO products (order_id, ficha, sku, description, rate) VALUES (?, ?, ?, ?, 0)");
                $stmt->bind_param("iss", $ficha, $sku, $description1);
                if (!$stmt->execute()) throw new Exception("Failed to auto-insert product for order ID #$order_id");

                $new_product_id = $conn->insert_id;

                $stmt = $conn->prepare("INSERT INTO products_dimensions (length_inches, width_inches, height_inches, weight_lbs) VALUES (?, 0, 0, 0, 0)");
                $stmt->bind_param("i", $new_product_id);
                if (!$stmt->execute()) throw new Exception("Failed to auto-insert dimensions for order ID #$order_id");

                $stmt = $conn->prepare("INSERT INTO products_types (ficha, uom_primary, piece_count, assembly) VALUES (?, '', 0, 'FALSE')");
                $stmt->bind_param("s", $ficha);
                if (!$stmt->execute()) throw new Exception("Failed to auto-insert product type for ficha #$order_id");

                // Track that this SKU was auto-created
                $new_skus[] = "Ficha #$ficha ($sku)";
            }

            // Check if ficha already exists in inventory
            $check = $conn->prepare("SELECT id FROM inventory WHERE ficha = ?");
            $check->bind_param("s", $ficha);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();

            if ($existing) {
                $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity + ? WHERE ficha = ?");
                $stmt->bind_param("is", $quantity, $ficha);
                if (!$stmt->execute()) throw new Exception("Inventory update failed for ficha #$ficha");
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO inventory (ficha, sku, quantity, description1, description2, quantity_unit, footage_quantity)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("ssisssd",
                    $ficha, $sku, $quantity, $description1,
                    $description2, $quantity_unit, $footage_quantity);
                if (!$stmt->execute()) throw new Exception("Inventory insert failed for ficha #$ficha");
            }
        }

        $conn->commit();

        // Build response message
        $message = "MPL #$mpl_id received. Inventory updated.";
        $warnings = null;

        if (!empty($new_skus)) {
            $warnings = "New SKUs auto-created (please complete their details): " . implode(', ', $new_skus);
        }

        return [
            'success'  => true,
            'message'  => $message,
            'warnings' => $warnings
        ];

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}