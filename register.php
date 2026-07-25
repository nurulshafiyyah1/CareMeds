<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!file_exists('db_connect.php')) {
    $error = "[!] File 'db_connect.php' not found. Please create the connection file.";
} else {
    include 'db_connect.php';
}

$success = "";
$error = isset($error) ? $error : "";
$redirect = false; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && file_exists('db_connect.php')) {
    try {
        $staff_id = $_POST['staff_id'];
        $username = $_POST['username'];
        $staff_name = $_POST['staff_name'];
        $role = $_POST['role']; 
        
        $password_raw = "CARE" . rand(1000, 9999);

        $check_stmt = $conn->prepare("SELECT username FROM staff WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = "✕ Username already in use! Please choose a different username.";
        } else {
            $password_hashed = password_hash($password_raw, PASSWORD_BCRYPT);
            
            $is_first_login = 1;

            $stmt = $conn->prepare("INSERT INTO staff (staff_ID, username, password, staff_name, role, is_first_login) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $staff_id, $username, $password_hashed, $staff_name, $role, $is_first_login);

            if ($stmt->execute()) {
                
                $success = "✓ Registration Successful!<br>Temporary One-Time Password: <strong>$password_raw</strong><br>Please copy and hand this to the staff member.";
                
                $redirect = false; 
            } else {
                $error = "✕ Failed to register account. Please try again.";
            }
        }
    } catch (Exception $e) {
        $error = "[!] System Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Staff | CAREMEDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/register.css?v=<?php echo time(); ?>">
    
    <?php if ($redirect): ?>
    <script>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 2000); 
    </script>
    <?php endif; ?>
</head>
<body>

<div class="register-wrapper">
    <div class="brand-header">
        <img src="img/caremeds_logo.png" alt="CareMeds Logo" style="height: 120px; width: auto; mix-blend-mode: multiply; margin-bottom: 10px;">
        <p class="subtitle">Staff Account Registration</p>
    </div>

    <?php 
    if($error) echo "<div class='error-msg'>$error</div>"; 
    if($success) echo "<div class='success-msg'>$success</div>";
    ?>

    <form action="register.php" method="POST">
        <label>Staff ID</label>
        <input type="text" name="staff_id" placeholder="e.g. STF001" required>

        <label>Full Name</label>
        <input type="text" name="staff_name" placeholder="e.g. Nur Rena" required>

        <label>Username</label>
        <input type="text" name="username" placeholder="e.g. Nur Rena" required>

        <label>System Role</label>
        <select name="role" required>
            <option value="staff" selected>Staff</option>
            <option value="admin">Administrator</option>
        </select>

        <button type="submit" class="btn-register">Register New Account</button>
    </form>

    <div class="footer-text">
        Already have an account? <a href="login.php">Back to Login</a>
    </div>
</div>

</body>
</html>