<?php
  session_start();
  require_once "db.php";
  require_once "lib/inventory.php";
  require_once "auth.php";
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
                      <th>FICHA</th>
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
                          echo "<td>" . htmlspecialchars($row['ficha']) . "</td>";
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
                      <th>ID</th>
                      <th>Reference Number</th>
                      <th>Status</th>
                      <th>Ship Date</th>
                      <th>Trailer Name</th>
                      <th>Number of Items</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      if ($orders_array['success']) {
                        foreach ($orders_array['data'] as $row) {
                          $order_id  = $row['orders_id'];
                          $status    = $row['status'];
                          $confirmed = $status === 'shipped';

                          echo "<tr class='order-header-row' onclick='toggleOrderItems({$order_id})'>";
                          echo "<td>" . htmlspecialchars($order_id) . "</td>";
                          echo "<td>" . htmlspecialchars($row['reference_numb']) . "</td>";
                          echo "<td class='status-cell-{$order_id}'>" . htmlspecialchars(ucfirst($status)) . "</td>";
                          echo "<td>" . htmlspecialchars($row['ship_date']) . "</td>";
                          echo "<td>" . htmlspecialchars($row['trailer_name']) . "</td>";
                          echo "<td>" . count($row['items']) . " item(s)</td>";
                          echo "<td>";

                          $disabled = $confirmed ? 'disabled' : '';
                          $label    = $confirmed ? 'Shipped' : 'ship';
                          echo "<button class='btn-confirm' data-order-id='{$order_id}' onclick='event.stopPropagation(); shipOrder(this)' {$disabled}>{$label}</button>";
                          echo "</td>";
                          echo "</tr>";

                          // Hidden items row
                          echo "<tr id='order-items-{$order_id}' style='display:none;'>";
                          echo "<td colspan='7'>";
                          echo "<table class='data-table'>";
                          echo "<thead><tr><th>Ficha</th><th>SKU</th><th>Description</th><th>Quantity</th><th>Unit</th><th>Rate</th></tr></thead>";
                          echo "<tbody>";
                          foreach ($row['items'] as $item) {
                              echo "<tr>";
                              echo "<td>" . htmlspecialchars($item['ficha']) . "</td>";
                              echo "<td>" . htmlspecialchars($item['sku']) . "</td>";
                              echo "<td>" . htmlspecialchars($item['description']) . "</td>";
                              echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                              echo "<td>" . htmlspecialchars($item['quantity_unit']) . "</td>";
                              echo "<td>" . htmlspecialchars($item['rate']) . "</td>";
                              echo "</tr>";
                          }
                          echo "</tbody></table>";
                          echo "</td></tr>";
                        }
                      } else {
                          echo "<tr><td colspan='7'>No records found.</td></tr>";
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
          <th>Status</th>
          <th>Truck Number</th>
          <th>Expected Delivery</th>
          <th>Number of Items</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
          if ($mpl_array['success']) {
            foreach ($mpl_array['data'] as $row) {
              $mpl_id   = $row['mpl_id'];
              $status   = $row['status'];
              $received = $status === 'received';
          
              echo "<tr class='mpl-header-row' onclick='toggleMplItems({$mpl_id})'>";
              echo "<td>" . htmlspecialchars($mpl_id) . "</td>";
              echo "<td>" . htmlspecialchars($row['order_number']) . "</td>";
              echo "<td class='mpl-status-cell-{$mpl_id}'>" . htmlspecialchars(ucfirst($status)) . "</td>";
              echo "<td>" . htmlspecialchars($row['truck_number']) . "</td>";
              echo "<td>" . htmlspecialchars($row['expected_delivery']) . "</td>";
              echo "<td>";
              echo count($row['items']) . " item(s)";
              echo "</td>";
              echo "<td>";
          
              $disabled = $received ? 'disabled' : '';
              $label    = $received ? 'Received' : 'Receive';
              echo "<button class='btn-confirm' data-mpl-id='{$mpl_id}' onclick='event.stopPropagation(); receiveMpl(this)' {$disabled}>{$label}</button>";
              echo "</td>";
              echo "</tr>";
          
              // Hidden items row
              echo "<tr id='mpl-items-{$mpl_id}' style='display:none;'>";
              echo "<td colspan='7'>";
              echo "<table class='data-table'>";
              echo "<thead><tr><th>Ficha</th><th>Quantity</th><th>Description</th></tr></thead>";
              echo "<tbody>";
              foreach ($row['items'] as $item) {
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($item['ficha']) . "</td>";
                  echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                  echo "<td>" . htmlspecialchars($item['description']) . "</td>";
                  echo "</tr>";
              }
              echo "</tbody></table>";
              echo "</td></tr>";
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
    async function shipOrder(btn) {
      const orderId = btn.dataset.orderId;

      if (!confirm(`Ship order #${orderId}? This will deduct from inventory.`)) return;

      btn.disabled    = true;
      btn.textContent = 'Processing...';

      try {
        const res = await fetch('api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'ship', order_id: parseInt(orderId) })
        });

          const data = await res.json();

          if (data.success) {
              // Update status cell in place — no page reload needed
              const statusCell = document.querySelector(`.status-cell-${orderId}`);
              if (statusCell) statusCell.textContent = 'Shipped';
              btn.textContent = 'Shipped';
              showToast(data.message, 'success');
          } else {
              btn.disabled    = false;
              btn.textContent = 'Ship';
              showToast(data.error, 'error');
          }

      } catch (err) {
          btn.disabled    = false;
          btn.textContent = 'Ship';
          showToast('Network error — please try again.', 'error');
          }
    }

    async function receiveMpl(btn) {
      const mplId = btn.dataset.mplId;

      if (!confirm(`Receive MPL #${mplId}? This will add to inventory.`)) return;

      btn.disabled    = true;
      btn.textContent = 'Processing...';

      try {
          const res = await fetch('api/mpl.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ action: 'receive', mpl_id: parseInt(mplId) })
          });

          const data = await res.json();

          if (data.success) {
              const statusCell = document.querySelector(`.mpl-status-cell-${mplId}`);
              if (statusCell) statusCell.textContent = 'Received';
              btn.textContent = 'Received';
              showToast(data.message, 'success');
          } else {
              btn.disabled    = false;
              btn.textContent = 'Receive';
              showToast(data.error, 'error');
          }

      } catch (err) {
          btn.disabled    = false;
          btn.textContent = 'Receive';
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
    
    function toggleMplItems(mplId) {
    const row = document.getElementById(`mpl-items-${mplId}`);
      if (row) {
          row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
      }
    }

    function toggleOrderItems(orderId) {
    const row = document.getElementById(`order-items-${orderId}`);
      if (row) {
          row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
      }
    }
  </script>

    

  <script src="script.js"></script>
</body>
</html>