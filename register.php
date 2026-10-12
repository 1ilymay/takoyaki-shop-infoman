<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';

ensure_default_admin();

if (is_logged_in()) {
    $redirect = ($_SESSION['user_role'] ?? 'customer') === 'admin'
        ? '/takoyaki-shop-infoman/admin/flavors/index.php'
        : '/takoyaki-shop-infoman/menu.php';
    header('Location: ' . $redirect);
    exit();
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Please complete all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $error = 'An account already exists with that email.';
        } else {
            mysqli_begin_transaction($conn);
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insertUser = mysqli_prepare($conn, 'INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, ?)');
            $role = 'customer';
            $status = 'active';
            mysqli_stmt_bind_param($insertUser, 'ssss', $email, $hashed, $role, $status);
            if (!mysqli_stmt_execute($insertUser)) {
                mysqli_rollback($conn);
                $error = 'Unable to create your account right now.';
            } else {
                $userId = mysqli_insert_id($conn);
                $insertProfile = mysqli_prepare($conn, 'INSERT INTO customer_profile (user_id, full_name, contact_number, default_address) VALUES (?, ?, ?, ?)');
                $phone = trim($_POST['contact_number'] ?? '');
                $address = trim($_POST['default_address'] ?? '');
                mysqli_stmt_bind_param($insertProfile, 'isss', $userId, $name, $phone, $address);
                if (!mysqli_stmt_execute($insertProfile)) {
                    mysqli_rollback($conn);
                    $error = 'Unable to save your profile details.';
                } else {
                    mysqli_commit($conn);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_role'] = 'customer';
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['message'] = 'Your account is ready. Welcome to Takoyaki Shop!';
                    $_SESSION['msg_type'] = 'success';
                    header('Location: /takoyaki-shop-infoman/menu.php');
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Takoyaki Shop</title>
    <link rel="stylesheet" href="<?= $base ?? '/takoyaki-shop-infoman' ?>/includes/style.css">
</head>
<body>
<div class="topline"></div>
<div class="landing-shell auth-shell">
    <header class="landing-header compact-auth">
        <div class="brand">
            <div class="brand-mark">た</div>
            <div><strong>Takoyaki Shop</strong><small>Fresh from our griddle</small></div>
        </div>
        <div class="landing-actions">
            <a class="btn ghost" href="/takoyaki-shop-infoman/">Back to welcome</a>
        </div>
    </header>

    <main class="auth-panel-wrap">
        <section class="auth-panel">
            <div class="auth-copy">
                <div class="eyebrow">Join us</div>
                <h1>Create your account</h1>
                <p>Sign up for fresh takoyaki favorites, quick ordering, and saved favorites.</p>
            </div>

            <form method="POST" class="auth-form" novalidate>
                <?php if ($error) { echo "<div class='alert danger'>" . htmlspecialchars($error) . '</div>'; } ?>

                <div class="field">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" type="text" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="Your name" required>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com" required>
                </div>

                <div class="field">
                    <label for="contact_number">Contact number</label>
                    <input id="contact_number" name="contact_number" type="tel" value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>" placeholder="09xx xxx xxxx">
                </div>

                <div class="field">
                    <label for="default_address">Default address</label>
                    <textarea id="default_address" name="default_address" placeholder="Your delivery address"><?= htmlspecialchars($_POST['default_address'] ?? '') ?></textarea>
                </div>

                <div class="field password-wrap">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Create a password" required>
                    <button type="button" class="toggle-password" data-target="password">Show</button>
                </div>

                <div class="field password-wrap">
                    <label for="confirm_password">Confirm password</label>
                    <input id="confirm_password" name="confirm_password" type="password" placeholder="Repeat your password" required>
                    <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
                </div>

                <button type="submit" class="btn full">Create Account</button>
                <p class="auth-switch">Already have an account? <a href="/takoyaki-shop-infoman/login.php">Sign in</a></p>
            </form>
        </section>
    </main>
</div>

<script>
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var field = document.getElementById(targetId);
            var showing = field.type === 'text';
            field.type = showing ? 'password' : 'text';
            this.textContent = showing ? 'Show' : 'Hide';
        });
    });
</script>
</body>
</html>
