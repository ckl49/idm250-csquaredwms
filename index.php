<?php 
    require "db.php";

    $result = $conn -> query("SELECT * FROM inventory, user_mgmt");

      if (!$result) {
          die("Query failed: " . $conn->error);
    }

    // if ($result) {
    //      print_r($result);
    //     print_r('connection sucessful');
    // }

    $conn -> close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-Squared WMS | Log-in</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- LOGiN PAGE-->
  <div id="loginPage" class="page active">
    <div class="login-container">
      <div class="login-box">
        <div class="login-header">
          <p class="login-title">Log In</p>
          <p class="login-subtitle">Welcome to C Squared WMS!</p>
        </div>
        
        <div class="form-group">
          <label class="form-label">Username</label>
          <div class="input-wrapper">
            <input type="text" id="username" class="form-input" placeholder="Username">
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-wrapper">
            <input type="password" id="password" class="form-input" placeholder="Password">
          </div>
        </div>
        
        <button class="login-button" onclick="login()">Log In</button>
      </div>
    </div>
  </div>

  <!-- ORDERS PAGE -->
  <div id="ordersPage" class="page">
    <div class="dashboard-container">
      <!-- SIDE-NAV -->
      <div class="sidebar">
        <div class="sidebar-top">
          <h1 class="sidebar-title">C-Squared WMS</h1>
          <div class="nav-buttons">
            <button class="nav-button active" onclick="navigate('orders')">Orders</button>
            <button class="nav-button" onclick="navigate('inventory')">Inventory</button>
            <button class="nav-button" onclick="navigate('mpl')">MPL</button>
          </div>
        </div>
        <button class="logout-button" onclick="logout()">Log out</button>
      </div>

      <div class="main-content">
        <div class="content-wrapper">
          <h2 class="content-title" id="contentTitle">Orders</h2>

          <div id="ordersTable" class="table-container">
            <div class="table-wrapper">

              <div class="table-row">
                <div class="table-cell header-cell">SKU</div>
                <div class="table-cell header-cell">Type</div>
                <div class="table-cell header-cell">Category</div>
                <div class="table-cell header-cell">Category</div>
                <div class="table-cell header-cell">Category</div>
                <div class="table-cell header-cell">Category</div>
              </div>

              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
              <div class="table-row">
                <div class="table-cell">111111111111</div>
                <div class="table-cell">Wood</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
                <div class="table-cell">Chart</div>
              </div>
            </div>
          </div>

          <div id="placeholderContent" class="placeholder-content" style="display: none;">
            <p class="placeholder-text">Content will be displayed here</p>
          </div>
        </div>
      </div>
    </