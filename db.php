<?php 
//    $env_vars = [
//     'DB_HOST' => $DB_SERVER,
//     'DB_NAME' => $DB_NAME,
//     'DB_USER' => $DB_USERNAME,
//     'DB_PASSWORD' => $DB_PASSWORD
//    ];

//    if (in_array(null, $env_vars, true))
//     die('Missing required environment variables');

    // define('DB_SERVER', $env_vars['DB_SERVER']);
    // define('DB_USERNAME',   $env_vars['DB_USERNAME']);
    // define('DB_PASSWORD',   $env_vars['DB_PASSWORD']);
    // define('DB_NAME',   $env_vars['DB_NAME']);

    // $DB_SERVER = 'localhost';
    // $DB_USERNAME = 'root';
    // $DB_PASSWORD = 'root';
    // $DB_NAME = 'idm216';

    $env_file = __DIR__ . '/.env.php';
    $env = file_exists($env_file) ? require $env_file : [];

    define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
    define('DB_USER', $env['DB_USER'] ?? 'root');
    define('DB_PASSWORD', $env['DB_PASSWORD'] ?? 'root');
    define('DB_NAME', $env['DB_NAME'] ?? 'idm216');


    // Create database connection 
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);

    if ($conn->connect_error)
        die("Connection failed: " . $conn->connect_error);
?>