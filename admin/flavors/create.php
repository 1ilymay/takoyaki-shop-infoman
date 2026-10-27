<?php
session_start();
include('../../includes/config.php');
include('../../includes/auth.php');

require_admin();

$page = "admin";
$adminPage = "menu";
include('../../includes/header.php');
include('../../includes/adminHeader.php');

// values typed earlier (if store.php sent us back because of an error)
$name = "";
$description = "";
$available = 1;
if (isset($_SESSION['old'])) {
    $name = $_SESSION['old']['name'];
    $description = $_SESSION['old']['description'];
    $available = $_SESSION['old']['is_available'];
    unset($_SESSION['old']);
}
?>

<div class="page-title">
    <div>
        <div class="eyebrow">Store management</div>
        <h1>Add a menu item</h1>
    </div>
</div>

<?php include('../../includes/alert.php'); ?>

<section class="panel narrow">
    <form action="store.php" method="POST" enctype="multipart/form-data" novalidate>
        <div class="field">
            <label for="name">Item name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>">
        </div>
        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= htmlspecialchars($description) ?></textarea>
        </div>
        <div class="form-grid">
            <div class="field">
                <label for="image">Photo (JPG, PNG, WEBP)</label>
                <input type="file" id="image" name="image">
            </div>
            <div class="field">
                <label for="is_available">Availability</label>
                <select id="is_available" name="is_available">
                    <option value="1" <?php if ($available == 1) echo "selected"; ?>>Available</option>
                    <option value="0" <?php if ($available == 0) echo "selected"; ?>>Sold Out</option>
                </select>
            </div>
        </div>
        <p class="tiny muted">Sizes and prices are added after saving.</p>
        <button type="submit">Add menu item</button>
        <a class="btn secondary" href="index.php">Cancel</a>
    </form>
</section>

<?php include('../../includes/footer.php'); ?>