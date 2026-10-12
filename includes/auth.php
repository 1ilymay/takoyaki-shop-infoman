<?php
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

function redirect_to_login(string $message = 'Please sign in to continue.', string $type = 'warning'): void
{
    $_SESSION['message'] = $message;
    $_SESSION['msg_type'] = $type;
    header('Location: /takoyaki-shop-infoman/login.php');
    exit();
}

function require_login(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!is_logged_in()) {
        redirect_to_login();
    }
}

function require_customer(): void
{
    require_login();

    if (($_SESSION['user_role'] ?? '') !== 'customer') {
        $_SESSION['message'] = 'Customer access only.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: /takoyaki-shop-infoman/login.php');
        exit();
    }
}

function require_admin(): void
{
    require_login();

    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        $_SESSION['message'] = 'Admin access required.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: /takoyaki-shop-infoman/login.php');
        exit();
    }
}

function ensure_default_admin(): void
{
    global $conn;

    if (!isset($conn) || !$conn) {
        return;
    }

    $email = 'admin@takoyaki.com';
    $stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = mysqli_prepare($conn, 'INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, ?)');
        $role = 'admin';
        $status = 'active';
        mysqli_stmt_bind_param($insert, 'ssss', $email, $hash, $role, $status);
        mysqli_stmt_execute($insert);
    }
}
