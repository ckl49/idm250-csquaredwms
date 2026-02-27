<?php 
<<<<<<< Updated upstream
    require 'db.php';
=======
    require_once 'db.php';

    $id    = $_GET['id'] ?? null;
    $table = $_GET['table'] ?? null;

    $allowed_tables = ['inventory', 'orders', 'mpl'];

    if (!in_array($table, $allowed_tables)) {
        http_response_code(400);
        exit('Invalid table');
    }

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

<<<<<<< Updated upstream
<<<<<<< Updated upstream
    if ($table === 'inventory'): ?>
        <form method="post" id="edit-form">
            <input type="hidden" name="table" value="inventory">
            <div class="input-div">
                <label for="id">ID</label>
                <input name="id" type="text" value="<?= $row['id'] ?? '' ?>" required>
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
            <button type="submit">Save</button>
        </form>
    
    <?php elseif ($table === 'orders'): ?>
        <form method="post">
            <input type="hidden" name="table" value="orders">

            <div class="input-div">
                <label for="ficha">FICHA</label>
                <input name="ficha" type="number" value="<?= $row['ficha'] ?? '' ?>" required>
            </div>

            <div class="input-div">
                <label for="status">Status</label>
                <select name="status">
                    <option value="pending"   <?= ($row['status'] ?? '') === 'pending'   ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= ($row['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                </select>
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

=======
    // Handle POST before any output
>>>>>>> Stashed changes
=======
    // Handle POST before any output
>>>>>>> Stashed changes
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $table = $_POST['table'] ?? null;
        if (!in_array($table, $allowed_tables)) exit('Invalid table');

        if ($table === 'inventory') {
            $id            = $_POST['id'];
            $sku           = $_POST['sku'];
            $description   = $_POST['description'];
            $uom_primary   = $_POST['uom_primary'];
            $piece_count   = $_POST['piece_count'];
            $length_inches = $_POST['length_inches'];
            $width_inches  = $_POST['width_inches'];
            $height_inches = $_POST['height_inches'];
            $weight_lbs    = $_POST['weight_lbs'];
            $assembly      = $_POST['assembly'];
            $rate          = $_POST['rate'];

            $update_stmt = $conn->prepare("UPDATE inventory SET 
                sku = ?, description = ?, uom_primary = ?, piece_count = ?,
                length_inches = ?, width_inches = ?, height_inches = ?,
                weight_lbs = ?, assembly = ?, rate = ?
                WHERE id = ?");
            $update_stmt->bind_param("sssiddddssi",
                $sku, $description, $uom_primary, $piece_count,
                $length_inches, $width_inches, $height_inches,
                $weight_lbs, $assembly, $rate, $id);

            if ($update_stmt->execute()) { header("Location: dashboard.php"); exit(); }
            else echo "Error: " . $conn->error;
<<<<<<< Updated upstream

<<<<<<< Updated upstream
            if ($update_stmt->execute()) {
                header("Location: dashboard.php");
                exit();
            } else {
                echo "Error updating record: " . $conn->error;
            }

=======
>>>>>>> Stashed changes
        } elseif ($table === 'orders') {
            $id               = $_GET['id'];
            $ficha            = $_POST['ficha'];
            $status           = $_POST['status'];
            $description1     = $_POST['description1'];
            $description2     = $_POST['description2'];
            $quantity         = (int)$_POST['quantity'];
            $quantity_unit    = $_POST['quantity_unit'];
            $footage_quantity = (int)$_POST['footage_quantity'];

<<<<<<< Updated upstream
            $id               = $_GET['id'];        // row ID from URL
            $ficha            = $_POST['ficha'];
            $status           = $_POST['status'];
            $description1     = $_POST['description1'];
            $description2     = $_POST['description2'];
            $quantity         = (int)$_POST['quantity'];
            $quantity_unit    = $_POST['quantity_unit'];
            $footage_quantity = (int)$_POST['footage_quantity'];

            $update_stmt = $conn->prepare("UPDATE orders SET 
                ficha            = ?, 
                status           = ?,
                description1     = ?, 
                description2     = ?, 
                quantity         = ?, 
                quantity_unit    = ?, 
                footage_quantity = ?
            WHERE id = ?");

            // i=ficha, s=status, s=desc1, s=desc2, i=quantity, s=unit, i=footage, i=id
            $update_stmt->bind_param(
                "isssisii",
                $ficha,
                $status,
                $description1,
                $description2,
                $quantity,
                $quantity_unit,
                $footage_quantity,
                $id               // <-- was missing entirely before
            );

            if ($update_stmt->execute()) {
                header("Location: dashboard.php");
                exit();
            } else {
                echo "Error updating record: " . $conn->error;
            }

        } elseif ($table === 'mpl') {

=======

        } elseif ($table === 'orders') {
            $id               = $_GET['id'];
            $ficha            = $_POST['ficha'];
            $status           = $_POST['status'];
            $description1     = $_POST['description1'];
            $description2     = $_POST['description2'];
            $quantity         = (int)$_POST['quantity'];
            $quantity_unit    = $_POST['quantity_unit'];
            $footage_quantity = (int)$_POST['footage_quantity'];

            $update_stmt = $conn->prepare("UPDATE orders SET 
                ficha = ?, status = ?, description1 = ?, description2 = ?,
                quantity = ?, quantity_unit = ?, footage_quantity = ?
                WHERE id = ?");
            $update_stmt->bind_param("isssisii",
                $ficha, $status, $description1, $description2,
                $quantity, $quantity_unit, $footage_quantity, $id);

            if ($update_stmt->execute()) { header("Location: dashboard.php"); exit(); }
            else echo "Error: " . $conn->error;

        } elseif ($table === 'mpl') {
>>>>>>> Stashed changes
            $id                = $_GET['id'];
            $order_number      = $_POST['order_number'];
            $truck_number      = $_POST['truck_number'];
            $expected_delivery = $_POST['expected_delivery'];

            $update_stmt = $conn->prepare("UPDATE mpl SET 
<<<<<<< Updated upstream
                                            order_number       = ?, 
                                            truck_number       = ?, 
                                            expected_delivery  = ?
                                        WHERE id = ?");
            $update_stmt->bind_param("iisi", 
                                    $order_number, 
                                    $truck_number, 
                                    $expected_delivery,
                                    $id);   // <-- was also missing
>>>>>>> Stashed changes

            if ($update_stmt->execute()) {
                header("Location: dashboard.php");
                exit();
            } else {
                echo "Error updating record: " . $conn->error;
            }
        }
    }
<<<<<<< Updated upstream
=======
            $update_stmt = $conn->prepare("UPDATE orders SET 
                ficha = ?, status = ?, description1 = ?, description2 = ?,
                quantity = ?, quantity_unit = ?, footage_quantity = ?
                WHERE id = ?");
            $update_stmt->bind_param("isssisii",
                $ficha, $status, $description1, $description2,
                $quantity, $quantity_unit, $footage_quantity, $id);

            if ($update_stmt->execute()) { header("Location: dashboard.php"); exit(); }
            else echo "Error: " . $conn->error;

        } elseif ($table === 'mpl') {
            $id                = $_GET['id'];
            $order_number      = $_POST['order_number'];
            $truck_number      = $_POST['truck_number'];
            $expected_delivery = $_POST['expected_delivery'];

            $update_stmt = $conn->prepare("UPDATE mpl SET 
                order_number = ?, truck_number = ?, expected_delivery = ?
                WHERE id = ?");
            $update_stmt->bind_param("iisi",
                $order_number, $truck_number, $expected_delivery, $id);

            if ($update_stmt->execute()) { header("Location: dashboard.php"); exit(); }
            else echo "Error: " . $conn->error;
        }
    }
=======
                order_number = ?, truck_number = ?, expected_delivery = ?
                WHERE id = ?");
            $update_stmt->bind_param("iisi",
                $order_number, $truck_number, $expected_delivery, $id);

            if ($update_stmt->execute()) { header("Location: dashboard.php"); exit(); }
            else echo "Error: " . $conn->error;
        }
    }
>>>>>>> Stashed changes

    // Table label for the page title
    $table_labels = ['inventory' => 'Inventory', 'orders' => 'Order', 'mpl' => 'MPL'];
    $label = $table_labels[$table] ?? ucfirst($table);
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-Squared WMS | Edit <?= htmlspecialchars($label) ?></title>
    <link rel="stylesheet" href="/styles.css">
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

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<!-- edit -->
<form method="post" id="edit-form">
    <div class="input-div">
        <label for="id">ID</label>
        <input name="id" type="text" value="<?= $row['id'] ?? '' ?>" required>
    </div>

    <div class="input-div">
        <label for="sku">SKU</label>
        <input name="sku" type="text" value="<?= $row['sku'] ?? '' ?>" required>
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

    <button type="submit">Edit</button>
    
</form>
=======
        <?php if ($table === 'inventory'): ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="table" value="inventory">
            <div class="input-div"><label>ID</label><input name="id" type="text" value="<?= $row['id'] ?? '' ?>" required></div>
            <div class="input-div"><label>SKU</label><input name="sku" type="text" value="<?= $row['sku'] ?? '' ?>" required></div>
            <div class="input-div"><label>Quantity In Stock</label><input name="quant_instock" type="number" value="<?= $row['quant_instock'] ?? '' ?>" required></div>
            <div class="input-div"><label>Description</label><input name="description" type="text" value="<?= $row['description'] ?? '' ?>" required></div>
            <div class="input-div"><label>Unit of Measurement</label><input name="uom_primary" type="text" value="<?= $row['uom_primary'] ?? '' ?>" required></div>
            <div class="input-div"><label>Piece Count</label><input name="piece_count" type="number" value="<?= $row['piece_count'] ?? '' ?>" required></div>
            <div class="input-div"><label>Length (in)</label><input name="length_inches" type="number" step="any" value="<?= $row['length_inches'] ?? '' ?>" required></div>
            <div class="input-div"><label>Width (in)</label><input name="width_inches" type="number" step="any" value="<?= $row['width_inches'] ?? '' ?>" required></div>
            <div class="input-div"><label>Height (in)</label><input name="height_inches" type="number" step="any" value="<?= $row['height_inches'] ?? '' ?>" required></div>
            <div class="input-div"><label>Weight (lbs)</label><input name="weight_lbs" type="number" step="any" value="<?= $row['weight_lbs'] ?? '' ?>" required></div>
            <div class="input-div"><label>Assembly</label><input name="assembly" type="text" value="<?= $row['assembly'] ?? '' ?>" required></div>
            <div class="input-div"><label>Price Rate</label><input name="rate" type="number" step="any" value="<?= $row['rate'] ?? '' ?>" required></div>
            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel-btn">Cancel</a>
                <button type="submit" class="form-save-btn">Save Changes</button>
            </div>
        </form>
>>>>>>> Stashed changes

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
            <div class="input-div"><label>Description 2</label><input name="description2" type="text" value="<?= $row['description2'] ?? '' ?>" required></div>
            <div class="input-div"><label>Quantity</label><input name="quantity" type="number" value="<?= $row['quantity'] ?? '' ?>" required></div>
            <div class="input-div"><label>Quantity Unit</label><input name="quantity_unit" type="text" value="<?= $row['quantity_unit'] ?? '' ?>" required></div>
            <div class="input-div"><label>Footage Quantity</label><input name="footage_quantity" type="number" value="<?= $row['footage_quantity'] ?? '' ?>" required></div>
            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel-btn">Cancel</a>
                <button type="submit" class="form-save-btn">Save Changes</button>
            </div>
        </form>

<<<<<<< Updated upstream

=======
?>
>>>>>>> Stashed changes
=======
=======
        <?php if ($table === 'inventory'): ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="table" value="inventory">
            <div class="input-div"><label>ID</label><input name="id" type="text" value="<?= $row['id'] ?? '' ?>" required></div>
            <div class="input-div"><label>SKU</label><input name="sku" type="text" value="<?= $row['sku'] ?? '' ?>" required></div>
            <div class="input-div"><label>Quantity In Stock</label><input name="quant_instock" type="number" value="<?= $row['quant_instock'] ?? '' ?>" required></div>
            <div class="input-div"><label>Description</label><input name="description" type="text" value="<?= $row['description'] ?? '' ?>" required></div>
            <div class="input-div"><label>Unit of Measurement</label><input name="uom_primary" type="text" value="<?= $row['uom_primary'] ?? '' ?>" required></div>
            <div class="input-div"><label>Piece Count</label><input name="piece_count" type="number" value="<?= $row['piece_count'] ?? '' ?>" required></div>
            <div class="input-div"><label>Length (in)</label><input name="length_inches" type="number" step="any" value="<?= $row['length_inches'] ?? '' ?>" required></div>
            <div class="input-div"><label>Width (in)</label><input name="width_inches" type="number" step="any" value="<?= $row['width_inches'] ?? '' ?>" required></div>
            <div class="input-div"><label>Height (in)</label><input name="height_inches" type="number" step="any" value="<?= $row['height_inches'] ?? '' ?>" required></div>
            <div class="input-div"><label>Weight (lbs)</label><input name="weight_lbs" type="number" step="any" value="<?= $row['weight_lbs'] ?? '' ?>" required></div>
            <div class="input-div"><label>Assembly</label><input name="assembly" type="text" value="<?= $row['assembly'] ?? '' ?>" required></div>
            <div class="input-div"><label>Price Rate</label><input name="rate" type="number" step="any" value="<?= $row['rate'] ?? '' ?>" required></div>
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
            <div class="input-div"><label>Description 2</label><input name="description2" type="text" value="<?= $row['description2'] ?? '' ?>" required></div>
            <div class="input-div"><label>Quantity</label><input name="quantity" type="number" value="<?= $row['quantity'] ?? '' ?>" required></div>
            <div class="input-div"><label>Quantity Unit</label><input name="quantity_unit" type="text" value="<?= $row['quantity_unit'] ?? '' ?>" required></div>
            <div class="input-div"><label>Footage Quantity</label><input name="footage_quantity" type="number" value="<?= $row['footage_quantity'] ?? '' ?>" required></div>
            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel-btn">Cancel</a>
                <button type="submit" class="form-save-btn">Save Changes</button>
            </div>
        </form>

>>>>>>> Stashed changes
        <?php elseif ($table === 'mpl'): ?>
        <form method="post" class="edit-form">
            <input type="hidden" name="table" value="mpl">
            <div class="input-div"><label>Order Number</label><input name="order_number" type="number" value="<?= $row['order_number'] ?? '' ?>" required></div>
            <div class="input-div"><label>Truck Number</label><input name="truck_number" type="number" value="<?= $row['truck_number'] ?? '' ?>" required></div>
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
<<<<<<< Updated upstream
</html>
>>>>>>> Stashed changes
=======
</html>
>>>>>>> Stashed changes
