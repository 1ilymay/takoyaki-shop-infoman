<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


$stmt = $pdo->prepare("SELECT * FROM flavors WHERE id = ?"); 
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo "Product not found.";
    exit();
}


$stmt_reviews = $pdo->prepare("
    r.id, r.rating, r.comment, r.created_at, r.user_id, u.name AS user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC
");
$stmt_reviews->execute([$product_id]);
$reviews = $stmt_reviews->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - Details</title>
    <link rel="stylesheet" href="includes/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container" style="padding: 20px;">
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <p><?php echo htmlspecialchars($product['description']); ?></p>
        
        <hr>

        <h2>Customer Reviews</h2>

    
        <?php if (isset($_SESSION['user_id'])): ?>
            <form action="store_review.php" method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                <label>Rating (1-5):</label>
                <select name="rating" required>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3">3 - Average</option>
                    <option value="2">2 - Poor</option>
                    <option value="1">1 - Terrible</option>
                </select>
                <br><br>
                <label>Your Review:</label><br>
                <textarea name="comment" rows="4" cols="50" required placeholder="Tell us about your experience with this flavor..."></textarea>
                <br>
                <button type="submit" style="margin-top: 10px;">Submit Review</button>
            </form>
        <?php else: ?>
            <p><a href="login.php">Log in</a> to write a review.</p>
        <?php endif; ?>


        <div class="reviews-list">
            <?php if (empty($reviews)): ?>
                <p>No reviews yet for this product.</p>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                    <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                        <strong><?php echo htmlspecialchars($rev['user_name']); ?></strong> 
                        <span>(Rating: <?php echo $rev['rating']; ?>/5)</span>
                        <p><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                        <small style="color: gray;"><?php echo $rev['created_at']; ?></small>

                        
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $rev['user_id']): ?>
                            <br>
                            <a href="edit_review.php?id=<?php echo $rev['id']; ?>">Edit Review</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>