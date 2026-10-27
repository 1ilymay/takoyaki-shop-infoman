<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$review_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ? AND user_id = ?");
$stmt->execute([$review_id, $user_id]);
$review = $stmt->fetch();

if (!$review) {
    echo "Review not found or unauthorized.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_comment = trim($_POST['comment']);
    $new_rating = intval($_POST['rating']);

   
    $filtered_comment = filterBadWords($new_comment);

   
    $update_stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?");
    $update_stmt->execute([$new_rating, $filtered_comment, $review_id]);

    header("Location: product_detail.php?id=" . $review['product_id']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Review</title>
    <link rel="stylesheet" href="includes/style.css">
</head>
<body style="padding: 20px;">
    <h2>Edit Your Review</h2>
    <form method="POST">
        <label>Rating:</label>
        <select name="rating">
            <option value="5" <?php if($review['rating'] == 5) echo 'selected'; ?>>5</option>
            <option value="4" <?php if($review['rating'] == 4) echo 'selected'; ?>>4</option>
            <option value="3" <?php if($review['rating'] == 3) echo 'selected'; ?>>3</option>
            <option value="2" <?php if($review['rating'] == 2) echo 'selected'; ?>>2</option>
            <option value="1" <?php if($review['rating'] == 1) echo 'selected'; ?>>1</option>
        </select>
        <br><br>
        <label>Comment:</label><br>
        <textarea name="comment" rows="4" cols="50" required><?php echo htmlspecialchars($review['comment']); ?></textarea>
        <br>
        <button type="submit" style="margin-top: 10px;">Update Review</button>
    </form>
</body>
</html>