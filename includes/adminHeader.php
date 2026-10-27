<?php

if (!isset($adminPage)) {
    $adminPage = "menu";
}
?>
<div class="admin-nav">
    <a href="<?php echo $base; ?>/admin/flavors/index.php" class="<?php if ($adminPage == 'menu') echo 'active'; ?>">Menu Management</a>
    <a href="<?php echo $base; ?>/admin/orders.php" class="<?php if ($adminPage == 'orders') echo 'active'; ?>">Orders &amp; Receipts</a>
    <a href="<?php echo $base; ?>/admin/users.php" class="<?php if ($adminPage == 'users') echo 'active'; ?>">User Management</a>
</div>