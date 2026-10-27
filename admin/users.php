<?php
require_once '../includes/config.php';

// Handle Deactivate / Activate
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'deactivate') {
        $stmt = mysqli_prepare($conn, "UPDATE users SET status = 'inactive' WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
    } elseif ($action === 'activate') {
        $stmt = mysqli_prepare($conn, "UPDATE users SET status = 'active' WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
    }
    header("Location: users.php");
    exit();
}

// Handle Role Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = $_POST['role'];
    
    $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
    mysqli_stmt_execute($stmt);
    header("Location: users.php");
    exit();
}

// Kunin ang mga user mula sa database
$result = mysqli_query($conn, "SELECT user_id, email, role, status FROM users");
if (!$result) {
    die("Database Query Error: " . mysqli_error($conn));
}

// I-set ang active page para lumitaw ang tamang design/active tab sa admin header
$adminPage = 'users';
include '../includes/header.php';
include '../includes/adminHeader.php';
?>

<div style="max-width: 1200px; margin: 30px auto; padding: 0 20px; font-family: Arial, sans-serif;">
    <div style="margin-bottom: 25px;">
        <h2 style="font-size: 26px; color: #222; margin-bottom: 5px; font-weight: bold;">User Management</h2>
        <p style="color: #666; font-size: 14px;">Add, edit, and manage user accounts and roles.</p>
    </div>

    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 25px; border: 1px solid #eaeaea;">
        <table width="100%" style="border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #f2f2f2; color: #555; font-size: 12px; letter-spacing: 0.5px;">
                    <th style="padding: 12px 15px;">ID</th>
                    <th style="padding: 12px 15px;">EMAIL</th>
                    <th style="padding: 12px 15px;">ROLE</th>
                    <th style="padding: 12px 15px;">STATUS</th>
                    <th style="padding: 12px 15px;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = mysqli_fetch_assoc($result)): ?>
                <tr style="border-bottom: 1px solid #f8f8f8; font-size: 14px;">
                    <td style="padding: 15px; font-weight: bold; color: #333;"><?php echo $u['user_id']; ?></td>
                    <td style="padding: 15px; color: #444;"><?php echo htmlspecialchars($u['email']); ?></td>
                    
                    <td style="padding: 15px;">
                        <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                            <select name="role" style="padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; background: #fff; font-size: 13px;">
                                <option value="customer" <?php if(($u['role'] ?? '') == 'customer') echo 'selected'; ?>>Customer</option>
                                <option value="admin" <?php if(($u['role'] ?? '') == 'admin') echo 'selected'; ?>>Admin</option>
                            </select>
                            <button type="submit" name="update_role" style="padding: 6px 12px; background: #337ab7; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: bold;">Update</button>
                        </form>
                    </td>

                    <td style="padding: 15px;">
                        <?php $status = $u['status'] ?? 'active'; ?>
                        <span style="background: <?php echo ($status == 'active') ? '#e6f4ea' : '#fce8e6'; ?>; color: <?php echo ($status == 'active') ? '#137333' : '#c5221f'; ?>; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </td>

                    <td style="padding: 15px;">
                        <?php if ($status === 'active'): ?>
                            <a href="users.php?action=deactivate&id=<?php echo $u['user_id']; ?>" style="background: #a94442; color: white; padding: 6px 14px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block;" onclick="return confirm('Sigurado ka bang gusto mong i-deactivate ang user na ito?');">Deactivate</a>
                        <?php else: ?>
                            <a href="users.php?action=activate&id=<?php echo $u['user_id']; ?>" style="background: #3c763d; color: white; padding: 6px 14px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block;">Activate</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>