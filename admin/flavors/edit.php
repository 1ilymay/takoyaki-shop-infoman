<?php
/** @var mysqli $conn */
session_start();
include('../../includes/config.php');
// TODO: check if admin is logged in

$id = (int)$_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM flavor WHERE flavor_id = $id");
$flavor = mysqli_fetch_assoc($result);

// if the id does not exist, go back to the list
if (!$flavor) {
    $_SESSION['message'] = "Menu item not found.";
    $_SESSION['msg_type'] = "danger";
    header("Location: index.php");
    exit();
}

$page = "admin";
$adminPage = "menu";
include('../../includes/header.php');
include('../../includes/adminHeader.php');
?>

<div class="page-title">
    <div>
        <div class="eyebrow">Store management</div>
        <h1>Edit menu item</h1>
    </div>
</div>

<?php include('../../includes/alert.php'); ?>

<section class="panel narrow">
    <form action="update.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="field">
            <label for="name">Item name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($flavor['name']) ?>">
        </div>
        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= htmlspecialchars($flavor['description']) ?></textarea>
        </div>
        <div class="field">
            <label>Current photo</label>
            <span class="thumb">
                <?php if ($flavor['image']) { ?>
                    <img src="<?= $base . '/' . $flavor['image'] ?>" alt="">
                <?php } else { ?>
                <img src="<?= $base ?>/images/takoyaki_logo.jpg" alt="">
                <?php } ?>
            </span>
        </div>
        <div class="form-grid">
            <div class="field">
                <label for="image">Replace photo (optional)</label>
                <input type="file" id="image" name="image">
            </div>
            <div class="field">
                <label for="is_available">Availability</label>
                <select id="is_available" name="is_available">
                    <option value="1" <?php if ($flavor['is_available'] == 1) echo "selected"; ?>>Available</option>
                    <option value="0" <?php if ($flavor['is_available'] == 0) echo "selected"; ?>>Sold Out</option>
                </select>
            </div>
        </div>
        <button type="submit">Save changes</button>
        <a class="btn secondary" href="index.php">Cancel</a>
    </form>
</section>

<?php include('../../includes/footer.php'); ?>