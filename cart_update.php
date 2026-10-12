<?php
session_start();
include('includes/config.php');

// the cart is kept in the session, so guests can use it too
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

$action = $_POST['action'];

if ($action == "add") {
    $sizeId = (int)$_POST['size_id'];
    $qty = (int)$_POST['qty'];
    if ($qty < 1) {
        $qty = 1;
    }
    if ($qty > 99) {
        $qty = 99;
    }

    // make sure the size and its flavor are still available
    $sql = "SELECT fs.size_id FROM flavor_size fs
            INNER JOIN flavor f ON f.flavor_id = fs.flavor_id
            WHERE fs.size_id = $sizeId AND fs.is_available = 1 AND f.is_available = 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 0) {
        $_SESSION['message'] = "Sorry, that item is currently sold out.";
        $_SESSION['msg_type'] = "danger";
    } else {
        // selected add-ons
        $addons = array();
        if (isset($_POST['addons'])) {
            foreach ($_POST['addons'] as $addonId) {
                $addons[] = (int)$addonId;
            }
        }
        sort($addons);

        // same size + same add-ons = same line in the cart
        $key = $sizeId . "-" . implode("-", $addons);

        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['qty'] += $qty;
            if ($_SESSION['cart'][$key]['qty'] > 99) {
                $_SESSION['cart'][$key]['qty'] = 99;
            }
        } else {
            $_SESSION['cart'][$key] = array('size_id' => $sizeId, 'qty' => $qty, 'addons' => $addons);
        }
        $_SESSION['message'] = "Added to your cart.";
    }
}

if ($action == "update") {
    $key = $_POST['key'];
    $qty = (int)$_POST['qty'];
    if (isset($_SESSION['cart'][$key])) {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$key]);
        } else {
            if ($qty > 99) {
                $qty = 99;
            }
            $_SESSION['cart'][$key]['qty'] = $qty;
        }
    }
}

if ($action == "remove") {
    unset($_SESSION['cart'][$_POST['key']]);
}

// order type: delivery, pickup, or dine-in
if ($action == "set_type") {
    $type = $_POST['order_type'];
    if ($type == "delivery" || $type == "pickup" || $type == "dine-in") {
        $_SESSION['order_type'] = $type;
    }
}

header("Location: index.php#cartPanel");