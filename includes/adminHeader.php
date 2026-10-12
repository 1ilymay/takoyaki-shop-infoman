<?php
// Set $adminPage before including.
if (!isset($adminPage)) {
    $adminPage = "menu";
}
?>
<div class="admin-nav">
    <a href="<?= $base ?>/admin/flavors/index.php" class="<?php if ($adminPage == 'menu') echo 'active'; ?>">Menu Management</a>
    <a href="<?= $base ?>/admin/orders.php" class="<?php if ($adminPage == 'orders') echo 'active'; ?>">Orders &amp; Receipts</a>
</div>