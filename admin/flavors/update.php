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

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$available = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 1;

if ($id <= 0) {
    $_SESSION['message'] = "Invalid menu item.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

$errors = array();

if ($name === "") {
    $errors[] = "Item name is required.";
} else {
    if (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = "Item name must be 2 to 100 characters.";
    }
    if (!preg_match("/^[a-zA-Z0-9 &'-]+$/", $name)) {
        $errors[] = "Item name can only have letters, numbers, spaces, & ' and -.";
    }
}

if (strlen($description) > 255) {
    $errors[] = "Description must be 255 characters or less.";
}

if ($available !== 0 && $available !== 1) {
    $errors[] = "Availability must be Available or Sold Out.";
}

if (count($errors) > 0) {
    $_SESSION['message'] = implode(" ", $errors);
    $_SESSION['msg_type'] = "danger";
    header("Location: edit.php?id=$id");
    exit();
}

$result = mysqli_query($conn, "SELECT image FROM flavor WHERE flavor_id = $id");
$old = mysqli_fetch_assoc($result);

if (!$old) {
    $_SESSION['message'] = "Menu item not found.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

$image = $old['image'];

if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['image'];
    $type = $file['type'];
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Image upload failed. Please try again.";
        $_SESSION['msg_type'] = "danger";
        header("Location: edit.php?id=$id");
        exit();
    }

    if (!in_array($type, $allowed, true)) {
        $_SESSION['message'] = "Wrong file type. Please upload a JPG, PNG, or WEBP image.";
        $_SESSION['msg_type'] = "danger";
        header("Location: edit.php?id=$id");
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
        header("Location: edit.php?id=$id");
        exit();
    }

    if ($old['image'] && file_exists("../../" . $old['image'])) {
        unlink("../../" . $old['image']);
    }

    $image = "uploads/" . $filename;
}

$sql = "UPDATE flavor SET name = ?, description = ?, image = ?, is_available = ? WHERE flavor_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'sssii', $name, $description, $image, $available, $id);
mysqli_stmt_execute($stmt);

$_SESSION['message'] = "Menu item updated.";
$_SESSION['msg_type'] = "success";
header("Location: index.php");
exit();