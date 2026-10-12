<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$base = '/takoyaki-shop-infoman';
$page = 'admin';
$adminPage = 'orders';
$statuses = ['Pending', 'Preparing', 'Ready', 'Completed', 'Cancelled'];

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['status'] ?? '';

    if (!hash_equals($_SESSION['admin_csrf'], $postedToken)) {
        $_SESSION['message'] = 'Your session expired. Please try that action again.';
        $_SESSION['msg_type'] = 'danger';
    } elseif (!$orderId || !in_array($newStatus, $statuses, true)) {
        $_SESSION['message'] = 'Invalid order update.';
        $_SESSION['msg_type'] = 'danger';
    } else {
        $update = mysqli_prepare($conn, 'UPDATE orders SET status = ? WHERE order_id = ?');
        if ($update) {
            mysqli_stmt_bind_param($update, 'si', $newStatus, $orderId);
            if (mysqli_stmt_execute($update) && mysqli_stmt_affected_rows($update) >= 0) {
                $_SESSION['message'] = 'Order #' . $orderId . ' status updated.';
                $_SESSION['msg_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Could not update the order status.';
                $_SESSION['msg_type'] = 'danger';
            }
            mysqli_stmt_close($update);
        } else {
            $_SESSION['message'] = 'Could not update the order status.';
            $_SESSION['msg_type'] = 'danger';
        }
    }

    header('Location: ' . $base . '/admin/orders.php');
    exit();
}

$summary = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'preparing_orders' => 0,
    'ready_orders' => 0,
    'completed_sales' => 0,
];
$summaryResult = mysqli_query($conn, "SELECT COUNT(*) AS total_orders,
    COALESCE(SUM(status = 'Pending'), 0) AS pending_orders,
    COALESCE(SUM(status = 'Preparing'), 0) AS preparing_orders,
    COALESCE(SUM(status = 'Ready'), 0) AS ready_orders,
    COALESCE(SUM(CASE WHEN status = 'Completed' THEN total_amount ELSE 0 END), 0) AS completed_sales
    FROM orders");
if ($summaryResult) {
    $summary = array_merge($summary, mysqli_fetch_assoc($summaryResult) ?: []);
}

$orders = [];
$ordersResult = mysqli_query($conn, 'SELECT o.order_id, o.order_type, o.total_amount, o.status, o.order_date,
    cp.full_name, cp.contact_number
    FROM orders o
    INNER JOIN customer_profile cp ON cp.customer_id = o.customer_id
    ORDER BY o.order_date DESC');
if ($ordersResult) {
    while ($row = mysqli_fetch_assoc($ordersResult)) {
        $orders[] = $row;
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/adminHeader.php';
?>

<div class="page-title">
    <div>
        <div class="eyebrow">Admin management</div>
        <h1>Orders &amp; Receipts</h1>
        <p>Review incoming orders, update kitchen status, and print customer receipts.</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/alert.php'; ?>

<section class="stats">
    <div class="stat"><small>Total orders</small><strong><?= (int) $summary['total_orders'] ?></strong></div>
    <div class="stat"><small>Pending</small><strong><?= (int) $summary['pending_orders'] ?></strong></div>
    <div class="stat"><small>Preparing / Ready</small><strong><?= (int) $summary['preparing_orders'] + (int) $summary['ready_orders'] ?></strong></div>
    <div class="stat"><small>Completed sales</small><strong>₱<?= number_format((float) $summary['completed_sales'], 2) ?></strong></div>
</section>

<section class="panel">
    <h2>Recent orders</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Type / Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders) { ?>
                    <?php foreach ($orders as $order) { ?>
                        <tr>
                            <td><strong>#<?= (int) $order['order_id'] ?></strong></td>
                            <td>
                                <?= htmlspecialchars($order['full_name'] ?: 'Customer') ?>
                                <?php if (!empty($order['contact_number'])) { ?><div class="muted"><?= htmlspecialchars($order['contact_number']) ?></div><?php } ?>
                            </td>
                            <td><?= htmlspecialchars(ucfirst($order['order_type'])) ?><div class="muted"><?= htmlspecialchars(date('M d, Y g:i A', strtotime($order['order_date']))) ?></div></td>
                            <td><strong>₱<?= number_format((float) $order['total_amount'], 2) ?></strong></td>
                            <td><span class="status <?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
                            <td>
                                <div class="actions">
                                    <form action="<?= $base ?>/admin/orders.php" method="POST" class="admin-order-status">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                        <input type="hidden" name="order_id" value="<?= (int) $order['order_id'] ?>">
                                        <select name="status" aria-label="Status for order <?= (int) $order['order_id'] ?>">
                                            <?php foreach ($statuses as $status) { ?>
                                                <option value="<?= htmlspecialchars($status) ?>" <?= $status === $order['status'] ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                                            <?php } ?>
                                        </select>
                                        <button type="submit" class="small">Save</button>
                                    </form>
                                    <a class="btn small secondary" href="<?= $base ?>/admin/receipt.php?id=<?= (int) $order['order_id'] ?>">Receipt</a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td colspan="6" class="empty">No orders have been placed yet.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>