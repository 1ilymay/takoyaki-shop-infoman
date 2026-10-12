<?php
session_start();
include('includes/config.php');

$page = "menu";
include('includes/header.php');

// all flavors (available ones first)
$flavors = mysqli_query($conn, "SELECT * FROM flavor ORDER BY is_available DESC, name");

// the first available flavor is shown as the big featured card
$result = mysqli_query($conn, "SELECT flavor_id FROM flavor WHERE is_available = 1 ORDER BY name LIMIT 1");
$first = mysqli_fetch_assoc($result);
$featuredId = 0;
if ($first) {
    $featuredId = $first['flavor_id'];
}

// ---------- cart ----------
$cartLines = array();
$subtotal = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        $sizeId = (int)$item['size_id'];
        $result = mysqli_query($conn, "SELECT fs.size_label, fs.price, f.name
                                       FROM flavor_size fs
                                       INNER JOIN flavor f ON f.flavor_id = fs.flavor_id
                                       WHERE fs.size_id = $sizeId");
        $size = mysqli_fetch_assoc($result);
        if (!$size) {
            continue; // the size was deleted
        }

        // add-ons of this cart line
        $addonNames = array();
        $addonTotal = 0;
        foreach ($item['addons'] as $addonId) {
            $result = mysqli_query($conn, "SELECT name, price FROM addon WHERE addon_id = " . (int)$addonId . " AND is_available = 1");
            $addon = mysqli_fetch_assoc($result);
            if ($addon) {
                $addonNames[] = $addon['name'];
                $addonTotal += $addon['price'];
            }
        }

        $lineTotal = ($size['price'] + $addonTotal) * $item['qty'];
        $subtotal += $lineTotal;

        $cartLines[] = array(
            'key' => $key,
            'name' => $size['name'],
            'size' => $size['size_label'],
            'addons' => implode(", ", $addonNames),
            'qty' => $item['qty'],
            'total' => $lineTotal
        );
    }
}

$orderType = "delivery";
if (isset($_SESSION['order_type'])) {
    $orderType = $_SESSION['order_type'];
}

$deliveryFee = 0;
if ($orderType == "delivery") {
    $deliveryFee = $settings['delivery_fee'];
}
$total = $subtotal + $deliveryFee;
?>

<div class="hero">
    <div>
        <div class="eyebrow">Made fresh to order</div>
        <h2>Good food, made fresh.<br>Takoyaki worth sharing.</h2>
        <p>Choose your favorite flavor, pick a size, and we'll prepare it fresh for you.</p>
    </div>
    <div class="hero-mark"><img src="<?= $base ?>/images/takoyaki_logo.jpg" alt="Takoyaki" class="hero-logo"></div>
</div>

<?php include('includes/alert.php'); ?>

<div class="layout">
    <section>
        <div class="products">
            <?php
            if (mysqli_num_rows($flavors) == 0) {
                echo "<div class='panel empty' style='grid-column:1/-1'>No menu items yet. Please check back soon.</div>";
            }

            while ($f = mysqli_fetch_assoc($flavors)) {
                $flavorId = $f['flavor_id'];

                // sizes and add-ons of this card
                $sizes = mysqli_query($conn, "SELECT * FROM flavor_size WHERE flavor_id = $flavorId ORDER BY price");
                $addons = mysqli_query($conn, "SELECT * FROM addon WHERE is_available = 1 ORDER BY name");
                $hasSizes = (mysqli_num_rows($sizes) > 0);
                $canOrder = ($f['is_available'] == 1 && $hasSizes);

                $cardClass = "product";
                if ($flavorId == $featuredId) {
                    $cardClass .= " featured";
                }
                if ($f['is_available'] == 0) {
                    $cardClass .= " sold";
                }
            ?>
                <article class="<?= $cardClass ?>">
                    <div class="food-photo">
                        <?php if ($f['image']) { ?>
                            <img src="<?= $base . '/' . $f['image'] ?>" alt="">
                        <?php } else { ?>
                          <img src="<?= $base ?>/images/takoyaki_logo.jpg" alt="">
                        <?php } ?>
                        <?php if ($f['is_available'] == 0) { ?>
                            <div class="sold-overlay">Sold Out</div>
                        <?php } ?>
                    </div>

                    <div class="product-content">
                        <div class="product-head">
                            <h3><?= htmlspecialchars($f['name']) ?></h3>
                        </div>
                        <p class="product-desc"><?= htmlspecialchars($f['description']) ?></p>

                        <form action="cart_update.php" method="POST" style="display:contents">
                            <input type="hidden" name="action" value="add">

                            <?php if ($hasSizes) { ?>
                                <div>
                                    <label>Choose a size</label>
                                    <div class="sizes">
                                        <?php
                                        $first = true;
                                        while ($s = mysqli_fetch_assoc($sizes)) {
                                            $ok = ($f['is_available'] == 1 && $s['is_available'] == 1);
                                        ?>
                                            <label class="size-option">
                                                <input type="radio" name="size_id" value="<?= $s['size_id'] ?>"
                                                    <?php if ($ok && $first) { echo "checked"; $first = false; } ?>
                                                    <?php if (!$ok) echo "disabled"; ?>>
                                                <strong><?= $s['size_label'] ?></strong>₱<?= number_format($s['price'], 2) ?>
                                            </label>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <p class="tiny muted">No sizes available yet.</p>
                            <?php } ?>

                            <?php if ($canOrder && mysqli_num_rows($addons) > 0) { ?>
                                <div>
                                    <label>Optional toppings and extras</label>
                                    <div class="toppings">
                                        <?php while ($a = mysqli_fetch_assoc($addons)) { ?>
                                            <label class="topping">
                                                <input type="checkbox" name="addons[]" value="<?= $a['addon_id'] ?>">
                                                <?= htmlspecialchars($a['name']) ?> +₱<?= number_format($a['price'], 2) ?>
                                            </label>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>

                            <div class="product-actions">
                                <input type="number" name="qty" min="1" max="99" value="1" <?php if (!$canOrder) echo "disabled"; ?>>
                                <button type="submit" <?php if (!$canOrder) echo "disabled"; ?>>＋ Add to Cart</button>
                            </div>
                        </form>
                    </div>
                </article>
            <?php } ?>
        </div>
    </section>

    <!-- Cart -->
    <aside class="panel cart-panel" id="cartPanel">
        <h2>Your Cart <span class="muted tiny">(<?= $cartCount ?> items)</span></h2>

        <?php if (count($cartLines) > 0) { ?>
            <form action="cart_update.php" method="POST" class="field">
                <input type="hidden" name="action" value="set_type">
                <label for="order_type">Order type</label>
                <select id="order_type" name="order_type" onchange="this.form.submit()">
                    <option value="delivery" <?php if ($orderType == "delivery") echo "selected"; ?>>Delivery</option>
                    <option value="pickup" <?php if ($orderType == "pickup") echo "selected"; ?>>Pick-up</option>
                    <option value="dine-in" <?php if ($orderType == "dine-in") echo "selected"; ?>>Dine-in</option>
                </select>
            </form>

            <div class="cart-items">
                <?php foreach ($cartLines as $line) { ?>
                    <div class="cart-item">
                        <div class="cart-item-head">
                            <span><?= htmlspecialchars($line['name']) ?></span>
                            <span>₱<?= number_format($line['total'], 2) ?></span>
                        </div>
                        <small>
                            <?= $line['size'] ?>
                            <?php if ($line['addons'] != "") { echo " · " . htmlspecialchars($line['addons']); } ?>
                        </small>
                        <div class="qty">
                            <form action="cart_update.php" method="POST">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="key" value="<?= $line['key'] ?>">
                                <input type="hidden" name="qty" value="<?= $line['qty'] - 1 ?>">
                                <button type="submit">−</button>
                            </form>
                            <strong><?= $line['qty'] ?></strong>
                            <form action="cart_update.php" method="POST">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="key" value="<?= $line['key'] ?>">
                                <input type="hidden" name="qty" value="<?= $line['qty'] + 1 ?>">
                                <button type="submit">+</button>
                            </form>
                            <form action="cart_update.php" method="POST">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="key" value="<?= $line['key'] ?>">
                                <button type="submit" class="ghost small">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="summary-line"><span>Subtotal</span><strong>₱<?= number_format($subtotal, 2) ?></strong></div>
            <div class="summary-line">
                <span><?php if ($orderType == "delivery") { echo "Delivery fee"; } else { echo "Service fee"; } ?></span>
                <strong>₱<?= number_format($deliveryFee, 2) ?></strong>
            </div>

            <?php if ($orderType == "delivery" && $subtotal < $settings['min_order_for_delivery']) { ?>
                <div class="notice">
                    Minimum delivery order is ₱<?= number_format($settings['min_order_for_delivery'], 2) ?>.
                    Add ₱<?= number_format($settings['min_order_for_delivery'] - $subtotal, 2) ?> more.
                </div>
            <?php } ?>

            <div class="summary-line total"><span>Total</span><strong>₱<?= number_format($total, 2) ?></strong></div>
            <a class="btn full" href="checkout.php">Proceed to Checkout →</a>
            <p class="tiny muted">You can review your order before confirming.</p>
        <?php } else { ?>
            <div class="empty">Your cart is empty.<br><span class="tiny">Choose something delicious to get started.</span></div>
        <?php } ?>
    </aside>
</div>

<?php include('includes/footer.php'); ?>