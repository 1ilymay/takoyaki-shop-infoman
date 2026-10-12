<?php
/** @var mysqli $conn */
session_start();
include('../../includes/config.php');
include('../../includes/auth.php');

require_admin();

if (!isset($conn) || !$conn) {
    $_SESSION['message'] = "Database connection failed. Please try again later.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

// switch between Available and Sold Out
$id = (int)$_POST['id'];
mysqli_query($conn, "UPDATE flavor SET is_available = 1 - is_available WHERE flavor_id = $id");

$_SESSION['message'] = "Availability updated.";
header("Location: index.php");
exit();