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
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error = "✕ Passwords do not match. Please try again.";
        } 
        elseif (strlen($new_password) < 6) {
            $error = "✕ Password must be at least 6 characters long.";
        } 
        else {
            $password_hashed = password_hash($new_password, PASSWORD_BCRYPT);
          
            $stmt = $conn->prepare("UPDATE staff SET password = ?, is_first_login = 0 WHERE staff_ID = ?");
            $stmt->bind_param("ss", $password_hashed, $user_id);

            if ($stmt->execute()) {
                $success = "✓ Password updated successfully! Redirecting...";
                
                $admin_roles = ['admin', 'administrator', 'management', 'administrative staff'];
                if (isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), $admin_roles)) {
                    $target_dashboard = 'dashboard.php';
                } else {
                    $target_dashboard = 'staff_portal.php';
                }
            
                header("refresh:1.5;url=" . $target_dashboard);
            } else {
                $error = "✕ Failed to update password. Please contact system administrator.";
            }
        }
    } catch (Exception $e) {
        $error = "[!] Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Your Password | CAREMEDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="login-wrapper">
    <div class="brand-header">
        <img src="image/caremeds_logo.png" alt="CareMeds Logo" style="height: 120px; width: auto; mix-blend-mode: multiply; margin-bottom: 10px;">
        <p style="font-size: 13px; color: #e63946; font-weight: 600; margin-top: 5px;">First-Time Password Reset</p>
        <p style="font-size: 12px; color: #666; margin-top: 2px; padding: 0 10px;">For system security, you must replace your temporary account password before proceeding.</p>
    </div>

    <?php 
    if($error) echo "<div class='error-msg'>$error</div>"; 
    if($success) echo "<div class='success-msg' style='background-color: #eef7f2; color: #2d6a4f; border-left: 4px solid #2d6a4f; padding: 12px 16px; border-radius: 6px; font-size: 14px; font-weight: 500; margin-bottom: 20px;'>$success</div>";
    ?>

    <form action="change_password_first.php" method="POST">
        <label>New Permanent Password</label>
        <input type="password" name="new_password" id="newPassword" placeholder="Enter new password" required>

        <label>Confirm Permanent Password</label>
        <input type="password" name="confirm_password" id="confirmPassword" placeholder="Repeat new password" required>

        <div style="margin: 10px 0 20px 0; display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569;">
            <input type="checkbox" id="togglePasswordsFirst" style="width: auto; margin: 0; cursor: pointer;">
            <label for="togglePasswordsFirst" style="margin: 0; cursor: pointer; font-weight: 500;">Show Passwords</label>
        </div>

        <button type="submit" class="btn-login" style="background-color: #0077b6;">Update Password & Continue</button>
    </form>
</div>

<script>
    document.getElementById('togglePasswordsFirst').addEventListener('change', function() {
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        if (this.checked) {
            newPassword.type = 'text';
            confirmPassword.type = 'text';
        } else {
            newPassword.type = 'password';
            confirmPassword.type = 'password';
        }
    });
</script>

</body>
</html>