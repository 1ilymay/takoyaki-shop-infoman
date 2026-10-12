<?php
session_start();
include('../../includes/config.php');
// TODO: check if admin is logged in

$name = trim($_POST['name']);
$description = trim($_POST['description']);
$available = (int)$_POST['is_available'];

// validation
if ($name == "") {
    $_SESSION['message'] = "Item name is required.";
    $_SESSION['msg_type'] = "danger";
    $_SESSION['old'] = $_POST;
    header("Location: create.php");
    exit();
}

// photo (optional)
$image = null;
if ($_FILES['image']['name'] != "") {
    $type = $_FILES['image']['type'];
    if ($type == "image/jpeg" || $type == "image/png" || $type == "image/webp") {
        if (!is_dir("../../uploads")) {
            mkdir("../../uploads");
        }
        $filename = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "../../uploads/" . $filename) or die("Couldn't copy");
        $image = "uploads/" . $filename;
    } else {
        $_SESSION['message'] = "Wrong file type. Please upload a JPG, PNG, or WEBP image.";
        $_SESSION['msg_type'] = "danger";
        $_SESSION['old'] = $_POST;
        header("Location: create.php");
        exit();
    }
}

$sql = "INSERT INTO flavor (name, description, image, is_available) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'sssi', $name, $description, $image, $available);
mysqli_stmt_execute($stmt);

$_SESSION['message'] = "Menu item added.";
header("Location: index.php");
exit();