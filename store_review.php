<?php
require_once 'includes/config.php';
require_once 'includes/auth.php'; // Siguraduhing naka-login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    $comment = trim($_POST['comment']);
    $rating = intval($_POST['rating']);


    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'Completed'
    ");
    $stmt->execute([$user_id, $product_id]);
    $has_availed = $stmt->fetchColumn();

    if (!$has_availed) {
        $_SESSION['error'] = "You can only review products/services you have successfully availed.";
        header("Location: product_detail.php?id=" . $product_id);
        exit();
    }


    $filtered_comment = filterBadWords($comment);


    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$product_id, $user_id, $rating, $filtered_comment]);

    $_SESSION['success'] = "Review submitted successfully!";
    header("Location: product_detail.php?id=" . $product_id);
    exit();
}