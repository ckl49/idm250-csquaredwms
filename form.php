<?php 
    require "db.php";

    $result = $conn -> query("SELECT * FROM inventory, user_mgmt");

    // if ($result) {
    //     print_r($result);
    //     print_r('connection sucessful');
    // }

    if ($_SERVER['REQUEST_METHOD'] = "POST") {
        if($_POST['username']);
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
    <form action="dashboard.php" method="POST">
        <label for="username">Username</label>
        <input type="text" id="username" name="username">
        <label for="password">Password</label>
        <input type="text" id="password" name="password">
        <button>Submit</button>
    </form>
</body>
</html>

