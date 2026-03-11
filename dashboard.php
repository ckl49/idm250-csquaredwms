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

  $sku_form_error   = null;
  $sku_form_success = null;

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_sku') {
    $product_id  = intval($_POST['product_id']    ?? 0);
    $sku_val     = trim($_POST['sku']             ?? '');
    $ficha       = intval($_POST['ficha']         ?? 0);
    $description = trim($_POST['description']     ?? '');
    $rate        = floatval($_POST['rate']        ?? 0);
    $length      = floatval($_POST['length_inches']  ?? 0);
    $width       = floatval($_POST['width_inches']   ?? 0);
    $height      = floatval($_POST['height_inches']  ?? 0);
    $weight      = floatval($_POST['weight_lbs']     ?? 0);
    $uom         = trim($_POST['uom_primary']     ?? '');
    $piece_count = intval($_POST['piece_count']   ?? 0);
    $assembly    = trim($_POST['assembly']        ?? 'FALSE');

    if ($product_id && $sku_val && $description) {
      $conn->begin_transaction();
      try {
        // Update products
        $stmt = $conn->prepare("UPDATE ashley.products SET ficha=?, sku=?, description=?, rate=? WHERE id=?");
        $stmt->bind_param("issdi", $ficha, $sku_val, $description, $rate, $product_id);
        if (!$stmt->execute()) throw new Exception("Failed to update product: " . $stmt->error);

        // Update products_dimensions
        $stmt = $conn->prepare("UPDATE ashley.products_dimensions SET length_inches=?, width_inches=?, height_inches=?, weight_lbs=? WHERE id=?");
        $stmt->bind_param("ddddi", $length, $width, $height, $weight, $product_id);
        if (!$stmt->execute()) throw new Exception("Failed to update dimensions: " . $stmt->error);

        // Update products_types (matched by ficha)
        $stmt = $conn->prepare("UPDATE ashley.products_types SET uom_primary=?, piece_count=?, assembly=? WHERE ficha=?");
        $stmt->bind_param("sisi", $uom, $piece_count, $assembly, $ficha);
        if (!$stmt->execute()) throw new Exception("Failed to update type: " . $stmt->error);

        $conn->commit();
        header("Location: dashboard.php?section=skus&sku_updated=1");
        exit;

      } catch (Exception $e) {
        $conn->rollback();
        $sku_form_error = $e->getMessage();
      }
    } else {
      $sku_form_error = 'Product ID, SKU, and Description are required.';
    }
  }

  // DELETE SKU
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_sku') {
    $product_id = intval($_POST['product_id'] ?? 0);

    if ($product_id) {
      $conn->begin_transaction();
      try {
        $stmt = $conn->prepare("SELECT ficha FROM ashley.products WHERE id=?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $row   = $stmt->get_result()->fetch_assoc();
        $ficha = $row['ficha'] ?? null;

        $stmt = $conn->prepare("DELETE FROM ashley.products WHERE id=?");
        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) throw new Exception("Failed to delete product");

        $stmt = $conn->prepare("DELETE FROM ashley.products_dimensions WHERE id=?");
        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) throw new Exception("Failed to delete dimensions");

        if ($ficha) {
          $stmt = $conn->prepare("DELETE FROM ashley.products_types WHERE ficha=?");
          $stmt->bind_param("i", $ficha);
          if (!$stmt->execute()) throw new Exception("Failed to delete type");
        }

        $conn->commit();
        header("Location: dashboard.php?section=skus&sku_deleted=1");
        exit;

      } catch (Exception $e) {
        $conn->rollback();
        $sku_form_error = $e->getMessage();
      }
    }
  }


  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_sku') {
    $ficha       = intval($_POST['ficha']         ?? 0);
    $sku_val     = trim($_POST['sku']             ?? '');
    $description = trim($_POST['description']     ?? '');
    $rate        = floatval($_POST['rate']        ?? 0);
    $length      = floatval($_POST['length_inches']  ?? 0);
    $width       = floatval($_POST['width_inches']   ?? 0);
    $height      = floatval($_POST['height_inches']  ?? 0);
    $weight      = floatval($_POST['weight_lbs']     ?? 0);
    $uom         = trim($_POST['uom_primary']     ?? '');
    $piece_count = intval($_POST['piece_count']   ?? 0);
    $assembly    = trim($_POST['assembly']        ?? 'FALSE');

    if (!$ficha || !$sku_val || !$description) {
      $sku_form_error = 'Ficha, SKU, and Description are required.';
    } else {
      $conn->begin_transaction();
      try {
        $stmt = $conn->prepare("INSERT INTO ashley.products (ficha, sku, description, rate) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issd", $ficha, $sku_val, $description, $rate);
        if (!$stmt->execute()) throw new Exception("Failed to insert product: " . $stmt->error);
        $new_id = $conn->insert_id;

        $stmt = $conn->prepare("INSERT INTO ashley.products_dimensions (id, length_inches, width_inches, height_inches, weight_lbs) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("idddd", $new_id, $length, $width, $height, $weight);
        if (!$stmt->execute()) throw new Exception("Failed to insert dimensions: " . $stmt->error);

        $stmt = $conn->prepare("INSERT INTO ashley.products_types (ficha, uom_primary, piece_count, assembly) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $ficha, $uom, $piece_count, $assembly);
        if (!$stmt->execute()) throw new Exception("Failed to insert type: " . $stmt->error);

        $conn->commit();
header("Location: dashboard.php?section=skus&sku_added=1");
exit;
      } catch (Exception $e) {
        $conn->rollback();
        $sku_form_error = $e->getMessage();
      }
    }
  }

  // INVENTORY MODAL
  $inv_form_error = null;

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_inventory') {
    $ficha            = trim($_POST['ficha']               ?? '');
    $sku_i            = trim($_POST['sku']                 ?? '');
    $quantity         = intval($_POST['quantity']          ?? 0);
    $description1     = trim($_POST['description1']        ?? '');
    $description2     = trim($_POST['description2']        ?? '');
    $quantity_unit    = trim($_POST['quantity_unit']        ?? '');
    $footage_quantity = floatval($_POST['footage_quantity'] ?? 0);

    if (!$ficha || !$sku_i || !$description1) {
      $inv_form_error = 'Ficha, SKU, and Description 1 are required.';
    } else {
      $stmt = $conn->prepare("INSERT INTO inventory (ficha, sku, quantity, description1, description2, quantity_unit, footage_quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssisssd", $ficha, $sku_i, $quantity, $description1, $description2, $quantity_unit, $footage_quantity);
      if ($stmt->execute()) {
        header("Location: dashboard.php?section=inventory&item_added=1"); exit;
      } else {
        $inv_form_error = 'Database error: ' . $stmt->error;
      }
    }
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_inventory') {
    $inv_id           = intval($_POST['inventory_id']       ?? 0);
    $ficha            = trim($_POST['ficha']                ?? '');
    $sku_i            = trim($_POST['sku']                  ?? '');
    $quantity         = intval($_POST['quantity']           ?? 0);
    $description1     = trim($_POST['description1']         ?? '');
    $description2     = trim($_POST['description2']         ?? '');
    $quantity_unit    = trim($_POST['quantity_unit']         ?? '');
    $footage_quantity = floatval($_POST['footage_quantity']  ?? 0);

    if (!$inv_id || !$ficha || !$sku_i || !$description1) {
      $inv_form_error = 'All required fields must be filled.';
    } else {
      $stmt = $conn->prepare("UPDATE inventory SET ficha=?, sku=?, quantity=?, description1=?, description2=?, quantity_unit=?, footage_quantity=? WHERE id=?");
      $stmt->bind_param("ssisssdi", $ficha, $sku_i, $quantity, $description1, $description2, $quantity_unit, $footage_quantity, $inv_id);
      if ($stmt->execute()) {
        header("Location: dashboard.php?section=inventory&item_updated=1"); exit;
      } else {
        $inv_form_error = 'Database error: ' . $stmt->error;
      }
    }
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_inventory') {
    $inv_id = intval($_POST['inventory_id'] ?? 0);
    if ($inv_id) {
      $stmt = $conn->prepare("DELETE FROM inventory WHERE id=?");
      $stmt->bind_param("i", $inv_id);
      if ($stmt->execute()) {
        header("Location: dashboard.php?section=inventory&item_deleted=1"); exit;
      } else {
        $inv_form_error = 'Delete failed: ' . $stmt->error;
      }
    }
  }

  // MPL MODAL
  $mpl_form_error = null;

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_mpl') {
    $order_number      = trim($_POST['order_number']      ?? '');
    $truck_number      = trim($_POST['truck_number']      ?? '');
    $expected_delivery = trim($_POST['expected_delivery'] ?? '');

    if (!$order_number || !$truck_number || !$expected_delivery) {
      $mpl_form_error = 'All fields are required.';
    } else {
      $stmt = $conn->prepare("INSERT INTO mpl (order_number, truck_number, expected_delivery) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $order_number, $truck_number, $expected_delivery);
      if ($stmt->execute()) {
        header("Location: dashboard.php?section=mpl&mpl_added=1"); exit;
      } else {
        $mpl_form_error = 'Database error: ' . $stmt->error;
      }
    }
  }

  $inventory_array = fetch_inventory($conn);
  $mpl_array       = fetch_mpl($conn);

  function fetch_skus($conn) {
    $sql = "SELECT 
                p.id          AS product_id,
                p.ficha,
                p.sku,
                p.description,
                p.rate,
                pd.length_inches,
                pd.width_inches,
                pd.height_inches,
                pd.weight_lbs,
                pt.uom_primary,
                pt.piece_count,
                pt.assembly
            FROM ashley.products p
            LEFT JOIN ashley.products_dimensions pd ON p.id = pd.id
            LEFT JOIN ashley.products_types pt      ON p.ficha = pt.ficha
            ORDER BY p.sku ASC";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
      $skus = [];
      while ($row = $result->fetch_assoc()) {
        $skus[] = $row;
      }
      return ['success' => true, 'data' => $skus];
    }

    return ['success' => false, 'data' => []];
  }

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

  // Fetch all orders from the ashley/ray API
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

  // UPDATE external api
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
  $skus_array   = fetch_skus($conn);
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
              <button class="nav-button"        onclick="navigate('skus')">SKU Management</button>
              <button class="nav-button active" onclick="navigate('inventory')">Inventory</button>
              <button class="nav-button"        onclick="navigate('orders')">Orders</button>
              <button class="nav-button"        onclick="navigate('mpl')">MPL</button>
              
            </div>
          </div>
          <form action="dashboard.php" method="post">
            <button class="nav-button" name="logout" value="logout">Log out</button>
          </form>
        </div>

        <div class="main-content">
          <div class="content-wrapper">
            <h2 class="content-title" id="contentTitle">Inventory</h2>

            <!--  INVENTORY  -->
            <section id="inventorySection" style="display:none;">
              <button class="nav-button" onclick="openModal('addInventoryModal')">+ Add Product</button>
              <div id="inventoryTable" class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>SKU</th>
                      <th>FICHA</th>
                      <th>Quantity</th>
                      <th>Quantity Unit</th>
                      <th>Description 1</th>
                      <th>Description 2</th>
                      <th>Footage Quantity</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($inventory_array['success']): ?>
                      <?php foreach ($inventory_array['data'] as $row): ?>
                        <tr>
                          <td><?= htmlspecialchars($row['inventory_id'])     ?></td>
                          <td><?= htmlspecialchars($row['sku'])              ?></td>
                          <td><?= htmlspecialchars($row['ficha'])            ?></td>
                          <td><?= htmlspecialchars($row['quantity'])         ?></td>
                          <td><?= htmlspecialchars($row['quantity_unit'])    ?></td>
                          <td><?= htmlspecialchars($row['description1'])     ?></td>
                          <td><?= htmlspecialchars($row['description2'])     ?></td>
                          <td><?= htmlspecialchars($row['footage_quantity']) ?></td>
                          <td>
                            <div class="actions-div">
                              <button class="sku-btn-edit" onclick="openEditInventory(
                                <?= intval($row['inventory_id']) ?>,
                                '<?= addslashes($row['ficha']) ?>',
                                '<?= addslashes($row['sku']) ?>',
                                <?= intval($row['quantity']) ?>,
                                '<?= addslashes($row['description1']) ?>',
                                '<?= addslashes($row['description2']) ?>',
                                '<?= addslashes($row['quantity_unit']) ?>',
                                <?= floatval($row['footage_quantity']) ?>
                              )">Edit</button>
                              <button class="sku-btn-delete" onclick="deleteInventory(<?= intval($row['inventory_id']) ?>, '<?= addslashes($row['sku']) ?>')">Delete</button>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="9">No records found.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </section>

            <!--  ORDERS  -->
            <section id="ordersSection" style="display:none;">
              <!-- [REMOVED] Add Order button — orders come from external API -->
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
                        <tr id="order-items-<?= $order_id ?>" style="display:none;">
                          <td colspan="7">
                            <table class="data-table">
                              <thead>
                                <tr>
                                  <th>Item ID</th><th>Inventory ID</th><th>Ficha</th><th>SKU</th>
                                  <th>Unit #</th><th>Description 1</th><th>Description 2</th>
                                  <th>Quantity</th><th>Unit</th><th>Footage Qty</th><th>Location</th><th>UOM</th>
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
                                    <td><?= htmlspecialchars($item['description1'])     ?></td>
                                    <td><?= htmlspecialchars($item['description2'])     ?></td>
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

             <!--  MPL  -->
             <section id="mplSection" style="display:none;">
              <button class="nav-button" onclick="openModal('addMplModal')">+ Add MPL</button>
              <div id="mplTable" class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Reference Number</th>
                      <th>Status</th>
                      <th>Trailer Name</th>
                      <th>Ship Date</th>
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
                        $label    = $received ? 'Received' : 'Receive';
                      ?>

                        <tr class="mpl-header-row" onclick="toggleMplItems(<?= $mpl_id ?>)">
                          <td><?= htmlspecialchars($mpl_id)                  ?></td>
                          <td><?= htmlspecialchars($row['reference_numb'])   ?></td>
                          <td class="mpl-status-cell-<?= $mpl_id ?>"><?= htmlspecialchars(ucfirst($status)) ?></td>
                          <td><?= htmlspecialchars($row['trailer_name'])     ?></td>
                          <td><?= htmlspecialchars($row['ship_date'])        ?></td>
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

                        <tr id="mpl-items-<?= $mpl_id ?>" style="display:none;">
                          <td colspan="7">
                            <table class="data-table">
                              <thead>
                                <tr>
                                  <th>Item ID</th>
                                  <th>Ficha</th>
                                  <th>Unit #</th>
                                  <th>Description 1</th>
                                  <th>Description 2</th>
                                  <th>Quantity</th>
                                  <th>Quantity Unit</th>
                                  <th>Footage Qty</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($row['items'] as $item): ?>
                                  <tr>
                                    <td><?= htmlspecialchars($item['item_id'])          ?></td>
                                    <td><?= htmlspecialchars($item['ficha'])            ?></td>
                                    <td><?= htmlspecialchars($item['unit_numb'])        ?></td>
                                    <td><?= htmlspecialchars($item['description1'])     ?></td>
                                    <td><?= htmlspecialchars($item['description2'])     ?></td>
                                    <td><?= htmlspecialchars($item['quantity'])         ?></td>
                                    <td><?= htmlspecialchars($item['quantity_unit'])    ?></td>
                                    <td><?= htmlspecialchars($item['footage_quantity']) ?></td>
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

<!--  SKU MANAGEMENT  -->
            <section id="skusSection" style="display:none;">

              <div class="sku-toolbar">
                <input type="text"
                       id="skuSearch"
                       class="sku-search"
                       placeholder="Search by SKU, description, or ficha…"
                       oninput="filterSkus()">
                <span class="sku-count" id="skuCount">
                  <?= count($skus_array['data']) ?> SKU<?= count($skus_array['data']) !== 1 ? 's' : '' ?>
                </span>
                <button class="btn-add-sku"
                        onclick="document.getElementById('addSkuModal').classList.add('open')">
                  + Add SKU
                </button>
              </div>

              <div class="sku-grid" id="skuGrid">
                <?php if ($skus_array['success']): ?>
                  <?php foreach ($skus_array['data'] as $sku): ?>
                    <div class="sku-card"
                         data-search="<?= strtolower(htmlspecialchars($sku['sku'] . ' ' . $sku['description'] . ' ' . $sku['ficha'])) ?>">

                      <div class="sku-card-header">
                        <span class="sku-badge"><?= htmlspecialchars($sku['sku']) ?></span>
                        <span class="sku-ficha">Ficha <?= htmlspecialchars($sku['ficha']) ?></span>
                      </div>

                      <div class="sku-description"><?= htmlspecialchars($sku['description']) ?></div>

                      <div class="sku-details">
                        <?php if ($sku['uom_primary']): ?>
                          <span class="sku-tag"><?= htmlspecialchars($sku['uom_primary']) ?></span>
                        <?php endif; ?>
                        <?php if ($sku['piece_count']): ?>
                          <span class="sku-tag"><?= htmlspecialchars($sku['piece_count']) ?> pcs</span>
                        <?php endif; ?>
                        <?php if ($sku['length_inches'] || $sku['width_inches'] || $sku['height_inches']): ?>
                          <span class="sku-tag">
                            <?= htmlspecialchars($sku['length_inches']) ?>″
                            x <?= htmlspecialchars($sku['width_inches']) ?>″
                            x <?= htmlspecialchars($sku['height_inches']) ?>″
                          </span>
                        <?php endif; ?>
                        <?php if ($sku['weight_lbs']): ?>
                          <span class="sku-tag"><?= htmlspecialchars($sku['weight_lbs']) ?> lbs</span>
                        <?php endif; ?>
                        <?php if ($sku['assembly'] === 'TRUE'): ?>
                          <span class="sku-tag">Assembly</span>
                        <?php endif; ?>
                      </div>

                      <div class="sku-rate">$<?= number_format($sku['rate'], 2) ?> / unit</div>

                      <!-- Edit / Delete actions -->
                      <div class="sku-card-actions">
                        <button class="sku-btn-edit" onclick="openEditSku(
                          <?= $sku['product_id'] ?>,
                          '<?= addslashes($sku['sku']) ?>',
                          <?= intval($sku['ficha']) ?>,
                          '<?= addslashes($sku['description']) ?>',
                          <?= floatval($sku['rate']) ?>,
                          '<?= addslashes($sku['uom_primary']) ?>',
                          <?= intval($sku['piece_count']) ?>,
                          '<?= addslashes($sku['assembly']) ?>',
                          <?= floatval($sku['length_inches']) ?>,
                          <?= floatval($sku['width_inches']) ?>,
                          <?= floatval($sku['height_inches']) ?>,
                          <?= floatval($sku['weight_lbs']) ?>
                        )">Edit</button>
                        <button class="sku-btn-delete" onclick="deleteSku(<?= $sku['product_id'] ?>, '<?= addslashes($sku['sku']) ?>')">Delete</button>
                      </div>

                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="no-results">No SKUs found in the database.</p>
                <?php endif; ?>
              </div>

            </section>

            <div id="placeholderContent" class="placeholder-content" style="display:none;">
              <p class="placeholder-text">Content will be displayed here</p>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- ===================== ADD INVENTORY MODAL ===================== -->
    <div class="modal-overlay" id="addInventoryModal">
      <div class="modal-card">
        <div class="modal-header">
          <span class="modal-title">Add Inventory Item</span>
          <button class="modal-close" onclick="closeModal('addInventoryModal')">✕</button>
        </div>
        <?php if ($inv_form_error && $_POST['action'] === 'add_inventory'): ?>
          <div class="form-msg error"><?= htmlspecialchars($inv_form_error) ?></div>
        <?php endif; ?>
        <form method="POST" action="dashboard.php" class="edit-form">
          <input type="hidden" name="action" value="add_inventory">
          <div class="modal-grid">
            <div class="input-div">
              <label>FICHA *</label>
              <input type="text" name="ficha" placeholder="e.g. 452" required>
            </div>
            <div class="input-div">
              <label>SKU *</label>
              <input type="text" name="sku" placeholder="e.g. 1720823-0567" required>
            </div>
            <div class="input-div full-width">
              <label>Description 1 *</label>
              <input type="text" name="description1" placeholder="e.g. BIRCH YEL FAS 6/4 RGH KD 10FT" required>
            </div>
            <div class="input-div full-width">
              <label>Description 2</label>
              <input type="text" name="description2" placeholder="e.g. Medex FSCMC 120">
            </div>
            <div class="input-div">
              <label>Quantity</label>
              <input type="number" name="quantity" placeholder="e.g. 100">
            </div>
            <div class="input-div">
              <label>Quantity Unit</label>
              <input type="text" name="quantity_unit" placeholder="e.g. PC">
            </div>
            <div class="input-div full-width">
              <label>Footage Quantity</label>
              <input type="number" name="footage_quantity" step="any" placeholder="e.g. 1320.28">
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="form-cancel-btn" onclick="closeModal('addInventoryModal')">Cancel</button>
            <button type="submit" class="form-save-btn">Add Item</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===================== EDIT INVENTORY MODAL ===================== -->
    <div class="modal-overlay" id="editInventoryModal">
      <div class="modal-card">
        <div class="modal-header">
          <span class="modal-title">Edit Inventory Item</span>
          <button class="modal-close" onclick="closeModal('editInventoryModal')">✕</button>
        </div>
        <?php if ($inv_form_error && $_POST['action'] === 'edit_inventory'): ?>
          <div class="form-msg error"><?= htmlspecialchars($inv_form_error) ?></div>
        <?php endif; ?>
        <form method="POST" action="dashboard.php" class="edit-form">
          <input type="hidden" name="action"       value="edit_inventory">
          <input type="hidden" name="inventory_id" id="edit_inv_id">
          <div class="modal-grid">
            <div class="input-div">
              <label>FICHA *</label>
              <input type="text" name="ficha" id="edit_inv_ficha" required>
            </div>
            <div class="input-div">
              <label>SKU *</label>
              <input type="text" name="sku" id="edit_inv_sku" required>
            </div>
            <div class="input-div full-width">
              <label>Description 1 *</label>
              <input type="text" name="description1" id="edit_inv_desc1" required>
            </div>
            <div class="input-div full-width">
              <label>Description 2</label>
              <input type="text" name="description2" id="edit_inv_desc2">
            </div>
            <div class="input-div">
              <label>Quantity</label>
              <input type="number" name="quantity" id="edit_inv_quantity">
            </div>
            <div class="input-div">
              <label>Quantity Unit</label>
              <input type="text" name="quantity_unit" id="edit_inv_unit">
            </div>
            <div class="input-div full-width">
              <label>Footage Quantity</label>
              <input type="number" name="footage_quantity" id="edit_inv_footage" step="any">
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="form-cancel-btn" onclick="closeModal('editInventoryModal')">Cancel</button>
            <button type="submit" class="form-save-btn">Save Changes</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Inventory hidden form -->
    <form method="POST" action="dashboard.php" id="deleteInventoryForm" style="display:none;">
      <input type="hidden" name="action"       value="delete_inventory">
      <input type="hidden" name="inventory_id" id="delete_inv_id">
    </form>

    <!-- ===================== ADD MPL MODAL ===================== -->
    <div class="modal-overlay" id="addMplModal">
      <div class="modal-card">
        <div class="modal-header">
          <span class="modal-title">Add MPL</span>
          <button class="modal-close" onclick="closeModal('addMplModal')">✕</button>
        </div>
        <?php if ($mpl_form_error && $_POST['action'] === 'add_mpl'): ?>
          <div class="form-msg error"><?= htmlspecialchars($mpl_form_error) ?></div>
        <?php endif; ?>
        <form method="POST" action="dashboard.php" class="edit-form">
          <input type="hidden" name="action" value="add_mpl">
          <div class="modal-grid">
            <div class="input-div">
              <label>Order Number *</label>
              <input type="text" name="order_number" placeholder="e.g. 12345" required>
            </div>
            <div class="input-div">
              <label>Truck Number *</label>
              <input type="text" name="truck_number" placeholder="e.g. Truck123" required>
            </div>
            <div class="input-div full-width">
              <label>Expected Delivery *</label>
              <input type="date" name="expected_delivery" required>
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="form-cancel-btn" onclick="closeModal('addMplModal')">Cancel</button>
            <button type="submit" class="form-save-btn">Add MPL</button>
          </div>
        </form>
      </div>
    </div>

    <!--  ADD SKU MODAL  -->
    <div class="modal-overlay" id="addSkuModal">
      <div class="modal-card">
        <div class="modal-header">
          <span class="modal-title">Add New SKU</span>
          <button class="modal-close" onclick="closeModal('addSkuModal')">✕</button>
        </div>

        <?php if ($sku_form_error): ?>
          <div class="form-msg error"><?= htmlspecialchars($sku_form_error) ?></div>
        <?php endif; ?>
        <?php if ($sku_form_success): ?>
          <div class="form-msg success"><?= htmlspecialchars($sku_form_success) ?></div>
        <?php endif; ?>

        <form method="POST" action="dashboard.php" class="edit-form">
          <input type="hidden" name="action" value="add_sku">
          <div class="modal-grid">
            <div class="input-div full-width">
              <label>Description *</label>
              <input type="text" name="description" placeholder="e.g. MAPLE HARD FAS 5/4 RGH KD" required>
            </div>
            <div class="input-div">
              <label>SKU *</label>
              <input type="text" name="sku" placeholder="e.g. 1720818-0167" required>
            </div>
            <div class="input-div">
              <label>Ficha *</label>
              <input type="number" name="ficha" placeholder="e.g. 223" required>
            </div>
            <div class="input-div">
              <label>Rate ($/unit)</label>
              <input type="number" name="rate" step="0.01" placeholder="e.g. 16.18">
            </div>
            <div class="input-div">
              <label>UOM Primary</label>
              <select name="uom_primary" class="form-select">
                <option value="PALLET">PALLET</option>
                <option value="BUNDLE">BUNDLE</option>
              </select>
            </div>
            <div class="input-div">
              <label>Piece Count</label>
              <input type="number" name="piece_count" placeholder="e.g. 120">
            </div>
            <div class="input-div">
              <label>Assembly</label>
              <select name="assembly" class="form-select">
                <option value="FALSE">FALSE</option>
                <option value="TRUE">TRUE</option>
              </select>
            </div>
            <div class="input-div">
              <label>Length (inches)</label>
              <input type="number" name="length_inches" step="0.01" placeholder="e.g. 120">
            </div>
            <div class="input-div">
              <label>Width (inches)</label>
              <input type="number" name="width_inches" step="0.01" placeholder="e.g. 48">
            </div>
            <div class="input-div">
              <label>Height (inches)</label>
              <input type="number" name="height_inches" step="0.01" placeholder="e.g. 38">
            </div>
            <div class="input-div">
              <label>Weight (lbs)</label>
              <input type="number" name="weight_lbs" step="0.01" placeholder="e.g. 3750">
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="form-cancel-btn" onclick="closeModal('addSkuModal')">Cancel</button>
            <button type="submit" class="form-save-btn">Add SKU</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===================== EDIT SKU MODAL ===================== -->
    <div class="modal-overlay" id="editSkuModal">
      <div class="modal-card">
        <div class="modal-header">
          <span class="modal-title">Edit SKU</span>
          <button class="modal-close" onclick="closeModal('editSkuModal')">✕</button>
        </div>

        <form method="POST" action="dashboard.php" class="edit-form">
          <input type="hidden" name="action"     value="edit_sku">
          <input type="hidden" name="product_id" id="edit_product_id">
          <div class="modal-grid">
            <div class="input-div full-width">
              <label>Description *</label>
              <input type="text" name="description" id="edit_description" required>
            </div>
            <div class="input-div">
              <label>SKU *</label>
              <input type="text" name="sku" id="edit_sku" required>
            </div>
            <div class="input-div">
              <label>Ficha *</label>
              <input type="number" name="ficha" id="edit_ficha" required>
            </div>
            <div class="input-div">
              <label>Rate ($/unit)</label>
              <input type="number" name="rate" id="edit_rate" step="0.01">
            </div>
            <div class="input-div">
              <label>UOM Primary</label>
              <select name="uom_primary" id="edit_uom_primary" class="form-select">
                <option value="PALLET">PALLET</option>
                <option value="BUNDLE">BUNDLE</option>
              </select>
            </div>
            <div class="input-div">
              <label>Piece Count</label>
              <input type="number" name="piece_count" id="edit_piece_count">
            </div>
            <div class="input-div">
              <label>Assembly</label>
              <select name="assembly" id="edit_assembly" class="form-select">
                <option value="FALSE">FALSE</option>
                <option value="TRUE">TRUE</option>
              </select>
            </div>
            <div class="input-div">
              <label>Length (inches)</label>
              <input type="number" name="length_inches" id="edit_length_inches" step="0.01">
            </div>
            <div class="input-div">
              <label>Width (inches)</label>
              <input type="number" name="width_inches" id="edit_width_inches" step="0.01">
            </div>
            <div class="input-div">
              <label>Height (inches)</label>
              <input type="number" name="height_inches" id="edit_height_inches" step="0.01">
            </div>
            <div class="input-div">
              <label>Weight (lbs)</label>
              <input type="number" name="weight_lbs" id="edit_weight_lbs" step="0.01">
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="form-cancel-btn" onclick="closeModal('editSkuModal')">Cancel</button>
            <button type="submit" class="form-save-btn">Save Changes</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===================== DELETE SKU FORM (hidden) ===================== -->
    <form method="POST" action="dashboard.php" id="deleteSkuForm" style="display:none;">
      <input type="hidden" name="action"     value="delete_sku">
      <input type="hidden" name="product_id" id="delete_product_id">
    </form>

    <div id="toast"></div>

    <script src="script.js"></script>
    <script>
      // SKU search
      function filterSkus() {
        const query = document.getElementById('skuSearch').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.sku-card');
        let visible = 0;

        cards.forEach(card => {
          const matches = card.dataset.search.includes(query);
          card.style.display = matches ? '' : 'none';
          if (matches) visible++;
        });

        document.getElementById('skuCount').textContent =
          visible + ' SKU' + (visible !== 1 ? 's' : '');

        let noResults = document.getElementById('noSkuResults');
        if (visible === 0) {
          if (!noResults) {
            noResults = document.createElement('p');
            noResults.id        = 'noSkuResults';
            noResults.className = 'no-results';
            noResults.textContent = 'No SKUs match your search.';
            document.getElementById('skuGrid').appendChild(noResults);
          }
          noResults.style.display = '';
        } else if (noResults) {
          noResults.style.display = 'none';
        }
      }

      // Modal helpers
      function openModal(id) {
        document.getElementById(id).classList.add('open');
      }

      function closeModal(id) {
        document.getElementById(id).classList.remove('open');
      }

      // Close on overlay click
      document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
          if (e.target === this) closeModal(this.id);
        });
      });

      // Edit SKU — pre-fill modal fields
      function openEditSku(id, sku, ficha, description, rate, uom, pieceCount, assembly, length, width, height, weight) {
        document.getElementById('edit_product_id').value   = id;
        document.getElementById('edit_sku').value          = sku;
        document.getElementById('edit_ficha').value        = ficha;
        document.getElementById('edit_description').value  = description;
        document.getElementById('edit_rate').value         = rate;
        document.getElementById('edit_uom_primary').value  = uom;
        document.getElementById('edit_piece_count').value  = pieceCount;
        document.getElementById('edit_assembly').value     = assembly;
        document.getElementById('edit_length_inches').value = length;
        document.getElementById('edit_width_inches').value  = width;
        document.getElementById('edit_height_inches').value = height;
        document.getElementById('edit_weight_lbs').value    = weight;

        document.getElementById('editSkuModal').classList.add('open');
      }

      // Delete SKU 
      function deleteSku(id, sku) {
        if (!confirm(`Delete SKU "${sku}"? This cannot be undone.`)) return;
        document.getElementById('delete_product_id').value = id;
        document.getElementById('deleteSkuForm').submit();
      }

      // EDIT MODAL (inventory)
      function openEditInventory(id, ficha, sku, quantity, desc1, desc2, unit, footage) {
        document.getElementById('edit_inv_id').value       = id;
        document.getElementById('edit_inv_ficha').value    = ficha;
        document.getElementById('edit_inv_sku').value      = sku;
        document.getElementById('edit_inv_quantity').value = quantity;
        document.getElementById('edit_inv_desc1').value    = desc1;
        document.getElementById('edit_inv_desc2').value    = desc2;
        document.getElementById('edit_inv_unit').value     = unit;
        document.getElementById('edit_inv_footage').value  = footage;
        openModal('editInventoryModal');
      }

      // DELETE MODAL (inventory)
      function deleteInventory(id, sku) {
        if (!confirm(`Delete inventory item "${sku}"? This cannot be undone.`)) return;
        document.getElementById('delete_inv_id').value = id;
        document.getElementById('deleteInventoryForm').submit();
      }

      <?php if (isset($_GET['section']) && $_GET['section'] === 'skus'): ?>
        document.addEventListener('DOMContentLoaded', () => {
          <?php if (isset($_GET['sku_added'])): ?>
            showToast('SKU added successfully.', 'success');
          <?php endif; ?>
          <?php if (isset($_GET['sku_updated'])): ?>
            showToast('SKU updated successfully.', 'success');
          <?php endif; ?>
          <?php if (isset($_GET['sku_deleted'])): ?>
            showToast('SKU deleted.', 'success');
          <?php endif; ?>
        });
      <?php endif; ?>

      <?php if ($sku_form_error): ?>
        document.addEventListener('DOMContentLoaded', () => {
          document.getElementById('addSkuModal').classList.add('open');
        });
      <?php endif; ?>

      // TOAST TRIGGER feedback for inventory/mpl actions
      <?php if (isset($_GET['section']) && $_GET['section'] === 'inventory'): ?>
        document.addEventListener('DOMContentLoaded', () => {
          <?php if (isset($_GET['item_added'])):   ?> showToast('Inventory item added.',   'success'); <?php endif; ?>
          <?php if (isset($_GET['item_updated'])): ?> showToast('Inventory item updated.', 'success'); <?php endif; ?>
          <?php if (isset($_GET['item_deleted'])): ?> showToast('Inventory item deleted.', 'success'); <?php endif; ?>
          <?php if ($inv_form_error): ?> openModal('<?= $_POST['action'] === 'edit_inventory' ? 'editInventoryModal' : 'addInventoryModal' ?>'); <?php endif; ?>
        });
      <?php endif; ?>

      <?php if (isset($_GET['section']) && $_GET['section'] === 'mpl'): ?>
        document.addEventListener('DOMContentLoaded', () => {
          <?php if (isset($_GET['mpl_added'])): ?> showToast('MPL added.', 'success'); <?php endif; ?>
          <?php if ($mpl_form_error): ?> openModal('addMplModal'); <?php endif; ?>
        });
      <?php endif; ?>

      // Ship an order
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

      // Update an order
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

      // Receive an MPL
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