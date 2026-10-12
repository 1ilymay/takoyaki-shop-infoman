<?php
/** @var mysqli $conn */
session_start();
include('../../includes/config.php');
include('../../includes/auth.php');

require_admin();

if (!isset($conn) || !$conn) {
    $_SESSION['message'] = "Database connection failed. Please try again later.";
    $_SESSION['msg_type'] = "danger";
    header("Location: create.php");
    exit();
}

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$available = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 1;

// validation
if ($name === "") {
    $_SESSION['message'] = "Item name is required.";
    $_SESSION['msg_type'] = "danger";
    $_SESSION['old'] = $_POST;
    header("Location: create.php");
    exit();
}

// photo (optional)
$image = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['image'];
    $type = $file['type'];
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Image upload failed. Please try again.";
        $_SESSION['msg_type'] = "danger";
        $_SESSION['old'] = $_POST;
        header("Location: create.php");
        exit();
    }

    if (!in_array($type, $allowed, true)) {
        $_SESSION['message'] = "Wrong file type. Please upload a JPG, PNG, or WEBP image.";
        $_SESSION['msg_type'] = "danger";
        $_SESSION['old'] = $_POST;
        header("Location: create.php");
        exit();
    }

    if (!is_dir("../../uploads")) {
        mkdir("../../uploads", 0777, true);
    }

    $filename = time() . "_" . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($file['name']));
    $target = "../../uploads/" . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        $_SESSION['message'] = "Image upload failed. Please try again.";
        $_SESSION['msg_type'] = "danger";
        $_SESSION['old'] = $_POST;
        header("Location: create.php");
        exit();
    }

    $image = "uploads/" . $filename;
}

$sql = "INSERT INTO flavor (name, description, image, is_available) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'sssi', $name, $description, $image, $available);
mysqli_stmt_execute($stmt);

$_SESSION['message'] = "Menu item added.";
$_SESSION['msg_type'] = "success";
header("Location: index.php");
exit();