<?php

if (!file_exists('db_connect.php')) {
    $error = "[!] File 'db_connect.php' not found. Please create the connection file.";
} else {
    include 'db_connect.php';
}

session_start();
$error = isset($error) ? $error : "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && file_exists('db_connect.php')) {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'forgot_password') {
        $username = trim($_POST['username'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($username === '' || $new_password === '' || $confirm_password === '') {
            $error = "✕ Please complete all password reset fields.";
        } elseif (strlen($new_password) < 6) {
            $error = "✕ New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error = "✕ New passwords do not match.";
        } else {
            $stmt = $conn->prepare("SELECT staff_ID FROM staff WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($user) {
                    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $update_stmt = $conn->prepare("UPDATE staff SET password = ?, is_first_login = 0 WHERE username = ?");
                    $update_stmt->bind_param("ss", $new_hash, $username);
                    if ($update_stmt->execute()) {
                        $success_message = "✓ Password reset successfully. You can now sign in with your new password.";
                    } else {
                        $error = "✕ Password reset failed. Please try again.";
                    }
                    $update_stmt->close();
                } else {
                    $error = "✕ Username not found in the system.";
                }
            } else {
                $error = "✕ Database error while resetting password.";
            }
        }
    } else {
    try {
        $username = $_POST['username'];
        $password_input = isset($_POST['password']) ? $_POST['password'] : "";

        // 1. Prepare select query
        $stmt = $conn->prepare("SELECT * FROM staff WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close(); // Close statement early to free resources

            if ($user) {
                if (password_verify($password_input, $user['password'])) {
                    $_SESSION['user_id'] = $user['staff_ID'];
                    $_SESSION['role'] = $user['role']; 
                    $_SESSION['name'] = $user['staff_name'];

                    // 2. Prepare update query with safety check
                    $update_login_stmt = $conn->prepare("UPDATE staff SET last_login = NOW() WHERE staff_ID = ?");
                    if ($update_login_stmt) {
                        $update_login_stmt->bind_param("s", $user['staff_ID']);
                        $update_login_stmt->execute();
                        $update_login_stmt->close();
                    } else {
                        // If it fails, log the error but don't crash the entire login flow
                        error_log("Database Error: Unable to update last_login. " . $conn->error);
                    }
                    
                    if (isset($user['is_first_login']) && $user['is_first_login'] == 1) {
                        header("Location: change_password_first.php");
                        exit();
                    } else {
                        $admin_roles = ['admin', 'administrator', 'management', 'administrative staff'];
                        if (in_array(strtolower($_SESSION['role']), $admin_roles)) {
                            header("Location: dashboard.php");
                        } else {
                            header("Location: nurse_dashboard.php");
                        }
                        exit();
                    }
                } else {
                    $error = "✕ Incorrect password!";
                }
            } else { 
                $error = "✕ Username not found in the system!"; 
            }
        } else {
            $error = "✕ Database error: Failed to prepare select statement. " . $conn->error;
        }
    } catch (Exception $e) {
        $error = "[!] Login System Error: " . $e->getMessage();
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CAREMEDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="login-wrapper">
    <div class="brand-header">
        <img src="image/caremeds_logo.png" alt="CareMeds Logo" style="height: 120px; width: auto; mix-blend-mode: multiply; margin-bottom: 10px;">
        <p style="font-size: 13px; color: #555; margin-top: 5px;">Internal Access Only</p>
    </div>

    <?php if($error) echo "<div class='error-msg'>$error</div>"; ?>
    <?php if($success_message) echo "<div class='success-msg' style='background-color: #eef7f2; color: #2d6a4f; border-left: 4px solid #2d6a4f; padding: 12px 16px; border-radius: 6px; font-size: 14px; font-weight: 500; margin-bottom: 20px;'>$success_message</div>"; ?>

    <form action="login.php" method="POST">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter your username" required>

        <label>Password</label>
        <input type="password" name="password" id="loginPassword" placeholder="••••••••" required>

        <div style="margin: 10px 0 20px 0; display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569;">
            <input type="checkbox" id="togglePassword" style="width: auto; margin: 0; cursor: pointer;">
            <label for="togglePassword" style="margin: 0; cursor: pointer; font-weight: 500;">Show Password</label>
        </div>

        <button type="submit" class="btn-login">Sign In to Portal</button>
    </form>

    <div style="margin-top: 14px;">
        <a href="#" id="showForgotPassword" style="font-size: 13px; color: #2563eb; text-decoration: none; font-weight: 600;">Forgot password?</a>
    </div>

    <div id="forgotPasswordForm" style="display:none; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
        <form action="login.php" method="POST">
            <input type="hidden" name="action" value="forgot_password">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter your username" required>

            <label>New Password</label>
            <input type="password" name="new_password" id="forgotNewPassword" placeholder="Enter new password" required>

            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" id="forgotConfirmPassword" placeholder="Repeat new password" required>

            <div style="margin: 10px 0 16px 0; display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569;">
                <input type="checkbox" id="toggleForgotPassword" style="width: auto; margin: 0; cursor: pointer;">
                <label for="toggleForgotPassword" style="margin: 0; cursor: pointer; font-weight: 500;">Show Passwords</label>
            </div>

            <button type="submit" class="btn-login" style="background-color: #2563eb;">Reset Password</button>
        </form>
    </div>

    <div class="register-text">
        Need system access? <a href="register.php">Register Staff Account</a>
    </div>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('change', function() {
        const passwordInput = document.getElementById('loginPassword');
        if (this.checked) {
            passwordInput.type = 'text';
        } else {
            passwordInput.type = 'password';
        }
    });

    document.getElementById('showForgotPassword').addEventListener('click', function(e) {
        e.preventDefault();
        const form = document.getElementById('forgotPasswordForm');
        form.style.display = form.style.display === 'block' ? 'none' : 'block';
    });

    document.getElementById('toggleForgotPassword').addEventListener('change', function() {
        const newPass = document.getElementById('forgotNewPassword');
        const confirmPass = document.getElementById('forgotConfirmPassword');

        if (this.checked) {
            newPass.type = 'text';
            confirmPass.type = 'text';
        } else {
            newPass.type = 'password';
            confirmPass.type = 'password';
        }
    });
</script>

</body>
</html>
