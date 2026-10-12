<?php
session_start();
include('../../includes/config.php');
// TODO: check if admin is logged in

$id = (int)$_POST['id'];

// do not delete if the item is already in an order
$sql = "SELECT COUNT(*) AS total FROM order_item oi
        INNER JOIN flavor_size fs ON oi.size_id = fs.size_id
        WHERE fs.flavor_id = $id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if ($row['total'] > 0) {
    $_SESSION['message'] = "This item is already in existing orders and cannot be deleted. Mark it as Sold Out instead.";
    $_SESSION['msg_type'] = "warning";
    header("Location: index.php");
    exit();
}

// delete the photo file
$result = mysqli_query($conn, "SELECT image FROM flavor WHERE flavor_id = $id");
$flavor = mysqli_fetch_assoc($result);
if ($flavor && $flavor['image'] && file_exists("../../" . $flavor['image'])) {
    unlink("../../" . $flavor['image']);
}

// delete the child rows first, then the flavor
mysqli_query($conn, "DELETE FROM size_ingredient_usage WHERE size_id IN (SELECT size_id FROM flavor_size WHERE flavor_id = $id)");
mysqli_query($conn, "DELETE FROM flavor_size WHERE flavor_id = $id");
mysqli_query($conn, "DELETE FROM flavor WHERE flavor_id = $id");

$_SESSION['message'] = "Menu item deleted.";
header("Location: index.php");
exit();