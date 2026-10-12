<?php
// Set $adminPage before including: menu, addons, ingredients, orders, ...
// Change class='soon' to a normal link once that page is finished.
if (!isset($adminPage)) {
    $adminPage = "menu";
}
?>
<div class="admin-nav">
    <a href="<?= $base ?>/admin/flavors/index.php" class="<?php if ($adminPage == 'menu') echo 'active'; ?>">Menu</a>
    <a href="#" class="soon">Add-ons</a>
    <a href="#" class="soon">Ingredients &amp; Recipes</a>
    <a href="#" class="soon">Orders</a>
    <a href="#" class="soon">Payments</a>
    <a href="#" class="soon">Reports</a>
    <a href="#" class="soon">Customers</a>
    <a href="#" class="soon">Team</a>
    <a href="#" class="soon">Shop Settings</a>
</div>