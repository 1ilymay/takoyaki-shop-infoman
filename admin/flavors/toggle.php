<?php
session_start();
include('../../includes/config.php');
// TODO: check if admin is logged in

// switch between Available and Sold Out
$id = (int)$_POST['id'];
mysqli_query($conn, "UPDATE flavor SET is_available = 1 - is_available WHERE flavor_id = $id");

$_SESSION['message'] = "Availability updated.";
header("Location: index.php");
exit();