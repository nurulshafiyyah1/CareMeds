<?php
include 'db_connect.php';
session_start();

$admin_roles = ['admin', 'administrator', 'management', 'administrative staff'];
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), $admin_roles)) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];

$name_parts = explode(' ', trim($user_name));
$user_first_name = $name_parts[0];

$today_date = date('Y-m-d');

$res_query = $conn->query("SELECT COUNT(*) AS total FROM RESIDENT");
$total_residents = $res_query->fetch_assoc()['total'];

$due_query = $conn->query("SELECT COUNT(*) AS total FROM SCHEDULE WHERE date = '$today_date'");
$meds_due_today = $due_query->fetch_assoc()['total'];

$missed_query = $conn->query("SELECT COUNT(*) AS total FROM SCHEDULE WHERE status = 'Missed'");
$meds_missed = $missed_query->fetch_assoc()['total'];

$alerts_query = $conn->query("
    SELECT s.time, r.resident_name, m.medicine_name 
    FROM SCHEDULE s
    JOIN RESIDENT r ON s.resident_ID = r.resident_ID
    JOIN MEDICINE m ON s.medicine_ID = m.medicine_ID
    WHERE s.date = '$today_date'
    ORDER BY s.time ASC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CAREMEDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

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
        <li><a href="/caremeds/dashboard.php" class="active"><span class="menu-icon">•</span> Dashboard</a></li>
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

<div class="main-content">
    
    <div class="top-header">
        <div class="welcome-text">
            <h2>Welcome Back, <?php echo htmlspecialchars($user_first_name); ?></h2>
            <p>CareMeds Medication Management & Tracking System</p>
        </div>
        <div class="header-right">
            <span class="role-badge"><?php echo strtoupper(htmlspecialchars($user_role)); ?> PORTAL</span>
            <div class="notification-bell">
                [!] <span class="bell-badge"><?php echo $meds_due_today; ?></span>
            </div>
        </div>
    </div>

    <div class="summary-container">
        <div class="metric-card shadow-sm">
            <div class="card-icon-wrapper bg-green-soft">»</div>
            <div class="card-data">
                <span class="card-label">Total Residents</span>
                <span class="card-number"><?php echo $total_residents; ?></span>
            </div>
        </div>
        <div class="metric-card shadow-sm">
            <div class="card-icon-wrapper bg-orange-soft">»</div>
            <div class="card-data">
                <span class="card-label">Medications Due Today</span>
                <span class="card-number text-orange"><?php echo $meds_due_today; ?></span>
            </div>
        </div>
        <div class="metric-card shadow-sm">
            <div class="card-icon-wrapper bg-red-soft">»</div>
            <div class="card-data">
                <span class="card-label">Missed Medications</span>
                <span class="card-number text-red"><?php echo $meds_missed; ?></span>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        
        <div class="grid-card">
            <div class="grid-card-header" style="margin-bottom: 20px;">
                <h3>Today's Medication Timeline</h3>
                <span class="status-indicator" style="background: #eef7f2; color: #2d6a4f; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Chronological Order</span>
            </div>
            
            <div class="timeline-container" style="position: relative; padding-left: 20px; border-left: 2px solid #e0e0e0; margin-left: 10px;">
                <?php if ($alerts_query->num_rows > 0): ?>
                    <?php while($alert = $alerts_query->fetch_assoc()): ?>
                        <div class="timeline-item" style="position: relative; margin-bottom: 25px;">
                          
                            <div style="position: absolute; left: -27px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: #0077b6; border: 2px solid #fff;"></div>
                            
                            <div class="timeline-content">
                                <div class="time-stamp" style="font-weight: 700; font-size: 13px; color: #0077b6; margin-bottom: 2px;">
                                    <?php echo date('h:i A', strtotime($alert['time'])); ?>
                                </div>
                                <h4 style="font-size: 15px; margin: 0; color: #222; font-weight: 600;">
                                    <?php echo htmlspecialchars($alert['resident_name']); ?>
                                </h4>
                                <p style="font-size: 13px; margin: 3px 0 0 0; color: #666;">
                                    Medicine Prescription: <span style="font-weight: 600; color: #444;"><?php echo htmlspecialchars($alert['medicine_name']); ?></span>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state" style="padding-left: 0;">
                        <p style="color: #2d6a4f; font-weight: 500;">✓ All residents have safely consumed their scheduled medications for today.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid-card">
            <div class="grid-card-header">
                <h3>Administrative Actions</h3>
            </div>
            <p class="grid-card-desc">Quick shortcuts for easy administrative data entry additions:</p>
            
            <div class="action-grid">
                <a href="manage_residents.php" class="action-btn">
                    <span class="btn-icon">»</span> Add New Resident
                </a>
                <a href="add_medicine.php" class="action-btn">
                    <span class="btn-icon">»</span> Add New Medicine
                </a>
                
                <?php if (in_array(strtolower($user_role), ['admin', 'administrator'])): ?>
                    <a href="manage_staff.php" class="action-btn btn-dark">
                        <span class="btn-icon">»</span> Manage Staff Accounts
                    </a>
                <?php endif; ?>
            </div>
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
</div>

</body>
</html>