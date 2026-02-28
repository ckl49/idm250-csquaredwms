<?php 
    require_once 'db.php';

    $id    = $_GET['id']    ?? null;
    $table = $_GET['table'] ?? null;

    $allowed_tables = ['inventory', 'orders', 'mpl'];

    if (!in_array($table, $allowed_tables)) {
        http_response_code(400);
        exit('Invalid table');
    }

    if (!$id) {
        http_response_code(400);
        exit('Invalid id');
    }

    $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    }

    $error = $conn->error;

    $table_labels = ['inventory' => 'Inventory', 'orders' => 'Order', 'mpl' => 'MPL'];
    $label = $table_labels[$table] ?? ucfirst($table);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-Squared WMS | Delete Error</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="form-page-container">
    <div class="form-card form-card--narrow">
        <div class="form-card-header">
            <h2 class="form-card-title">Delete Failed</h2>
        </div>
        <p class="form-error-msg">Something went wrong while deleting the <?= htmlspecialchars($label) ?> record.</p>
        <?php if (!empty($error)): ?>
            <p class="form-error-detail"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <div class="form-actions">
            <a href="dashboard.php" class="form-save-btn">Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>