<?php
include 'db_connect.php';
session_start();

$clinical_roles = ['nurse', 'caregiver', 'physiotherapist', 'staff'];
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), $clinical_roles)) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'];
$residents = $conn->query("SELECT * FROM RESIDENT ORDER BY resident_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Directory | CAREMEDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/residents.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand-wrapper" style="padding: 20px; text-align: center;">
        <img src="image/PJKHR_logo.png" alt="Logo" style="max-width: 150px; height: auto; margin-bottom: 8px; display: inline-block;">
        <div class="sidebar-brand" style="font-size: 14px; font-weight: 700; color: #fff;">PUSAT JAGAAN KENANGAN HAJJAH RAHMAH</div>
        <div style="font-size: 11px; color: #a2a8b5; font-weight: 500;">CAREMEDS SYSTEM</div>
    </div>
    <div class="sidebar-divider"></div>
    <ul class="sidebar-menu">
        <li><a href="nurse_dashboard.php"><span class="menu-icon">•</span> Dashboard</a></li>
        <li><a href="nurse_residents.php" class="active"><span class="menu-icon">•</span> Residents</a></li>
        <li><a href="nurse_medicine.php"><span class="menu-icon">•</span> Medication Tracking</a></li>
        <li><a href="appointments.php"><span class="menu-icon">•</span> Hospital Appointments</a></li>
        <li class="logout-item"><a href="logout.php"><span class="menu-icon">↳</span> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="top-header">
        <div class="welcome-text">
            <h2>Resident Directory</h2>
            <p>View active resident profiles and assigned rooms.</p>
        </div>
        <div class="header-right">
            <span class="role-badge" style="background: #e0f2fe; color: #0369a1;">NURSE PORTAL</span>
        </div>
    </div>

    <div class="management-container" style="margin-top: 20px;">
        <div class="table-responsive">
            <table class="management-table" style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <thead>
                    <tr style="background: #f8fafc; text-align: left; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 14px 16px; color: #475569;">Resident ID</th>
                        <th style="padding: 14px 16px; color: #475569;">Full Name</th>
                        <th style="padding: 14px 16px; color: #475569;">Age</th>
                        <th style="padding: 14px 16px; color: #475569;">Gender</th>
                        <th style="padding: 14px 16px; color: #475569;">Room Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($residents && $residents->num_rows > 0): ?>
                        <?php while($row = $residents->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 14px 16px; font-weight: 600; color: #0077b6;"><?php echo htmlspecialchars($row['resident_ID']); ?></td>
                                <td style="padding: 14px 16px; font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($row['resident_name']); ?></td>
                                <td style="padding: 14px 16px; color: #334155;"><?php echo htmlspecialchars($row['age']); ?> yrs</td>
                                <td style="padding: 14px 16px; color: #334155;"><?php echo ucfirst(htmlspecialchars($row['gender'])); ?></td>
                                <td style="padding: 14px 16px;"><span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 12px;"><?php echo htmlspecialchars($row['room_name'] ?? 'NO ROOM'); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center; color: #64748b;">No residents found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
