<?php 
    require "db.php";

    $result = $conn -> query("SELECT * FROM inventory");

      if (!$result) {
          die("Query failed: " . $conn->error);
    }

    $conn -> close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php 
    while ($row = $result->fetch_assoc()) {
        foreach ($row as $column => $value) {
            echo htmlspecialchars($column) . ": " . htmlspecialchars($value) . "<br>";
        }
        // echo "Item: " . $row['id'] . " - Quantity: " . $row['piece_count'] . "<br>";
        echo "
            <a href='edit-form.php?id=" . ($row['id']) . "'>Edit</a>
            <a href='delete-form.php?id=" . ($row['id']) . "'>Delete</a>";
        echo "<br><br>";

    }
    ?>
</body>
</html>