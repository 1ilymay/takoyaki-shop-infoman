<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

require_customer();

$page = 'menu';
include 'includes/header.php';

$search = "";
if (isset($_GET['q'])) {
    $search = trim($_GET['q']);
}

$flavors = false;
if (isset($conn) && $conn) {
    $like = "%" . $search . "%";
    $sql = "SELECT * FROM flavor WHERE name LIKE ? OR description LIKE ? ORDER BY is_available DESC, name";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
    mysqli_stmt_execute($stmt);
    $flavors = mysqli_stmt_get_result($stmt);
}

$first = null;
if (isset($conn) && $conn) {
    $result = mysqli_query($conn, 'SELECT flavor_id FROM flavor WHERE is_available = 1 ORDER BY name LIMIT 1');
    $first = mysqli_fetch_assoc($result);
}
$featuredId = 0;
if ($first) {
    $featuredId = $first['flavor_id'];
}

$cartLines = [];
$subtotal = 0;
if (isset($_SESSION['cart']) && isset($conn) && $conn) {
    foreach ($_SESSION['cart'] as $key => $item) {
        $sizeId = (int) $item['size_id'];
        $result = mysqli_query($conn, "SELECT fs.size_label, fs.price, f.name
                                       FROM flavor_size fs
                                       INNER JOIN flavor f ON f.flavor_id = fs.flavor_id
                                       WHERE fs.size_id = $sizeId");
        $size = mysqli_fetch_assoc($result);

        if (!$size) {
            continue;
        }

        $addonNames = [];
        $addonTotal = 0;
        foreach ($item['addons'] as $addonId) {
            $result = mysqli_query($conn, 'SELECT name, price FROM addon WHERE addon_id = ' . (int) $addonId . ' AND is_available = 1');
            $addon = mysqli_fetch_assoc($result);
            if ($addon) {
                $addonNames[] = $addon['name'];
                $addonTotal += $addon['price'];
            }
        }

        $lineTotal = ($size['price'] + $addonTotal) * $item['qty'];
        $subtotal += $lineTotal;

        $cartLines[] = [
            'key' => $key,
            'name' => $size['name'],
            'size' => $size['size_label'],
            'addons' => implode(', ', $addonNames),
            'qty' => $item['qty'],
            'total' => $lineTotal,
        ];
    }
}

$orderType = 'delivery';
if (isset($_SESSION['order_type'])) {
    $orderType = $_SESSION['order_type'];
}

$deliveryFee = 0;
if ($orderType == 'delivery') {
    $deliveryFee = $settings['delivery_fee'];
}
$total = $subtotal + $deliveryFee;
?>

<?php
// hero background photo: put your photo in the images folder and name it hero.jpg (or .jpeg, .png, .webp)
$heroImg = $base . '/images/takoyaki_logo.jpg';
$heroClass = ' hero-fallback';
foreach (array('jpg', 'jpeg', 'png', 'webp') as $ext) {
    if (file_exists(__DIR__ . '/images/hero.' . $ext)) {
        $heroImg = $base . '/images/hero.' . $ext;
        $heroClass = '';
        break;
    }
}
?>

<div class="hero-banner<?= $heroClass ?>" style="--hero-img: url('<?= $heroImg ?>');">
    <div class="hero-copy">
        <div class="eyebrow">Fresh off the griddle</div>
        <h2>Hot takoyaki, made for sharing.</h2>
        <p>Choose your favorite flavor and build the perfect bite.</p>
        <a class="btn" href="#products">Order now</a>
    </div>
</div>

<?php include 'includes/alert.php'; ?>

<form method="GET" action="menu.php" style="display:flex; gap:8px; margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search flavors...">
    <button type="submit">Search</button>
    <?php if ($search != "") { ?>
        <a class="btn ghost" href="menu.php">Clear</a>
    <?php } ?>
</form>

<div class="layout" id="products">
    <section>
        <div class="products">
            <?php
            if ($flavors && mysqli_num_rows($flavors) == 0) {
                echo "<div class='panel empty' style='grid-column:1/-1'>No menu items yet. Please check back soon.</div>";
            }

            if ($flavors) {
                while ($f = mysqli_fetch_assoc($flavors)) {
                    $flavorId = $f['flavor_id'];
                    $sizes = mysqli_query($conn, "SELECT * FROM flavor_size WHERE flavor_id = $flavorId ORDER BY price");
                    $addons = mysqli_query($conn, 'SELECT * FROM addon WHERE is_available = 1 ORDER BY name');
                    $hasSizes = ($sizes && mysqli_num_rows($sizes) > 0);
                    $canOrder = ($f['is_available'] == 1 && $hasSizes);

                    $cardClass = 'product';
                    if ($flavorId == $featuredId) {
                        $cardClass .= ' featured';
                    }
                    if ($f['is_available'] == 0) {
                        $cardClass .= ' sold';
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
                                        $firstSize = true;
                                        while ($s = mysqli_fetch_assoc($sizes)) {
                                            $ok = ($f['is_available'] == 1 && $s['is_available'] == 1);
                                        ?>
                                            <label class="size-option">
                                                <input type="radio" name="size_id" value="<?= $s['size_id'] ?>"
                                                    <?php if ($ok && $firstSize) { echo 'checked'; $firstSize = false; } ?>
                                                    <?php if (!$ok) echo 'disabled'; ?>>
                                                <strong><?= $s['size_label'] ?></strong>₱<?= number_format($s['price'], 2) ?>
                                            </label>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <p class="tiny muted">No sizes available yet.</p>
                            <?php } ?>

                            <?php if ($canOrder && $addons && mysqli_num_rows($addons) > 0) { ?>
                                <details class="toppings-box">
                                    <summary>＋ Toppings and extras</summary>
                                    <div class="toppings">
                                        <?php while ($a = mysqli_fetch_assoc($addons)) { ?>
                                            <label class="topping">
                                                <input type="checkbox" name="addons[]" value="<?= $a['addon_id'] ?>">
                                                <?= htmlspecialchars($a['name']) ?> +₱<?= number_format($a['price'], 2) ?>
                                            </label>
                                        <?php } ?>
                                    </div>
                                </details>
                            <?php } ?>

                            <div class="product-actions">
                                <input type="number" name="qty" min="1" max="99" value="1" <?php if (!$canOrder) echo 'disabled'; ?>>
                                <button type="submit" <?php if (!$canOrder) echo 'disabled'; ?>>＋ Add to Cart</button>
                            </div>
                        </form>
                    </div>
                </article>
                <?php }
            }
            ?>
        </div>
    </section>

        <div class="side-col">
        <div class="promo-card mini">
            <div class="eyebrow">Today’s deal</div>
            <h3>30% off combo packs</h3>
            <p>Perfect for sharing with friends.</p>
        </div>

        <div class="promo-card mini alt">
            <div class="eyebrow">Chef’s pick</div>
            <h3>Signature flavor</h3>
            <p>Smoky, savory, and crispy every time.</p>
        </div>

    <aside class="panel cart-panel" id="cartPanel">
        <h2>Your Cart <span class="muted tiny">(<?= $cartCount ?> items)</span></h2>

        <?php if (count($cartLines) > 0) { ?>
            <form action="cart_update.php" method="POST" class="field">
                <input type="hidden" name="action" value="set_type">
                <label for="order_type">Order type</label>
                <select id="order_type" name="order_type" onchange="this.form.submit()">
                    <option value="delivery" <?php if ($orderType == 'delivery') echo 'selected'; ?>>Delivery</option>
                    <option value="pickup" <?php if ($orderType == 'pickup') echo 'selected'; ?>>Pickup</option>
                    <option value="dine-in" <?php if ($orderType == 'dine-in') echo 'selected'; ?>>Dine-in</option>
                </select>
            </form>

            <div class="cart-items">
                <?php foreach ($cartLines as $line) { ?>
                    <div class="cart-item">
                        <div class="cart-item-head">
                            <span><?= htmlspecialchars($line['name']) ?></span>
                            <strong>₱<?= number_format($line['total'], 2) ?></strong>
                        </div>
                        <small><?= htmlspecialchars($line['size']) ?><?php if ($line['addons'] !== '') { echo ' · ' . htmlspecialchars($line['addons']); } ?></small>
                        <div class="qty">
                            <form action="cart_update.php" method="POST">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="key" value="<?= $line['key'] ?>">
                                <input type="hidden" name="qty" value="<?= $line['qty'] - 1 ?>">
                                <button type="submit">-</button>
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
                <span><?php if ($orderType == 'delivery') { echo 'Delivery fee'; } else { echo 'Service fee'; } ?></span>
                <strong>₱<?= number_format($deliveryFee, 2) ?></strong>
            </div>

            <?php if ($orderType == 'delivery' && $subtotal < $settings['min_order_for_delivery']) { ?>
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
</div>


<?php include 'includes/footer.php'; ?>
