<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
  session_start();
  require_once "db.php";
  require_once "lib/inventory.php";
  require_once "auth.php";
  // require_once "lib/orders.php"; removed — using external API for orders now
  require_once "lib/mpl.php";
  require_once "lib/logout.php";

  if (isset($_GET['logout']))  logout();
  if (isset($_POST['logout'])) logout();

  $inventory_array = fetch_inventory($conn);
  $mpl_array       = fetch_mpl($conn);

  function api_request($url, $method, $data = null): mixed {
    $api_key = "test";

    $options = [
      'http' => [
        'method'        => $method,
        'header'        => "Content-Type: application/json\r\n" .
                           "x-api-key: " . $api_key . "\r\n",
        'ignore_errors' => true
      ]
    ];

    if ($data !== null) {
      $options['http']['content'] = json_encode($data);
    }

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    $result   = json_decode($response, true);

    return $result;
  }

  function fetch_orders_from_api($conn) {
    $url    = "https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api_orders.php";
    $result = api_request($url, 'GET');

    if (!is_array($result) || isset($result['error'])) {
      return ['success' => false, 'data' => []];
    }

    $rows = isset($result['data']) && is_array($result['data'])
              ? $result['data']
              : $result;

    if (empty($rows) || !isset($rows[0])) {
      return ['success' => false, 'data' => []];
    }

    // Check local DB for shipped reference numbers so status persists on reload
    $shipped = [];
    $shipped_result = $conn->query("SELECT reference_numb FROM orders WHERE status = 'shipped'");
    if ($shipped_result) {
      while ($r = $shipped_result->fetch_assoc()) {
        $shipped[$r['reference_numb']] = true;
      }
    }

    $grouped = [];

    foreach ($rows as $row) {
      if (!is_array($row)) continue;

      $ref = $row['reference_numb'] ?? null;
      if ($ref === null) continue;

      if (!isset($grouped[$ref])) {
        // Use local shipped status if we have it, otherwise use external API status
        $status = isset($shipped[$ref]) ? 'shipped' : ($row['status'] ?? 'draft');

        $grouped[$ref] = [
          'orders_id'      => $row['id']             ?? '',
          'reference_numb' => $row['reference_numb'] ?? '',
          'status'         => $status,
          'ship_date'      => $row['ship_date']      ?? '',
          'trailer_name'   => $row['trailer_name']   ?? '',
          'item_ids'       => [],
          'items'          => []
        ];
      }

      $grouped[$ref]['item_ids'][] = $row['item_id'] ?? '';

      $grouped[$ref]['items'][] = [
        'item_id'          => $row['item_id']          ?? '',
        'inventory_id'     => $row['inventory_id']     ?? '',
        'ficha'            => $row['ficha']            ?? '',
        'sku'              => $row['sku']              ?? '',
        'unit_numb'        => $row['unit_numb']        ?? '',
        'description1'     => $row['description1']    ?? '',
        'description2'     => $row['description2']    ?? '',
        'quantity'         => $row['quantity']         ?? '',
        'quantity_unit'    => $row['quantity_unit']    ?? '',
        'footage_quantity' => $row['footage_quantity'] ?? '',
        'location'         => $row['location']         ?? '',
        'uom_primary'      => $row['uom_primary']      ?? '',
      ];
    }

    if (empty($grouped)) {
      return ['success' => false, 'data' => []];
    }

    return ['success' => true, 'data' => array_values($grouped)];
  }

  // -------------------------------------------------------
  // Update an existing order on the external API (PUT)
  // -------------------------------------------------------
  // Required fields: id, reference_numb, ship_date, trailer_name

  function update_order_on_api($data) {
    $url = "https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api_orders.php";

    if (!isset($data['id'], $data['reference_numb'], $data['ship_date'], $data['trailer_name'])) {
      return ['success' => false, 'error' => 'Missing required fields: id, reference_numb, ship_date, trailer_name'];
    }

    $payload = [
      'id'             => intval($data['id']),
      'reference_numb' => $data['reference_numb'],
      'ship_date'      => $data['ship_date'],
      'trailer_name'   => $data['trailer_name']
    ];

    $result = api_request($url, 'PUT', $payload);

    return is_array($result) ? $result : ['success' => false, 'error' => 'Invalid response from API'];
  }

  $orders_array = fetch_orders_from_api($conn);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-Squared WMS | Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700&display=swap" rel="stylesheet">
  </head>
  <body>
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

            <!-- ===================== INVENTORY ===================== -->
            <section id="inventorySection">
              <a id="addButton" href="create-form.php?table=inventory" class="nav-button">Add Product</a>
              <div id="inventoryTable" class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>SKU</th>
                      <th>FICHA</th>
                      <th>Quantity</th>
                      <th>Quantity Unit</th>
                      <th>Description1</th>
                      <th>Description2</th>
                      <!-- <th>UOM</th> -->
                      <!-- <th>Piece Count</th> -->
                      <!-- <th>Length</th>
                      <th>Width</th>
                      <th>Height</th>
                      <th>Weight</th> -->
                      <th>Footage Quantity</th>
                      <!-- <th>Assembly</th> -->
                      <!-- <th>Price Rate</th> -->
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($inventory_array['success']): ?>
                      <?php foreach ($inventory_array['data'] as $row): ?>
                        <tr>
                          <td><?= htmlspecialchars($row['inventory_id'])  ?></td>
                          <td><?= htmlspecialchars($row['sku'])           ?></td>
                          <td><?= htmlspecialchars($row['ficha'])         ?></td>
                          <td><?= htmlspecialchars($row['quantity']) ?></td>
                          <td><?= htmlspecialchars($row['quantity_unit'])   ?></td>
                          <td><?= htmlspecialchars($row['description1'])   ?></td>
                          <td><?= htmlspecialchars($row['description2'])   ?></td>
                          <td><?= htmlspecialchars($row['footage_quantity']) ?></td>
                          
                          <td>
                            <div class="actions-div">
                              <a href="edit-form.php?table=inventory&id=<?= htmlspecialchars($row['inventory_id']) ?>">Edit</a>
                              <a href="delete-form.php?table=inventory&id=<?= htmlspecialchars($row['inventory_id']) ?>" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="14">No records found.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- ===================== ORDERS ===================== -->
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
                    <?php if ($orders_array['success']): ?>
                      <?php foreach ($orders_array['data'] as $row):
                        $order_id      = $row['orders_id'];
                        $status        = $row['status'];
                        $confirmed     = $status === 'shipped';
                        $disabled      = $confirmed ? 'disabled' : '';
                        $label         = $confirmed ? 'Shipped'  : 'Ship';
                        $item_ids_json = htmlspecialchars(json_encode($row['item_ids'] ?? []));
                      ?>

                        <!-- Order header row — click to toggle items -->
                        <tr class="order-header-row" onclick="toggleOrderItems(<?= $order_id ?>)">
                          <td><?= htmlspecialchars($order_id)              ?></td>
                          <td><?= htmlspecialchars($row['reference_numb']) ?></td>
                          <td class="status-cell-<?= $order_id ?>"><?= htmlspecialchars(ucfirst($status)) ?></td>
                          <td><?= htmlspecialchars($row['ship_date'])      ?></td>
                          <td><?= htmlspecialchars($row['trailer_name'])   ?></td>
                          <td><?= count($row['items']) ?> item(s)</td>
                          <td>
                            <button class="btn-confirm"
                                    data-order-id="<?= $order_id ?>"
                                    data-item-ids="<?= $item_ids_json ?>"
                                    data-reference="<?= htmlspecialchars($row['reference_numb']) ?>"
                                    data-ship-date="<?= htmlspecialchars($row['ship_date']) ?>"
                                    data-trailer="<?= htmlspecialchars($row['trailer_name']) ?>"
                                    onclick="event.stopPropagation(); shipOrder(this)"
                                    <?= $disabled ?>>
                              <?= $label ?>
                            </button>
                          </td>
                        </tr>

                        <!-- Hidden items sub-table -->
                        <tr id="order-items-<?= $order_id ?>" style="display:none;">
                          <td colspan="7">
                            <table class="data-table">
                              <thead>
                                <tr>
                                  <th>Item ID</th>
                                  <th>Inventory ID</th>
                                  <th>Ficha</th>
                                  <th>SKU</th>
                                  <th>Unit #</th>
                                  <th>Description 1</th>
                                  <th>Description 2</th>
                                  <th>Quantity</th>
                                  <th>Unit</th>
                                  <th>Footage Qty</th>
                                  <th>Location</th>
                                  <th>UOM</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($row['items'] as $item): ?>
                                  <tr>
                                    <td><?= htmlspecialchars($item['item_id'])          ?></td>
                                    <td><?= htmlspecialchars($item['inventory_id'])     ?></td>
                                    <td><?= htmlspecialchars($item['ficha'])            ?></td>
                                    <td><?= htmlspecialchars($item['sku'])              ?></td>
                                    <td><?= htmlspecialchars($item['unit_numb'])        ?></td>
                                    <td><?= htmlspecialchars($item['description1'])    ?></td>
                                    <td><?= htmlspecialchars($item['description2'])    ?></td>
                                    <td><?= htmlspecialchars($item['quantity'])         ?></td>
                                    <td><?= htmlspecialchars($item['quantity_unit'])    ?></td>
                                    <td><?= htmlspecialchars($item['footage_quantity']) ?></td>
                                    <td><?= htmlspecialchars($item['location'])         ?></td>
                                    <td><?= htmlspecialchars($item['uom_primary'])      ?></td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </td>
                        </tr>

                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="7">No records found.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- ===================== MPL ===================== -->
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
                    <?php if ($mpl_array['success']): ?>
                      <?php foreach ($mpl_array['data'] as $row):
                        $mpl_id   = $row['mpl_id'];
                        $status   = $row['status'];
                        $received = $status === 'received';
                        $disabled = $received ? 'disabled' : '';
                        $label    = $received ? 'Received'  : 'Receive';
                      ?>

                        <!-- MPL header row — click to toggle items -->
                        <tr class="mpl-header-row" onclick="toggleMplItems(<?= $mpl_id ?>)">
                          <td><?= htmlspecialchars($mpl_id)                  ?></td>
                          <td><?= htmlspecialchars($row['order_number'])      ?></td>
                          <td class="mpl-status-cell-<?= $mpl_id ?>"><?= htmlspecialchars(ucfirst($status)) ?></td>
                          <td><?= htmlspecialchars($row['truck_number'])      ?></td>
                          <td><?= htmlspecialchars($row['expected_delivery']) ?></td>
                          <td><?= count($row['items']) ?> item(s)</td>
                          <td>
                            <button class="btn-confirm"
                                    data-mpl-id="<?= $mpl_id ?>"
                                    onclick="event.stopPropagation(); receiveMpl(this)"
                                    <?= $disabled ?>>
                              <?= $label ?>
                            </button>
                          </td>
                        </tr>

                        <!-- Hidden items sub-table -->
                        <tr id="mpl-items-<?= $mpl_id ?>" style="display:none;">
                          <td colspan="7">
                            <table class="data-table">
                              <thead>
                                <tr>
                                  <th>Ficha</th>
                                  <th>Quantity</th>
                                  <th>Description</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($row['items'] as $item): ?>
                                  <tr>
                                    <td><?= htmlspecialchars($item['ficha'])       ?></td>
                                    <td><?= htmlspecialchars($item['quantity'])    ?></td>
                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </td>
                        </tr>

                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="7">No records found.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </section>

            <div id="placeholderContent" class="placeholder-content" style="display:none;">
              <p class="placeholder-text">Content will be displayed here</p>
            </div>

          </div>
        </div>
      </div>
    </div>

    <div id="toast"></div>

    <script src="script.js"></script>
    <script>
      // -------------------------------------------------------
      // Ship an order — POST to local api/orders.php
      // which deducts inventory locally and notifies external API
      // -------------------------------------------------------
      async function shipOrder(btn) {
        const orderId   = btn.dataset.orderId;
        const itemIds   = JSON.parse(btn.dataset.itemIds);
        const reference = btn.dataset.reference;
        const shipDate  = btn.dataset.shipDate;
        const trailer   = btn.dataset.trailer;

        if (!confirm(`Ship order #${orderId}? This will deduct from inventory.`)) return;

        btn.disabled    = true;
        btn.textContent = 'Processing...';

        try {
          const res  = await fetch('api/orders.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
              action:       'ship',
              order_id:     parseInt(orderId),
              item_ids:     itemIds,
              reference:    reference,
              ship_date:    shipDate,
              trailer_name: trailer
            })
          });
          const data = await res.json();

          if (data.success) {
            document.querySelector(`.status-cell-${orderId}`).textContent = 'Shipped';
            btn.textContent = 'Shipped';
            showToast(data.message || 'Order shipped successfully.', 'success');
          } else {
            btn.disabled    = false;
            btn.textContent = 'Ship';
            showToast(data.error || 'Something went wrong.', 'error');
          }
        } catch (err) {
          btn.disabled    = false;
          btn.textContent = 'Ship';
          showToast('Network error — please try again.', 'error');
        }
      }

      // -------------------------------------------------------
      // Update an order — PUT through local api/orders.php
      // -------------------------------------------------------
      async function updateOrder(orderId, referenceNumb, shipDate, trailerName) {
        try {
          const res  = await fetch('api/orders.php', {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
              id:             orderId,
              reference_numb: referenceNumb,
              ship_date:      shipDate,
              trailer_name:   trailerName
            })
          });
          const data = await res.json();

          if (data.success) {
            showToast(data.message || 'Order updated.', 'success');
          } else {
            showToast(data.error || 'Update failed.', 'error');
          }
        } catch (err) {
          showToast('Network error — please try again.', 'error');
        }
      }

      // Receive an MPL — POST to local api/mpl.php
      async function receiveMpl(btn) {
        const mplId = btn.dataset.mplId;
        if (!confirm(`Receive MPL #${mplId}? This will add to inventory.`)) return;

        btn.disabled    = true;
        btn.textContent = 'Processing...';

        try {
          const res  = await fetch('api/mpl.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'receive', mpl_id: parseInt(mplId) })
          });
          const data = await res.json();

          if (data.success) {
            document.querySelector(`.mpl-status-cell-${mplId}`).textContent = 'Received';
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

      // Toast notification helper
      function showToast(message, type) {
        const toast         = document.getElementById('toast');
        toast.textContent   = message;
        toast.className     = type;
        toast.style.display = 'block';
        setTimeout(() => toast.style.display = 'none', 4000);
      }

      // Toggle expandable sub-tables
      function toggleOrderItems(orderId) {
        const row = document.getElementById(`order-items-${orderId}`);
        if (row) row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
      }

      function toggleMplItems(mplId) {
        const row = document.getElementById(`mpl-items-${mplId}`);
        if (row) row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
      }
    </script>
  </body>
</html>