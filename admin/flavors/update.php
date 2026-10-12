<?php
/** @var mysqli $conn */
session_start();
include('../../includes/config.php');
// TODO: check if admin is logged in

$id = (int)$_POST['id'];
$name = trim($_POST['name']);
$description = trim($_POST['description']);
$available = (int)$_POST['is_available'];

if ($name == "") {
    $_SESSION['message'] = "Item name is required.";
    $_SESSION['msg_type'] = "danger";
    header("Location: edit.php?id=$id");
    exit();
}

// get the current photo
$result = mysqli_query($conn, "SELECT image FROM flavor WHERE flavor_id = $id");
$old = mysqli_fetch_assoc($result);
$image = $old['image'];

// new photo (optional)
if ($_FILES['image']['name'] != "") {
    $type = $_FILES['image']['type'];
    if ($type == "image/jpeg" || $type == "image/png" || $type == "image/webp") {
        if (!is_dir("../../uploads")) {
            mkdir("../../uploads");
        }
        $filename = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "../../uploads/" . $filename) or die("Couldn't copy");

        // remove the old photo file
        if ($old['image'] && file_exists("../../" . $old['image'])) {
            unlink("../../" . $old['image']);
        }
        $image = "uploads/" . $filename;
    } else {
        $_SESSION['message'] = "Wrong file type. Please upload a JPG, PNG, or WEBP image.";
        $_SESSION['msg_type'] = "danger";
        header("Location: edit.php?id=$id");
        exit();
    }
}

$sql = "UPDATE flavor SET name = ?, description = ?, image = ?, is_available = ? WHERE flavor_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'sssii', $name, $description, $image, $available, $id);
mysqli_stmt_execute($stmt);

$_SESSION['message'] = "Menu item updated.";
header("Location: index.php");
exit();