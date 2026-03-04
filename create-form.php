<?php 
ini_set('display_errors', 1); error_reporting(E_ALL);

    require_once 'db.php';

    $table = $_GET['table'] ?? null;

    $allowed_tables = ['inventory', 'orders', 'mpl'];

    if (!in_array($table, $allowed_tables)) {
        http_response_code(400);
        exit('Invalid table');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $table = $_POST['table'] ?? null;

        if ($table === 'inventory') {
            $ficha            = $_POST['ficha'];
            $sku              = $_POST['sku'];
            $quantity         = (int)$_POST['quantity'];
            $description1     = $_POST['description1'];
            $description2     = $_POST['description2'] ?? '';
            $quantity_unit    = $_POST['quantity_unit'];
            $footage_quantity = (float)$_POST['footage_quantity'];

            $stmt = $conn->prepare(
                "INSERT INTO inventory (ficha, sku, quantity, description1, description2, quantity_unit, footage_quantity)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssissd",
                $ficha, $sku, $quantity, $description1, $description2, $quantity_unit, $footage_quantity);

            if ($stmt->execute()) { header("Location: dashboard.php"); exit(); }
            else $error = 'Database error: ' . $stmt->error;

        } elseif ($table === 'orders') {
            $ficha            = $_POST['ficha'];
            $description1     = $_POST['description1'];
            $description2     = $_POST['description2'] ?? '';
            $quantity         = (int)$_POST['quantity'];
            $quantity_unit    = $_POST['quantity_unit'];
            $footage_quantity = (float)$_POST['footage_quantity'];

            $stmt = $conn->prepare(
                "INSERT INTO orders (ficha, description1, description2, quantity, quantity_unit, footage_quantity)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssiss",
                $ficha, $description1, $description2, $quantity, $quantity_unit, $footage_quantity);

            if ($stmt->execute()) { header("Location: dashboard.php"); exit(); }
            else $error = 'Database error: ' . $stmt->error;

        } elseif ($table === 'mpl') {
            $order_number      = $_POST['order_number'];
            $truck_number      = $_POST['truck_number'];
            $expected_delivery = $_POST['expected_delivery'];

            $stmt = $conn->prepare(
                "INSERT INTO mpl (order_number, truck_number, expected_delivery) VALUES (?, ?, ?)"
            );
            $stmt->bind_param("sss", $order_number, $truck_number, $expected_delivery);

            if ($stmt->execute()) { header("Location: dashboard.php"); exit(); }
            else $error = 'Database error: ' . $stmt->error;
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
    <link rel="stylesheet" href="styles.css">
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
            <div class="input-div"><label>FICHA</label><input name="ficha" placeholder="ex: 452" type="text" required></div>
            <div class="input-div"><label>SKU</label><input name="sku" placeholder="ex: 1720823-0567" type="text" required></div>
            <div class="input-div"><label>Quantity</label><input name="quantity" placeholder="ex: 100" type="number" required></div>
            <div class="input-div"><label>Description 1</label><input name="description1" placeholder="ex: BIRCH YEL FAS 6/4 RGH KD 10FT" type="text" required></div>
            <div class="input-div"><label>Description 2</label><input name="description2" placeholder="ex: Medex FSCMC 120" type="text"></div>
            <div class="input-div"><label>Quantity Unit</label><input name="quantity_unit" placeholder="ex: PC" type="text"></div>
            <div class="input-div"><label>Footage Quantity</label><input name="ex: footage_quantity" type="number" placeholder="ex: 1320.28" step="any"></div>
            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel-btn">Cancel</a>
                <button type="submit" class="form-save-btn">Add Item</button>
            </div>
        </form>

        <?php elseif ($table === 'orders'): ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="table" value="orders">
            <div class="input-div"><label>FICHA</label><input name="ficha" placeholder="ex: 452"  type="number" required></div>
            <div class="input-div"><label>Description 1</label><input name="description1" placeholder="ex: BIRCH YEL FAS 6/4 RGH KD 10FT" type="text" required></div>
            <div class="input-div"><label>Description 2</label><input name="description2" placeholder="ex: Medex FSCMC 120" type="text"></div>
            <div class="input-div"><label>Quantity</label><input name="quantity" placeholder="100" type="number" required></div>
            <div class="input-div"><label>Quantity Unit</label><input name="quantity_unit" placeholder="ex: PC" type="text"></div>
            <div class="input-div"><label>Footage Quantity</label><input name="footage_quantity" type="number" placeholder="ex: 1320.28" step="any"></div>
            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel-btn">Cancel</a>
                <button type="submit" class="form-save-btn">Add Order</button>
            </div>
        </form>

        <?php elseif ($table === 'mpl'): ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="table" value="mpl">
            <div class="input-div"><label>Order Number</label><input name="order_number" placeholder="ex: 12345" type="text" required></div>
            <div class="input-div"><label>Truck Number</label><input name="truck_number" placeholder="ex: Truck123" type="text" required></div>
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