<?php 
    header('Content-Type: application/json');
    header('access-Control-Allow-Origin: *');

    // require_once '../db.php';
    // require_once '../auth.php';

    $message = "Practice API is working!";

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(['success' => true, 'data' => $message]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    }
?>