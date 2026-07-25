<?php
include 'db_connect.php';
session_start();

$admin_roles = ['admin', 'administrator', 'management', 'administrative staff'];
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), $admin_roles)) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'];
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $med_name = trim($_POST['medicine_name'] ?? '');
    $purpose = trim($_POST['description'] ?? '');

    if (!empty($med_name) && !empty($purpose)) {
        $check_stmt = $conn->prepare("SELECT medicine_name FROM medicine WHERE LOWER(medicine_name) = LOWER(?)");
        $check_stmt->bind_param("s", $med_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "This medicine name is already registered!";
            $message_type = "error";
        } else {
            $medicine_id = 'MED-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)); 
            $stmt = $conn->prepare("INSERT INTO medicine (medicine_ID, medicine_name, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $medicine_id, $med_name, $purpose);
            
            if ($stmt->execute()) {
                $message = "Successfully registered " . htmlspecialchars($med_name) . "!";
                $message_type = "success";
            } else {
                $message = "Database Error: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $message = "Please fill in all required fields.";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" style="width=device-width, initial-scale=1.0">
    <title>Register Medicine | CAREMEDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/add_medicine.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-brand-wrapper" style="padding: 20px; text-align: center;">
                <img src="image/PJKHR_logo.png" alt="Kenangan Hajah Rahmah Logo" style="max-width: 150px; height: auto; margin-bottom: 8px; display: inline-block;">
                <div class="sidebar-brand" style="font-size: 14px; font-weight: 700; letter-spacing: 0.5px; color: #fff;">
                    PUSAT JAGAAN KENANGAN HAJJAH RAHMAH
                </div>
                <div style="font-size: 11px; color: #a2a8b5; font-weight: 500; margin-top: 2px;">CAREMEDS SYSTEM</div>
            </div>
            
            <div class="sidebar-divider"></div>
            
            <ul class="sidebar-menu">
                <li><a href="/caremeds/dashboard.php"><span class="menu-icon">•</span> Dashboard</a></li>
                <li><a href="/caremeds/manage_residents.php"><span class="menu-icon">•</span> Residents</a></li>
                <li><a href="/caremeds/track_schedule.php"><span class="menu-icon">•</span> Medication Tracking</a></li>
                <li><a href="/caremeds/appointments.php"><span class="menu-icon">•</span> Hospital Appointments</a></li>
                <li><a href="/caremeds/manage_staff.php"><span class="menu-icon">•</span> Staff</a></li>
                <?php if (in_array(strtolower($user_role), ['admin', 'administrator'])): ?>
                <li><a href="/caremeds/reports.php"><span class="menu-icon">•</span> Reports</a></li>
                <?php endif; ?>
                <li class="logout-item"><a href="/caremeds/logout.php"><span class="menu-icon">↳</span> Logout</a></li>
            </ul>
        </div>

        <main class="main-content">
            <header class="top-navbar">
                <div class="page-info">
                    <h1>Medication Registration</h1>
                    <p class="sub-page-title">Register Master Inventory Medication</p>
                </div>
                <div class="admin-profile">
                    <span class="badge-admin"><?php echo strtoupper(htmlspecialchars($user_role)); ?> PORTAL</span>
                </div>
            </header>

            <div class="content-body">
                <div class="med-form-card">
                    <?php if (!empty($message)): ?>
                        <div class="system-alert <?php echo $message_type; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card-header">
                        <h3>Register New Medicine </h3>
                        <p>Save basic information about a generic or brand name medicine here.</p>
                    </div>
                    
                    <form action="add_medicine.php" method="POST">
                        <div class="form-group">
                            <label for="medicine_name">Medicine Name</label>
                            <input type="text" id="medicine_name" name="medicine_name" placeholder="e.g., Paracetamol" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Purpose / Medical Use</label>
                            <textarea id="description" name="description" placeholder="e.g., For treating fever and headaches..." rows="4" required></textarea>
                        </div>

                        <div class="form-actions">
                            <a href="dashboard.php" class="btn-cancel">Cancel</a>
                            <button type="submit" class="btn-save">Register Medicine</button>
                        </div>
                    </form>
                </div>
            </div>

            <footer class="portal-footer">
                <div class="portal-footer-wrapper">
                    <div class="portal-footer-logo">
                        <img src="image/caremeds_logo.png" alt="CareMeds Logo" style="height: 24px; width: auto; object-fit: contain;">
                        <span>CareMeds</span>
                    </div>
                    <p class="portal-footer-copy">&copy; 2026 CareMeds: Medication Alert System. All rights reserved.</p>
                    <div class="portal-footer-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</body>
</html>