<?php
include 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && file_exists('db_connect.php')) {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT password FROM staff WHERE staff_ID = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($current_password, $user['password'])) {
            $error = "✕ Current password is incorrect.";
        }
        elseif ($new_password !== $confirm_password) {
            $error = "✕ New passwords do not match.";
        }
        elseif (strlen($new_password) < 6) {
            $error = "✕ New password must be at least 6 characters long.";
        }
        else {
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            $update_stmt = $conn->prepare("UPDATE staff SET password = ? WHERE staff_ID = ?");
            $update_stmt->bind_param("ss", $new_hash, $user_id);
            
            if ($update_stmt->execute()) {
                $success = "✓ Password updated successfully!";
            } else {
                $error = "✕ Database update failure. Try again.";
            }
            $update_stmt->close();
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = "[!] Error processing update: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | CAREMEDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="login-wrapper">
    <div class="brand-header">
        <img src="image/caremeds_logo.png" alt="CareMeds Logo" style="height: 120px; width: auto; mix-blend-mode: multiply; margin-bottom: 10px;">
        <p style="font-size: 13px; color: #555; margin-top: 5px;">Account Security Settings</p>
    </div>

    <?php 
    if($error) echo "<div class='error-msg'>$error</div>"; 
    if($success) echo "<div class='success-msg' style='background-color: #eef7f2; color: #2d6a4f; border-left: 4px solid #2d6a4f; padding: 12px 16px; border-radius: 6px; font-size: 14px; font-weight: 500; margin-bottom: 20px;'>$success</div>";
    ?>

    <form action="change_password.php" method="POST">
        <label>Current Password</label>
        <input type="password" name="current_password" id="currentPass" placeholder="Enter current password" required>

        <label>New Password</label>
        <input type="password" name="new_password" id="newPass" placeholder="Enter new password" required>

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" id="confirmPass" placeholder="Repeat new password" required>

        <div style="margin: 10px 0 20px 0; display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569;">
            <input type="checkbox" id="togglePasswordsStandard" style="width: auto; margin: 0; cursor: pointer;">
            <label for="togglePasswordsStandard" style="margin: 0; cursor: pointer; font-weight: 500;">Show Passwords</label>
        </div>

        <button type="submit" class="btn-login" style="background-color: #2a9d8f;">Save Security Updates</button>
    </form>

    <div class="register-text" style="margin-top: 20px;">
        <?php 
        $admin_roles = ['admin', 'administrator', 'management', 'administrative staff'];
        if (isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), $admin_roles)): 
        ?>
            <a href="dashboard.php">← Back to Dashboard</a>
        <?php else: ?>
            <a href="staff_portal.php">← Back to Portal</a>
        <?php endif; ?>
    </div>
</div>

<script>
    document.getElementById('togglePasswordsStandard').addEventListener('change', function() {
        const currentPass = document.getElementById('currentPass');
        const newPass = document.getElementById('newPass');
        const confirmPass = document.getElementById('confirmPass');
        
        if (this.checked) {
            currentPass.type = 'text';
            newPass.type = 'text';
            confirmPass.type = 'text';
        } else {
            currentPass.type = 'password';
            newPass.type = 'password';
            confirmPass.type = 'password';
        }
    });
</script>

</body>
</html>