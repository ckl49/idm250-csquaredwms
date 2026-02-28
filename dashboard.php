<?php
  session_start();
  require_once "db.php";
  require_once "lib/inventory.php";
  require_once "lib/orders.php";
  require_once "lib/mpl.php";
  require_once "lib/logout.php";

  if (isset($_GET['logout'])) {
    logout();
  }

  $inventory_array = fetch_inventory($conn);
  $orders_array = fetch_orders($conn);
  $mpl_array = fetch_mpl($conn);

  if (isset($_POST['logout'])) {
    logout();
  }

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
    <!-- ORDERS PAGE -->
    <div id="ordersPage" class="page">
      <div class="dashboard-container">
        <!-- SIDE-NAV -->
        <div class="sidebar">
          <div class="sidebar-top">
            <h1 class="sidebar-title">C-Squared WMS</h1>
            <div class="nav-buttons">
              <button class="nav-button active" onclick="navigate('inventory')">Inventory</button>
              <button class="nav-button" onclick="navigate('orders')">Orders</button>
              <button class="nav-button" onclick="navigate('mpl')">MPL</button>
            </div>
          </div>
          <form action="dashboard.php" method="post">
            <button class="nav-button" name="logout" value="logout">Log out</button>
          </form>
        </div>

        <div class="main-content">
          <div class="content-wrapper">
            <h2 class="content-title" id="contentTitle">Inventory</h2>

            <section id="inventorySection">
              <a id="addButton" href="create-form.php?table=inventory" class="nav-button">Add Product</a>
              <div id="inventoryTable" class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>SKU</th>
                      <th>Quantity In Stock</th>
                      <th>Description</th>
                      <th>UOM</th>
                      <th>Piece Count</th>
                      <th>Length</th>
                      <th>Width</th>
                      <th>Height</th>
                      <th>Weight</th>
                      <th>Assembly</th>
                      <th>Price Rate</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      if ($inventory_array['success']) {
                        foreach ($inventory_array['data'] as $row) {
                          echo "<tr>";
                          echo "<td>" . htmlspecialchars($row['inventory_id']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['sku']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['quant_instock']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['uom_primary']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['piece_count']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['length_inches']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['width_inches']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['height_inches']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['weight_lbs']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['assembly']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['rate']) . "</td>";
                          echo "<td>";
                    ?>
                    <div class="actions-div">
                      <?php
                        echo "<a href='edit-form.php?table=inventory&id=" . htmlspecialchars($row['inventory_id']) . "'>Edit</a>";
                        echo "<a href='delete-form.php?table=inventory&id=" . htmlspecialchars($row['inventory_id']) . "' onclick=\"return confirm('Are you sure you want to delete this record?')\">Delete</a>";
                      ?>
                    </div>
                    <?php
                          echo "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='12'>No records found.</td></tr>";
                      }
                    ?>
                  </tbody>
                </table>
              </div>
            </section>

            <section id="ordersSection">
              <a id="addButton" href="create-form.php?table=orders" class="nav-button">Add Order</a>
              <div id="ordersTable" class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>FICHA</th>
                      <th>Status</th>
                      <th>Description 1</th>
                      <th>Description 2</th>
                      <th>Quantity</th>
                      <th>Quantity Unit</th>
                      <th>Footage</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      if ($orders_array['success']) {
                        foreach ($orders_array['data'] as $row) {
                          $order_id  = htmlspecialchars($row['orders_id']);
                          $status    = $row['status'];
                          $confirmed = $status === 'confirmed';

                          echo "<tr id='order-row-{$order_id}'>";
                          echo "<td>" . htmlspecialchars($row['ficha']) . "</td>";
                          echo "<td class='status-cell-{$order_id}'>" . htmlspecialchars(ucfirst($status)) . "</td>";
                          echo "<td>" . htmlspecialchars($row['description1']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['description2']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['quantity_unit']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['footage_quantity']) . "</td>";
                          echo "<td>";
                    ?>
                    <div class="actions-div">
                      <?php
                        echo "<a href='edit-form.php?table=orders&id={$order_id}'>Edit</a>";
                        echo "<a href='delete-form.php?table=orders&id={$order_id}' onclick=\"return confirm('Are you sure you want to delete this record?')\">Delete</a>";

                        // Confirm button 
                        $disabled = $confirmed ? 'disabled' : '';
                        $label    = $confirmed ? 'Confirmed' : 'Confirm';
                        echo "<button class='btn-confirm' data-order-id='{$order_id}' onclick='confirmOrder(this)' {$disabled}>{$label}</button>";
                      ?>
                    </div>
                    <?php
                          echo "</td>";
                          echo "</tr>";
                        }
                      } else {
                        echo "<tr><td colspan='8'>No records found.</td></tr>";
                      }
                    ?>
                  </tbody>
                </table>
              </div>
            </section>

<section id="mplSection">
  <a id="addButton" href="create-form.php?table=mpl" class="nav-button">Add MPL</a>
  <div id="mplTable" class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Order Number</th>
          <th>Truck Number</th>
          <th>Expected Delivery</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
          if ($mpl_array['success']) {
            foreach ($mpl_array['data'] as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['mpl_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['order_number']) . "</td>";
                echo "<td>" . htmlspecialchars($row['truck_number']) . "</td>";
                echo "<td>" . htmlspecialchars($row['expected_delivery']) . "</td>";
                echo "<td>"; ?>
                <div class="actions-div">
                  <?php
                    echo "<a href='edit-form.php?table=mpl&id=" . htmlspecialchars($row['mpl_id']) . "'>Edit</a>";
  
                    echo "<a href='delete-form.php?table=mpl&id=" . htmlspecialchars($row['mpl_id']) . "' onclick=\"return confirm('Are you sure you want to delete this record?')\">Delete</a>";
                  ?>
                </div>
                <?php
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='12'>No records found.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</section>

          <div id="placeholderContent" class="placeholder-content" style="display: none;">
            <p class="placeholder-text">Content will be displayed here</p>
          </div>
        </div>
      </div>
    </div>
  </div>


<div id="toast"></div>

  <script src="script.js"></script>
  <script>
    async function confirmOrder(btn) {
        const orderId = btn.dataset.orderId;

        if (!confirm(`Confirm order #${orderId}? This will deduct from inventory.`)) return;

        btn.disabled    = true;
        btn.textContent = 'Processing...';

        try {
          const res = await fetch('api/orders.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ action: 'confirm', order_id: parseInt(orderId) })
          });

            const data = await res.json();

            if (data.success) {
                // Update status cell in place — no page reload needed
                const statusCell = document.querySelector(`.status-cell-${orderId}`);
                if (statusCell) statusCell.textContent = 'Confirmed';
                btn.textContent = 'Confirmed';
                showToast(data.message, 'success');
            } else {
                btn.disabled    = false;
                btn.textContent = 'Confirm';
                showToast(data.error, 'error');
            }

        } catch (err) {
            btn.disabled    = false;
            btn.textContent = 'Confirm';
            showToast('Network error — please try again.', 'error');
        }
    }

    function showToast(message, type) {
        const toast     = document.getElementById('toast');
        toast.textContent = message;
        toast.className   = type;
        toast.style.display = 'block';
        setTimeout(() => toast.style.display = 'none', 4000);
    }
  </script>

 

  <script src="script.js"></script>
</body>
</html>