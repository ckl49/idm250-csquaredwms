<?php 
    session_start();

    require_once "db.php";

    //  if (!$result) {
    //      die("Query failed: " . $conn->error);
    //  }

    if (isset($_POST["login"])) {
        if (!empty($_POST["username"]) && !empty($_POST["password"])) {
    
            $username = $_POST["username"];
            $password = $_POST["password"];
    
            $stmt = $conn->prepare("SELECT password FROM user_mgmt WHERE name = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result) {
                $user = $result->fetch_assoc();
    
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION["username"] = $username;
                    header('Location: dashboard.php');
                    exit;
                }
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
                
                    <div class="login-button-div"><button class="login-button" type="submit" name="login" value="submit">Log In</button></div>
                </form>
            </div>
        </div>
    </main>    
</body>
</html>

