<?php 
    require_once 'db.php';

    $table = $_GET['table'] ?? null;

    $allowed_tables = ['inventory', 'orders', 'mpl'];

    if (!in_array($table, $allowed_tables)) {
        http_response_code(400);
        exit('Invalid table');
    }

    // Handle POST before any output so header() redirect works
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $table = $_POST['table'] ?? null;

        if ($table === 'inventory') {
            $ficha         = $_POST['ficha'];
            $sku           = $_POST['sku'];
            $quant_instock = $_POST['quant_instock'];
            $description   = $_POST['description'];
            $uom_primary   = $_POST['uom_primary'];
            $piece_count   = $_POST['piece_count'];
            $length_inches = $_POST['length_inches'];
            $width_inches  = $_POST['width_inches'];
            $height_inches = $_POST['height_inches'];
            $weight_lbs    = $_POST['weight_lbs'];
            $assembly      = $_POST['assembly'];
            $rate          = $_POST['rate'];

            $sql  = "INSERT INTO inventory (ficha, sku, quant_instock, description, uom_primary, piece_count, length_inches, width_inches, height_inches, weight_lbs, assembly, rate, time_stamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissiddddsdi", $ficha, $sku, $quant_instock, $description, $uom_primary, $piece_count, $length_inches, $width_inches, $height_inches, $weight_lbs, $assembly, $rate);

            if ($stmt->execute()) {
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Database error: ' . $stmt->error;
            }

        } elseif ($table === 'orders') {
            $ficha            = $_POST['ficha'];
            $description1     = $_POST['description1'];
            $description2     = $_POST['description2'];
            $quantity         = $_POST['quantity'];
            $quantity_unit    = $_POST['quantity_unit'];
            $footage_quantity = $_POST['footage_quantity'];

            $sql  = "INSERT INTO orders (ficha, description1, description2, quantity, quantity_unit, footage_quantity) VALUES (?,?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issisi", $ficha, $description1, $description2, $quantity, $quantity_unit, $footage_quantity);

            if ($stmt->execute()) {
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Database error: ' . $stmt->error;
            }

        } elseif ($table === 'mpl') {
            $order_number      = $_POST['order_number'];
            $truck_number      = $_POST['truck_number'];
            $expected_delivery = $_POST['expected_delivery'];

            $sql  = "INSERT INTO mpl (order_number, truck_number, expected_delivery) VALUES (?,?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $order_number, $truck_number, $expected_delivery);

            if ($stmt->execute()) {
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Database error: ' . $stmt->error;
            }
        }
    }

    $table_labels = ['inventory' => 'Inventory Item', 'orders' => 'Order', 'mpl' => 'MPL'];
    $label = $table_labels[$table] ?? ucfirst($table);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-Squared WMS | Add <?= htmlspecialchars($label) ?></title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="form-page">
<div class="form-page-container">
    <div class="form-card">
        <div class="form-card-header">
            <h2 class="form-card-title">Add <?= htmlspecialchars($label) ?></h2>
            <a href="dashboard.php" class="form-back-link">← Back</a>
        </div>

        <?php if (!empty($error)): ?>
            <p class="form-error-msg"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($table === 'inventory'): ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="table" value="inventory">
            <div class="input-div"><label>FICHA</label><input name="ficha" type="number" required></div>
            <div class="input-div"><label>SKU</label><input name="sku" type="text" required></div>
            <div class="input-div"><label>Quantity In Stock</label><input name="quant_instock" type="number" required></div>
            <div class="input-div"><label>Description</label><input name="description" type="text" required></div>
            <div class="input-div"><label>Unit of Measurement</label><input name="uom_primary" type="text" required></div>
            <div class="input-div"><label>Piece Count</label><input name="piece_count" type="number" required></div>
            <div class="input-div"><label>Length (in)</label><input name="length_inches" type="number" step="any" required></div>
            <div class="input-div"><label>Width (in)</label><input name="width_inches" type="number" step="any" required></div>
            <div class="input-div"><label>Height (in)</label><input name="height_inches" type="number" step="any" required></div>
            <div class="input-div"><label>Weight (lbs)</label><input name="weight_lbs" type="number" step="any" required></div>
            <div class="input-div"><label>Assembly</label><input name="assembly" type="text" required></div>
            <div class="input-div"><label>Price Rate</label><input name="rate" type="number" step="any" required></div>
            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel-btn">Cancel</a>
                <button type="submit" class="form-save-btn">Add Item</button>
            </div>
        </form>

        <?php elseif ($table === 'orders'): ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="table" value="orders">
            <div class="input-div"><label>FICHA</label><input name="ficha" type="number" required></div>
            <div class="input-div"><label>Description 1</label><input name="description1" type="text" required></div>
            <div class="input-div"><label>Description 2</label><input name="description2" type="text" required></div>
            <div class="input-div"><label>Quantity</label><input name="quantity" type="number" required></div>
            <div class="input-div"><label>Quantity Unit</label><input name="quantity_unit" type="text" required></div>
            <div class="input-div"><label>Footage Quantity</label><input name="footage_quantity" type="number" required></div>
            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel-btn">Cancel</a>
                <button type="submit" class="form-save-btn">Add Order</button>
            </div>
        </form>

        <?php elseif ($table === 'mpl'): ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="table" value="mpl">
            <div class="input-div"><label>Order Number</label><input name="order_number" type="number" required></div>
            <div class="input-div"><label>Truck Number</label><input name="truck_number" type="number" required></div>
            <div class="input-div"><label>Expected Delivery</label><input name="expected_delivery" type="date" required></div>
            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel-btn">Cancel</a>
                <button type="submit" class="form-save-btn">Add MPL</button>
            </div>
        </form>
        <?php endif; ?>

    </div>
</div>
</body>
</html>
