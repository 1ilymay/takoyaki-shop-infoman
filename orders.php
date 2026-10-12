<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

require_customer();

$page = 'orders';
$orders = [];
$ordersError = false;

if (isset($conn) && $conn) {
    $stmt = mysqli_prepare($conn, 'SELECT o.order_id, o.order_type, o.total_amount, o.status, o.order_date
        FROM orders o
        INNER JOIN customer_profile cp ON cp.customer_id = o.customer_id
        WHERE cp.user_id = ?
        ORDER BY o.order_date DESC');

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $orders[] = $row;
                }
            }
        } else {
            $ordersError = true;
        }
        mysqli_stmt_close($stmt);
    } else {
        $ordersError = true;
    }
} else {
    $ordersError = true;
}

include __DIR__ . '/includes/header.php';
?>

<div class="page-title">
    <div>
        <div class="eyebrow">My account</div>
        <h1>My Orders</h1>
    </div>
</div>

<?php include __DIR__ . '/includes/alert.php'; ?>

<section class="panel">
    <?php if ($ordersError) { ?>
        <div class="empty">We couldn't load your orders right now. Please try again later.</div>
    <?php } elseif (count($orders) > 0) { ?>
        <?php foreach ($orders as $order) { ?>
            <div class="order-card">
                <div class="order-card-head">
                    <div>
                        <strong>Order #<?= (int) $order['order_id'] ?></strong>
                        <p><?= htmlspecialchars(date('M d, Y', strtotime($order['order_date']))) ?> · <?= htmlspecialchars($order['order_type']) ?></p>
                    </div>
                    <span class="status <?= htmlspecialchars(strtolower($order['status'])) ?>"><?= htmlspecialchars($order['status']) ?></span>
                </div>
                <p>Total: <strong>₱<?= number_format((float) $order['total_amount'], 2) ?></strong></p>
            </div>
        <?php } ?>
    <?php } else { ?>
        <div class="empty">
            <p>No orders yet. Start by adding your favorite takoyaki to the cart.</p>
            <a class="btn" href="<?= htmlspecialchars($base) ?>/menu.php">Browse menu</a>
        </div>
    <?php } ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
