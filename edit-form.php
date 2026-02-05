<?php 
    require 'db.php';

    $id = $_GET['id'] ?? null;

    $stmt = $conn->prepare("SELECT id, sku, description, uom_primary, piece_count, length_inches, width_inches, height_inches, weight_lbs, assembly, rate
                        FROM inventory
                        WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        http_response_code(404);
        exit('Record not found');
    }

    
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'];
        $sku = $_POST['sku'];
        $description = $_POST['description'];
        $uom_primary = $_POST['uom_primary'];
        $piece_count = $_POST['piece_count'];
        $length_inches = $_POST['length_inches'];
        $width_inches = $_POST['width_inches'];
        $height_inches = $_POST['height_inches'];
        $weight_lbs = $_POST['weight_lbs'];
        $assembly = $_POST['assembly'];
        $rate = $_POST['rate'];

        $update_stmt = $conn->prepare("UPDATE inventory SET 
                                        sku = ?, 
                                        description = ?, 
                                        uom_primary = ?, 
                                        piece_count = ?, 
                                        length_inches = ?, 
                                        width_inches = ?, 
                                        height_inches = ?, 
                                        weight_lbs = ?, 
                                        assembly = ?, 
                                        rate = ? 
                                      WHERE id = ?");
        $update_stmt->bind_param("sssiddddssi", 
                                  $sku, 
                                  $description, 
                                  $uom_primary, 
                                  $piece_count, 
                                  $length_inches, 
                                  $width_inches, 
                                  $height_inches, 
                                  $weight_lbs, 
                                  $assembly, 
                                  $rate, 
                                  $id);

        if ($update_stmt->execute()) {
            header("Location: inventory.php");
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }
?>

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



