<?php 
   function fetch_mpl($conn) {
    $sql = "SELECT 
                m.id AS mpl_id,
                m.item_id,
                m.reference_numb,
                m.ship_date,
                m.trailer_name,
                i.status,
            FROM mpl m
            LEFT JOIN inventory_item_info i ON m.id = i.mpl_id
            ORDER BY m.id";
    
        $result = $conn->query($sql);
    
        if ($result->num_rows > 0) {
            $mpl_array = [];
            while ($row = $result->fetch_assoc()) {
                $mpl_id = $row['mpl_id'];
    
                // Group items under their parent MPL
                if (!isset($mpl_array[$mpl_id])) {
                    $mpl_array[$mpl_id] = [
                        'mpl_id'            => $mpl_id,
                        'reference_numb'    => $row['reference_numb'],
                        'trailer_name'      => $row['trailer_name'],
                        'ship_date'         => $row['ship_date'],
                        'status'            => $row['status'],
                        'items'             => []
                    ];
                }
    
                // Add item if it exists
                if ($row['item_id']) {
                    $mpl_array[$mpl_id]['items'][] = [
                        'item_id'       => $row['inventory_id'],
                        'sku'           => $row['sku'],
                        'unit_numb'     => $row['unit_numb'],
                        'ficha'         => $row['ficha'],
                        'description1'   => $row['description1'],
                        'description2'   => $row['description2'],
                        'quantity'      => $row['quantity'],
                        'quantity_unit' => $row['quantity_unit'],
                        'footage_quantity'  => $row['footage_quantity'],
                    ];
                }
            }
    
            return ['success' => true, 'data' => array_values($mpl_array)];
        } else {
            return ['success' => false, 'error' => 'No MPL records found'];
        }
    }


    function receive_mpl($conn, $mpl_id) {
        // 1. FETCH MPL STATUS
        $sql  = "SELECT status FROM mpl WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $mpl_id);
        $stmt->execute();
        $mpl  = $stmt->get_result()->fetch_assoc();
    
        if (!$mpl) {
            return ['success' => false, 'error' => "MPL #$mpl_id not found"];
        }
    
        if ($mpl['status'] === 'received') {
            return ['success' => false, 'error' => "MPL #$mpl_id has already been received"];
        }
    
        // 2. FETCH ALL ITEMS ON THIS MPL
        $sql  = $sql  = "SELECT ficha, quantity, description, sku, uom_primary, piece_count, length_inches, width_inches, height_inches, weight_lbs, assembly, rate FROM mpl_items WHERE mpl_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $mpl_id);
        $stmt->execute();
        $items = $stmt->get_result();
    
        if ($items->num_rows === 0) {
            return ['success' => false, 'error' => "No items found for MPL #$mpl_id"];
        }
    
        // 3. TRANSACTION: Add to inventory and mark MPL as received
        $conn->begin_transaction();
    
        try {
            while ($item = $items->fetch_assoc()) {
                $ficha       = $item['ficha'];
                $quantity    = $item['quantity'];
                $description = $item['description'] ?? 'Imported from MPL';
    
                // Check if inventory item exists for this ficha
                $sql  = "SELECT id FROM inventory WHERE ficha = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $ficha);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
    
                if ($existing) {
                    // Already exists — add to quantity
                    $sql  = "UPDATE inventory SET quant_instock = quant_instock + ? WHERE ficha = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $quantity, $ficha);
                    if (!$stmt->execute()) throw new Exception("Inventory update failed for ficha #$ficha");
                } else {
                    $sku         = $item['sku'];
                    $uom         = $item['uom_primary'];
                    $piece_count = $item['piece_count'];
                    $length      = $item['length_inches'];
                    $width       = $item['width_inches'];
                    $height      = $item['height_inches'];
                    $weight      = $item['weight_lbs'];
                    $assembly    = $item['assembly'];
                    $rate        = $item['rate'];
                
                    $sql  = "INSERT INTO inventory (ficha, quant_instock, description, sku, uom_primary, piece_count, length_inches, width_inches, height_inches, weight_lbs, assembly, rate, time_stamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iisssiddddsd", $ficha, $quantity, $description, $sku, $uom, $piece_count, $length, $width, $height, $weight, $assembly, $rate);
                    if (!$stmt->execute()) throw new Exception("Inventory insert failed for ficha #$ficha");
                }
            }
    
            // Mark MPL as received
            $sql  = "UPDATE mpl SET status = 'received' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $mpl_id);
            if (!$stmt->execute()) throw new Exception("MPL status update failed");
    
            $conn->commit();
    
            return [
                'success' => true,
                'message' => "MPL #$mpl_id received. Inventory updated."
            ];
    
        } catch (Exception $e) {
            $conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
?>