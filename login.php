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
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT user_id, email, password, role, status FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $error = 'This account is disabled.';
        } else {
            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            $fullName = $user['email'];
            if (isset($conn) && $conn) {
                $profileStmt = mysqli_prepare($conn, 'SELECT full_name FROM customer_profile WHERE user_id = ? LIMIT 1');
                mysqli_stmt_bind_param($profileStmt, 'i', $_SESSION['user_id']);
                mysqli_stmt_execute($profileStmt);
                $profileRes = mysqli_stmt_get_result($profileStmt);
                if ($profileRow = mysqli_fetch_assoc($profileRes)) {
                    $fullName = $profileRow['full_name'] ?: $user['email'];
                }
            }
            $_SESSION['user_name'] = $fullName;

            $redirectUrl = ($user['role'] === 'admin')
                ? '/takoyaki-shop-infoman/admin/flavors/index.php'
                : '/takoyaki-shop-infoman/menu.php';

            $_SESSION['message'] = 'Welcome back!';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $redirectUrl);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Takoyaki Shop</title>
    <link rel="stylesheet" href="/takoyaki-shop-infoman/includes/style.css">
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
                <div class="eyebrow">Welcome back</div>
                <h1>Sign in to your account</h1>
                <p>Enjoy fresh takoyaki, quick checkout, and personalized order tracking.</p>
            </div>

            <form method="POST" class="auth-form" novalidate>
                <?php if ($error) { echo "<div class='alert danger'>" . htmlspecialchars($error) . '</div>'; } ?>
                <?php include 'includes/alert.php'; ?>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com" required>
                </div>

                <div class="field password-wrap">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Enter your password" required>
                    <button type="button" class="toggle-password" data-target="password">Show</button>
                </div>

                <button type="submit" class="btn full">Sign In</button>
                <p class="auth-switch">Need an account? <a href="/takoyaki-shop-infoman/register.php">Create one</a></p>
            </form>
        </section>
    </main>
</div>

<script>
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            var target = document.getElementById(this.getAttribute('data-target'));
            var showing = target.type === 'text';
            target.type = showing ? 'password' : 'text';
            this.textContent = showing ? 'Show' : 'Hide';
        });
    });
</script>
</body>
</html>
