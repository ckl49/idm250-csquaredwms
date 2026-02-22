<?php 
    require_once 'db.php';

    $table = $_GET['table'] ?? null;

    $allowed_tables = ['inventory', 'orders', 'mpl'];

    if (!in_array($table, $allowed_tables)) {
        http_response_code(400);
        exit('Invalid table');
    }

    if ($table === 'inventory'): ?>            <!-- create a new item -->
        <form method="post">
            <input type="hidden" name="table" value="inventory">
            <div class="input-div">
                <label for="ficha">FICHA</label>
                <input name="ficha" type="number" value="<?= $row['ficha'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="sku">SKU</label>
                <input name="sku" type="text" value="<?= $row['sku'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="quant_instock">Quantity In Stock</label>
                <input name="quant_instock" type="number" value="<?= $row['quant_instock'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="description">Description</label>
                <input name="description" type="text" value="<?= $row['description'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="uom_primary">Unit of Measurement</label>
                <input name="uom_primary" type="text" value="<?= $row['uom_primary'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="piece_count">Piece Count</label>
                <input name="piece_count" type="number" value="<?= $row['piece_count'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="length_inches">Length</label>
                <input name="length_inches" type="number" value="<?= $row['length_inches'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="width_inches">Width</label>
                <input name="width_inches" type="number" value="<?= $row['width_inches'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="height_inches">Height</label>
                <input name="height_inches" type="number" value="<?= $row['height_inches'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="weight_lbs">Weight</label>
                <input name="weight_lbs" type="number" value="<?= $row['weight_lbs'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="assembly">Assembly</label>
                <input name="assembly" type="text" value="<?= $row['assembly'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="rate">Price Rate</label>
                <input name="rate" type="number" value="<?= $row['rate'] ?? '' ?>" required>
            </div>

            <button type="submit" >Save</button>
        </form>
    
    <?php elseif ($table === 'orders'): ?>
        <form method="post">
            <input type="hidden" name="table" value="orders">
            <div class="input-div">
                <label for="ficha">FICHA</label>
                <input name="ficha" type="number" value="<?= $row['ficha'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="description1">Description 1</label>
                <input name="description1" type="text" value="<?= $row['description1'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="description2">Description 2</label>
                <input name="description2" type="text" value="<?= $row['description2'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="quantity">Quantity</label>
                <input name="quantity" type="number" value="<?= $row['quantity'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="quantity_unit">Quantity Unit</label>
                <input name="quantity_unit" type="text" value="<?= $row['quantity_unit'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="footage_quantity">Footage Quantity</label>
                <input name="footage_quantity" type="number" value="<?= $row['footage_quantity'] ?? '' ?>" required>
            </div>
            <button type="submit">Save</button>
        </form>
    
    <?php elseif ($table === 'mpl'): ?>
        <form method="post">
            <input type="hidden" name="table" value="mpl">
            <div class="input-div">
                <label for="order_number">Order Number</label>
                <input name="order_number" type="number" value="<?= $row['order_number'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="truck_number">Truck Number</label>
                <input name="truck_number" type="number" value="<?= $row['truck_number'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="expected_delivery">Expected Delivery</label>
                <input name="expected_delivery" type="date" value="<?= $row['expected_delivery'] ?? '' ?>" required>
            </div>
            <button type="submit">Save</button>
        </form> 
    
    <?php endif;

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $table = $_POST['table'] ?? null;

        if ($table === 'inventory') {
            $ficha = $_POST['ficha'];
            $sku = $_POST['sku'];
            $quant_instock = $_POST['quant_instock'];
            $description = $_POST['description'];
            $uom_primary = $_POST['uom_primary'];
            $piece_count = $_POST['piece_count'];
            $length_inches = $_POST['length_inches'];
            $width_inches = $_POST['width_inches'];
            $height_inches = $_POST['height_inches'];
            $weight_lbs = $_POST['weight_lbs'];
            $assembly = $_POST['assembly'];
            $rate = $_POST['rate'];

            $sql = "INSERT INTO inventory (ficha, sku, quant_instock, description, uom_primary, piece_count, length_inches, width_inches, height_inches, weight_lbs, assembly, rate, time_stamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isissiddddsd", $ficha, $sku, $quant_instock, $description, $uom_primary, $piece_count, $length_inches, $width_inches, $height_inches, $weight_lbs, $assembly, $rate);
    
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => 'New item created successfully']);
                header("Location: dashboard.php");
                exit();
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
            }
        } elseif ($table === 'orders') {
            $ficha = $_POST['ficha'];
            $description1 = $_POST['description1'];
            $description2 = $_POST['description2'];        
            $quantity = $_POST['quantity'];
            $quantity_unit = $_POST['quantity_unit'];
            $footage_quantity = $_POST['footage_quantity'];

            
            $sql = "INSERT INTO orders( ficha, description1, description2, quantity, quantity_unit, footage_quantity) VALUES (?,?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issisi", $ficha, $description1, $description2, $quantity, $quantity_unit, $footage_quantity);
    
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => 'New order created successfully']);
                header("Location: dashboard.php");
                exit();
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
            }
        } elseif ($table === 'mpl') {
            $order_number = $_POST['order_number'];
            $truck_number = $_POST['truck_number'];
            $expected_delivery = $_POST['expected_delivery'];        

            $sql = "INSERT INTO mpl( order_number, truck_number, expected_delivery) VALUES (?,?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $order_number, $truck_number, $expected_delivery);
    
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => 'New mpl created successfully']);
                header("Location: dashboard.php");
                exit();
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
            }
        }
    }    
?>

