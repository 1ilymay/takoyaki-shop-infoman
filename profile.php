<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

require_customer();

$page = 'account';
include 'includes/header.php';

$userData = [
    'email' => '',
    'full_name' => '',
    'contact_number' => '',
    'default_address' => '',
];

if (isset($conn) && $conn) {
    $stmt = mysqli_prepare($conn, 'SELECT u.email, cp.full_name, cp.contact_number, cp.default_address
        FROM users u
        LEFT JOIN customer_profile cp ON cp.user_id = u.user_id
        WHERE u.user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $userData = [
            'email' => $row['email'] ?? '',
            'full_name' => $row['full_name'] ?? '',
            'contact_number' => $row['contact_number'] ?? '',
            'default_address' => $row['default_address'] ?? '',
        ];
    }
}
?>

<div class="page-title">
    <div>
        <div class="eyebrow">My account</div>
        <h1>Profile</h1>
    </div>
</div>

<?php include('includes/alert.php'); ?>

<section class="panel narrow">
    <div class="field">
        <label>Name</label>
        <input type="text" value="<?= htmlspecialchars($userData['full_name'] ?: ($_SESSION['user_name'] ?? 'Customer')) ?>" readonly>
    </div>
    <div class="field">
        <label>Email</label>
        <input type="text" value="<?= htmlspecialchars($userData['email']) ?>" readonly>
    </div>
    <div class="field">
        <label>Contact number</label>
        <input type="text" value="<?= htmlspecialchars($userData['contact_number']) ?>" readonly>
    </div>
    <div class="field">
        <label>Default address</label>
        <textarea readonly><?= htmlspecialchars($userData['default_address']) ?></textarea>
    </div>
</section>

<?php include('includes/footer.php'); ?>
