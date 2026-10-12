<?php
/** @var mysqli $conn */
// Set $page before including this file: menu, orders, account, admin
if (!isset($conn) || !$conn) {
    include __DIR__ . '/config.php';
}

$base = "/takoyaki-shop-infoman";
if (!isset($page)) {
    $page = "menu";
}

// get the shop settings (opening time, closing time, delivery fee...)
$settings = array();
$opening = "10:00:00";
$closing = "20:00:00";

if (isset($conn) && $conn) {
    $result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    if (isset($settings['opening_time'])) {
        $opening = $settings['opening_time'];
    }
    if (isset($settings['closing_time'])) {
        $closing = $settings['closing_time'];
    }
}

$now = date("H:i:s");
$isOpen = ($now >= $opening && $now < $closing);

// number of items in the cart
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['qty'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Takoyaki Shop</title>
<link rel="stylesheet" href="<?= $base ?>/includes/style.css">
</head>
<body>
<div class="topline"></div>
<header>
    <div class="header-inner">
        <div class="brand">
            <div class="brand-mark">た</div>
            <div><strong>Takoyaki Shop</strong><small>Fresh from our griddle</small></div>
        </div>
        <?php if (($_SESSION['user_role'] ?? '') !== 'admin') { ?>
            <nav class="nav">
                <a href="<?= $base ?>/menu.php" class="<?php if ($page == 'menu') echo 'active'; ?>">Home</a>
                <a href="<?= $base ?>/orders.php" class="<?php if ($page == 'orders') echo 'active'; ?>">My Orders</a>
                <a href="<?= $base ?>/user/profile.php" class="<?php if ($page == 'account') echo 'active'; ?>">Account</a>
            </nav>
        <?php } ?>
        <div class="header-actions">
            <?php if (isset($_SESSION['user_name'])) { ?>
                    <?php if (($_SESSION['user_role'] ?? '') === 'customer') { ?>
                        <a class="btn secondary" href="<?= $base ?>/user/profile.php"><?= htmlspecialchars($_SESSION['user_name']) ?></a>
                    <?php } else { ?>
                        <span class="btn secondary"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <?php } ?>
                <a class="btn ghost" href="<?= $base ?>/logout.php">Log out</a>
            <?php } else { ?>
                <a class="btn secondary" href="<?= $base ?>/login.php">Sign in</a>
            <?php } ?>
            <?php if (($_SESSION['user_role'] ?? '') === 'customer') { ?>
                <a class="btn ghost" href="<?= $base ?>/menu.php#cartPanel">Cart <span><?= $cartCount ?></span></a>
            <?php } ?>
        </div>
    </div>
    <div class="statusline">
        <span>
            <i class="status-dot"></i>
            <?php if ($isOpen) { echo "Open for orders"; } else { echo "Currently closed"; } ?>
            · <?= date("g:i A", strtotime($opening)) ?>–<?= date("g:i A", strtotime($closing)) ?>
        </span>
        <span>Pick-up · Dine-in · Delivery</span>
    </div>
</header>
<main>
<div class="wrap">