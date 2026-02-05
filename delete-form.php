<?php 
    require 'db.php';

    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("index.php");
            http_response_code(302);
            json_encode(['success' => true, 'data' => 'Record deleted successfully']);
            exit();
        } else {
            http_response_code(400);
            json_encode(['success' => false, 'error' => 'Error deleting record']);
            echo "Error deleting record: " . $conn->error;
        }
    } else {
        echo "Invalid ID.";
    }

?>