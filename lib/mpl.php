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
            'description1'     => $row['description1']     ?? '',
            'description2'     => $row['description2']     ?? '',
            'quantity'         => $row['quantity']         ?? '',
            'quantity_unit'    => $row['quantity_unit']    ?? '',
            'footage_quantity' => $row['footage_quantity'] ?? ''
        ];
    }

    return ['success' => true, 'data' => array_values($grouped)];
}


function receive_mpl($conn, $mpl_id) {

    // 1. FETCH ALL MPL DATA FROM EXTERNAL API
    $url    = "https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api-mpl.php";
    $opts   = ['http' => ['method' => 'GET', 'header' => "x-api-key: test\r\n", 'ignore_errors' => true]];
    $result = json_decode(file_get_contents($url, false, stream_context_create($opts)), true);

    if (empty($result) || !is_array($result)) {
        return ['success' => false, 'error' => "Could not fetch MPL data"];
    }

    // 2. FIND reference_numb AND STATUS FOR THIS mpl_id
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

    if ($status === 'received' || $status === 'accepted') {
        return ['success' => false, 'error' => "MPL #$mpl_id has already been received"];
    }

    // 3. COLLECT ALL ITEMS FOR THIS reference_numb
    $items = [];
    foreach ($result as $row) {
        if ($row['reference_numb'] === $ref) {
            $items[] = $row;
        }
    }

    if (empty($items)) {
        return ['success' => false, 'error' => "No items found for MPL #$mpl_id"];
    }

    // 4. TRANSACTION: insert one inventory row per item
    $conn->begin_transaction();

    try {
        foreach ($items as $item) {
            $item_id          = $item['item_id']          ?? null;
            $unit_numb        = $item['unit_numb']        ?? '';
            $ficha            = $item['ficha']            ?? '';
            $quantity         = (int)($item['quantity']   ?? 1);
            $description1     = $item['description1']     ?? '';
            $description2     = $item['description2']     ?? '';
            $quantity_unit    = $item['quantity_unit']    ?? '';
            $footage_quantity = (float)($item['footage_quantity'] ?? 0);

            // Look up SKU from ashley.products using ficha
            $sku_stmt = $conn->prepare("SELECT sku FROM ashley.products WHERE ficha = ? LIMIT 1");
            $sku_stmt->bind_param("s", $ficha);
            $sku_stmt->execute();
            $sku_row = $sku_stmt->get_result()->fetch_assoc();
            $sku     = $sku_row['sku'] ?? '';  // empty string if ficha not found in products table

            // Insert one row per individual unit — no grouping, no quantity merging
            $stmt = $conn->prepare(
                "INSERT INTO inventory
                    (order_id, unit_numb, ficha, sku, quantity, description1, description2, quantity_unit, footage_quantity)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "isssisssd",
                $item_id,
                $unit_numb,
                $ficha,
                $sku,
                $quantity,
                $description1,
                $description2,
                $quantity_unit,
                $footage_quantity
            );

            if (!$stmt->execute()) {
                throw new Exception("Inventory insert failed for unit #$unit_numb (ficha $ficha): " . $stmt->error);
            }
        }

        $conn->commit();

        return [
            'success' => true,
            'message' => "MPL #$mpl_id received. " . count($items) . " unit(s) added to inventory."
        ];

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}