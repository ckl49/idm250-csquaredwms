<?php 
    require 'db.php';

    $id = $_GET['id'] ?? null;
    $table = $_GET['table'] ?? null;

    $allowed_tables = ['inventory', 'orders', 'mpl'];

    // if (!in_array($table, $allowed_tables)) {
    //     http_response_code(400);
    //     exit('Invalid table');
    // }

    if ($table === 'inventory') {
        $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            http_response_code(302);
            json_encode(['success' => true, 'data' => 'Record deleted successfully']);
            exit();
        } else {
            http_response_code(400);
            json_encode(['success' => false, 'error' => 'Error deleting record']);
            echo "Error deleting record: " . $conn->error;
        }

    } elseif ($table === 'orders') {
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            http_response_code(302);
            json_encode(['success' => true, 'data' => 'Record deleted successfully']);
            exit();
        } else {
            http_response_code(400);
            json_encode(['success' => false, 'error' => 'Error deleting record']);
            echo "Error deleting record: " . $conn->error;
        }
    } elseif ($table === 'mpl') {
        $stmt = $conn->prepare("DELETE FROM mpl WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            http_response_code(302);
            json_encode(['success' => true, 'data' => 'Record deleted successfully']);
            exit();
        } else {
            http_response_code(400);
            json_encode(['success' => false, 'error' => 'Error deleting record']);
            echo "Error deleting record: " . $conn->error;
    } } else {
         http_response_code(400);
         exit('Invalid id');
    }

?>