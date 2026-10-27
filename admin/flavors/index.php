<?php
/** @var mysqli $conn */
session_start();
include('../../includes/config.php');
include('../../includes/auth.php');

require_admin();

if (!isset($conn) || !$conn) {
    $_SESSION['message'] = "Database connection failed. Please try again later.";
    $_SESSION['msg_type'] = "danger";
}

$page = "admin";
$adminPage = "menu";
include('../../includes/header.php');
include('../../includes/adminHeader.php');

$result = false;
if (isset($conn) && $conn) {
    $sql = "SELECT * FROM flavor ORDER BY name";
    $result = mysqli_query($conn, $sql);
}
?>

<div class="page-title">
    <div>
        <div class="eyebrow">Store management</div>
        <h1>Menu items</h1>
        <p>Add, edit, and set availability of your takoyaki flavors.</p>
    </div>
    <a class="btn" href="create.php">+ Add menu item</a>
</div>

<?php include('../../includes/alert.php'); ?>

<section class="panel">
    <h2>Menu catalog</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Sizes / prices</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $id = $row['flavor_id'];

                        // get the sizes of this flavor
                        $sizes = mysqli_query($conn, "SELECT size_label, price FROM flavor_size WHERE flavor_id = $id ORDER BY price");
                    ?>
                        <tr>
                            <td>
                                <div class="item-cell">
                                    <span class="thumb">
                                        <?php if ($row['image']) { ?>
                                            <img src="<?= $base . '/' . $row['image'] ?>" alt="">
                                        <?php } else { ?>
                                        <img src="<?= $base ?>/images/takoyaki_logo.jpg" alt="">
                                        <?php } ?>
                                    </span>
                                    <div>
                                        <strong><?= htmlspecialchars($row['name']) ?></strong>
                                        <div class="muted"><?= htmlspecialchars($row['description']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                if ($sizes && mysqli_num_rows($sizes) == 0) {
                                    echo "<span class='muted'>No sizes yet</span>";
                                }
                                while ($s = mysqli_fetch_assoc($sizes)) {
                                    echo $s['size_label'] . ": ₱" . number_format($s['price'], 2) . "<br>";
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($row['is_available'] == 1) { ?>
                                    <span class="status Available">Available</span>
                                <?php } else { ?>
                                    <span class="status SoldOut">Sold Out</span>
                                <?php } ?>
                            </td>
                            <td>
                                <div class="actions">

                                    <a class="btn small secondary" href="sizes.php?id=<?= $id ?>">Sizes</a>
                                    <a class="btn small secondary" href="edit.php?id=<?= $id ?>">Edit</a>

                                    <form action="toggle.php" method="POST" class="inline">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <button type="submit" class="small ghost">
                                            <?php if ($row['is_available'] == 1) { echo "Mark sold out"; } else { echo "Make available"; } ?>
                                        </button>
                                    </form>

                                    <form action="delete.php" method="POST" class="inline" onsubmit="return confirm('Delete this menu item?');">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <button type="submit" class="small danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="4" class="muted">Database connection failed. Please try again later.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<?php include('../../includes/footer.php'); ?>