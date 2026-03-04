<?php
ini_set('display_errors', 1); error_reporting(E_ALL);

require 'db.php';

$id    = $_GET['id'] ?? null;
$table = $_GET['table'] ?? null;

$allowed_tables = ['inventory', 'orders', 'mpl'];

if (!in_array($table, $allowed_tables)) {
    http_response_code(400);
    exit('Invalid table');
}

// Handle POST before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table = $_POST['table'] ?? null;
    if (!in_array($table, $allowed_tables)) exit('Invalid table');

    if ($table === 'inventory') {
        $id               = (int)$_POST['id'];
        $ficha            = $_POST['ficha'];
        $sku              = $_POST['sku'];
        $quantity         = (int)$_POST['quantity'];
        $description1     = $_POST['description1'];
        $description2     = $_POST['description2'];
        $quantity_unit    = $_POST['quantity_unit'];
        $footage_quantity = (float)$_POST['footage_quantity'];

        $update_stmt = $conn->prepare("UPDATE inventory SET
            sku = ?, ficha = ?, description1 = ?, description2 = ?,
            quantity = ?, quantity_unit = ?, footage_quantity = ?
            WHERE id = ?");
        $update_stmt->bind_param("ssssisdi",
            $sku, $ficha, $description1, $description2,
            $quantity, $quantity_unit, $footage_quantity, $id);

        if ($update_stmt->execute()) { header("Location: dashboard.php"); exit(); }
        else echo "Error: " . $conn->error;

    } elseif ($table === 'orders') {
        $id               = (int)$_GET['id'];
        $ficha            = $_POST['ficha'];
        $status           = $_POST['status'];
        $description1     = $_POST['description1'];
        $description2     = $_POST['description2'];
        $quantity         = (int)$_POST['quantity'];
        $quantity_unit    = $_POST['quantity_unit'];
        $footage_quantity = (float)$_POST['footage_quantity'];

        $update_stmt = $conn->prepare("UPDATE orders SET
            ficha = ?, status = ?, description1 = ?, description2 = ?,
            quantity = ?, quantity_unit = ?, footage_quantity = ?
            WHERE id = ?");
        $update_stmt->bind_param("ssssisdi",
            $ficha, $status, $description1, $description2,
            $quantity, $quantity_unit, $footage_quantity, $id);

        if ($update_stmt->execute()) { header("Location: dashboard.php"); exit(); }
        else echo "Error: " . $conn->error;

    } elseif ($table === 'mpl') {
        $id                = (int)$_GET['id'];
        $order_number      = $_POST['order_number'];
        $truck_number      = $_POST['truck_number'];
        $expected_delivery = $_POST['expected_delivery'];

        $update_stmt = $conn->prepare("UPDATE mpl SET
            order_number = ?, truck_number = ?, expected_delivery = ?
            WHERE id = ?");
        $update_stmt->bind_param("sssi",
            $order_number, $truck_number, $expected_delivery, $id);

        if ($update_stmt->execute()) { header("Location: dashboard.php"); exit(); }
        else echo "Error: " . $conn->error;
    }
}

// Fetch current row to pre-fill the form
if ($table === 'inventory') {
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
} elseif ($table === 'orders') {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
} elseif ($table === 'mpl') {
    $stmt = $conn->prepare("SELECT * FROM mpl WHERE id = ?");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) exit("Record not found.");

$table_labels = ['inventory' => 'Inventory', 'orders' => 'Order', 'mpl' => 'MPL'];
$label = $table_labels[$table] ?? ucfirst($table);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-Squared WMS | Edit <?= htmlspecialchars($label) ?></title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="form-page">
<div class="form-page-container">
    <div class="form-card">
        <div class="form-card-header">
            <h2 class="form-card-title">Edit <?= htmlspecialchars($label) ?></h2>
            <a href="dashboard.php" class="form-back-link">← Back</a>
        </div>

        <?php if ($table === 'inventory'): ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="table" value="inventory">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <div class="input-div"><label>FICHA</label><input name="ficha" type="text" placeholder="452" value="<?= $row['ficha'] ?? '' ?>" required></div>
            <div class="input-div"><label>SKU</label><input name="sku" type="text" placeholder="1720823-0567" value="<?= $row['sku'] ?? '' ?>" required></div>
            <div class="input-div"><label>Quantity</label><input name="quantity" type="number" placeholder="100" value="<?= $row['quantity'] ?? '' ?>" required></div>
            <div class="input-div"><label>Description 1</label><input name="description1" placeholder="BIRCH YEL FAS 6/4 RGH KD 10FT" type="text" value="<?= $row['description1'] ?? '' ?>" required></div>
            <div class="input-div"><label>Description 2</label><input name="description2" placeholder="Medex FSCMC 120" type="text" value="<?= $row['description2'] ?? '' ?>"></div>
            <div class="input-div"><label>Quantity Unit</label><input name="quantity_unit" type="text" placeholder="PC" value="<?= $row['quantity_unit'] ?? '' ?>"></div>
            <div class="input-div"><label>Footage Quantity</label><input name="footage_quantity" type="number" step="any" placeholder="1320.28" value="<?= $row['footage_quantity'] ?? '' ?>"></div>
            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel-btn">Cancel</a>
                <button type="submit" class="form-save-btn">Save Changes</button>
            </div>
        </form>

        <?php elseif ($table === 'orders'): ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="table" value="orders">
            <div class="input-div"><label>FICHA</label><input name="ficha" type="number" value="<?= $row['ficha'] ?? '' ?>" required></div>
            <div class="input-div">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="pending"   <?= ($row['status'] ?? '') === 'pending'   ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= ($row['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                </select>
            </div>
            <div class="input-div"><label>Description 1</label><input name="description1" type="text" value="<?= $row['description1'] ?? '' ?>" required></div>
            <div class="input-div"><label>Description 2</label><input name="description2" type="text" value="<?= $row['description2'] ?? '' ?>"></div>
            <div class="input-div"><label>Quantity</label><input name="quantity" type="number" value="<?= $row['quantity'] ?? '' ?>" required></div>
            <div class="input-div"><label>Quantity Unit</label><input name="quantity_unit" type="text" value="<?= $row['quantity_unit'] ?? '' ?>"></div>
            <div class="input-div"><label>Footage Quantity</label><input name="footage_quantity" type="number" value="<?= $row['footage_quantity'] ?? '' ?>"></div>
            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel-btn">Cancel</a>
                <button type="submit" class="form-save-btn">Save Changes</button>
            </div>
        </form>

        <?php elseif ($table === 'mpl'): ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="table" value="mpl">
            <div class="input-div"><label>Order Number</label><input name="order_number" type="text" value="<?= $row['order_number'] ?? '' ?>" required></div>
            <div class="input-div"><label>Truck Number</label><input name="truck_number" type="text" value="<?= $row['truck_number'] ?? '' ?>" required></div>
            <div class="input-div"><label>Expected Delivery</label><input name="expected_delivery" type="date" value="<?= $row['expected_delivery'] ?? '' ?>" required></div>
            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel-btn">Cancel</a>
                <button type="submit" class="form-save-btn">Save Changes</button>
            </div>
        </form>
        <?php endif; ?>

    </div>
</div>
</body>
</html>