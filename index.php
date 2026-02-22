<?php 
    session_start();

    require_once "db.php";
    require_once "lib/auth-user.php";

    // if (!$result) {
    //     die("Query failed: " . $conn->error);
    // }

    if(isset($_POST["login"])) {
        if(!empty($_POST["username"]) && !empty($_POST["password"])) {
            $_SESSION["username"] = $_POST["username"];
            $_SESSION["password"] = $_POST["password"];

            $sql = "SELECT * FROM user_mgmt WHERE name = '$username' AND password = '$password'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {

                // session_start();
                // $_SESSION['logged_in'] = true;
                header('Location: dashboard.php');
                exit;
                
            } else {
                echo "Invalid username or password.";
            }
        } else {
            echo "Please enter both username and password.";
        }
    }


    $conn -> close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In to WMS</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <main id="loginPage" class="login-container">
        <div>
            <div class="login-box">
                <div class="login-header">
                    <p class="login-title">Log In</p>
                    <p class="login-subtitle">Welcome to C Squared WMS!</p>
                </div>

                <form action="index.php" method="post">
                
                    <label class="form-label" for="username">Username</label>
                    <div class="input-wrapper">
                        <input class="form-input" type="text" id="username" name="username">
                    </div>
                
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <input class="form-input" type="password" id="password" name="password">
                    </div>
                
                    <button class="login-button" type="submit" value="submit">Log In</button>
                </form>
            </div>
        </div>
    </main>    
</body>
</html>

