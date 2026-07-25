<?php
include 'db_connect.php';
session_start();

$clinical_roles = ['nurse', 'caregiver', 'physiotherapist', 'staff'];
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), $clinical_roles)) {
    header("Location: login.php");
    exit();
}

$full_name = $_SESSION['name'];
$name_parts = explode(' ', trim($full_name));
$user_name = $name_parts[0]; 

$user_role = $_SESSION['role'];

date_default_timezone_set('Asia/Kuala_Lumpur');
$today_date = date('d M Y'); 
$current_time = date('h:i A'); 
$db_date = date('Y-m-d');

$total_residents = $conn->query("SELECT COUNT(*) AS total FROM resident")->fetch_assoc()['total'];
$meds_pending = $conn->query("SELECT COUNT(*) AS total FROM schedule WHERE date = '$db_date' AND status = 'Pending'")->fetch_assoc()['total'];
$meds_given = $conn->query("SELECT COUNT(*) AS total FROM schedule WHERE date = '$db_date' AND status = 'Given'")->fetch_assoc()['total'];

$recent_logs = [];
$log_query = $conn->query("
    SELECT s.*, r.resident_name 
    FROM schedule s 
    JOIN resident r ON s.resident_ID = r.resident_ID 
    WHERE s.date = '$db_date' AND s.status != 'Pending'
    ORDER BY s.time DESC LIMIT 3
");
if ($log_query) {
    while ($row = $log_query->fetch_assoc()) {
        $recent_logs[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard | CAREMEDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <style>
        .stats-grid { display: flex; gap: 20px; margin-top: 25px; }
        .stat-card { background: white; padding: 24px; border-radius: 16px; flex: 1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px; }
        .dashboard-layout { display: flex; gap: 20px; margin-top: 25px; }
        .content-left { flex: 2; display: flex; flex-direction: column; gap: 20px; }
        .content-right { flex: 1; display: flex; flex-direction: column; gap: 20px; }
        .info-box { background: white; padding: 24px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .info-box h3 { margin: 0 0 15px 0; color: #1e293b; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .todo-item { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .todo-item:last-child { border-bottom: none; }
        .todo-item input[type="checkbox"] { cursor: pointer; width: 16px; height: 16px; }
        .todo-item input[type="checkbox"]:checked + span { text-decoration: line-through; color: #94a3b8; }
        .log-item { padding: 12px; border-radius: 8px; background: #f8fafc; margin-bottom: 10px; font-size: 13px; border-left: 4px solid #cbd5e1; }
        .log-item.given { border-left-color: #22c55e; background: #f0fdf4; }
        .log-item.missed { border-left-color: #ef4444; background: #fef2f2; }
    </style>
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
        <li><a href="nurse_dashboard.php" class="active"><span class="menu-icon">•</span> Dashboard</a></li>
        <li><a href="nurse_residents.php"><span class="menu-icon">•</span> Residents</a></li>
        <li><a href="nurse_medicine.php"><span class="menu-icon">•</span> Medication Tracking</a></li>
        <li><a href="appointments.php"><span class="menu-icon">•</span> Hospital Appointments</a></li>
        <li class="logout-item"><a href="logout.php"><span class="menu-icon">↳</span> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="top-header" style="background: linear-gradient(135deg, #1e3d37 0%, #0f201d 100%); padding: 30px; border-radius: 16px; color: white; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
        <div class="welcome-text">
            <h2 style="margin: 0; font-size: 24px; color: #fff;">Welcome back, <?php echo ucfirst(strtolower($user_role)) . ' ' . htmlspecialchars($user_name); ?>.</h2>
            <p style="margin: 5px 0 0 0; color: #a3b8b5; font-size: 14px;">Current Login Time: <?php echo $current_time; ?></p>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: 600; font-size: 14px; background: rgba(255,255,255,0.1); padding: 6px 14px; border-radius: 20px;"><?php echo $today_date; ?></div>
            <span class="role-badge" style="background: #e0f2fe; color: #0369a1; margin-top: 10px; display: inline-block;">NURSE PORTAL</span>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div style="background: #edf7f6; color: #1e3d37; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px;">»</div>
            <div>
                <h3 style="margin: 0 0 4px 0; color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Total Residents</h3>
                <p style="margin: 0; font-size: 28px; font-weight: 700; color: #1e293b;"><?php echo $total_residents; ?></p>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #eab308;">
            <div style="background: #fef9c3; color: #854d0e; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px;">»</div>
            <div>
                <h3 style="margin: 0 0 4px 0; color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Meds Pending Today</h3>
                <p style="margin: 0; font-size: 28px; font-weight: 700; color: #eab308;"><?php echo $meds_pending; ?></p>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #22c55e;">
            <div style="background: #dcfce7; color: #166534; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px;">»</div>
            <div>
                <h3 style="margin: 0 0 4px 0; color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Meds Disbursed</h3>
                <p style="margin: 0; font-size: 28px; font-weight: 700; color: #22c55e;"><?php echo $meds_given; ?></p>
            </div>
        </div>
    </div>

    <div class="dashboard-layout">
        <div class="content-left">
            <div class="info-box">
                <h3><span style="color: #0077b6;">»</span> Upcoming Hospital Appointments</h3>
                <div class="table-responsive" style="margin-top: 10px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid #f1f5f9; color: #64748b;">
                                <th style="padding: 8px 0;">Resident</th>
                                <th style="padding: 8px 0;">Hospital / Clinic</th>
                                <th style="padding: 8px 0;">Time Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid #f8fafc;">
                                <td style="padding: 10px 0; font-weight: 600; color: #1e293b;">Ahmad Bin Albab</td>
                                <td style="padding: 10px 0; color: #475569;">Klinik Kesihatan Kota Bharu</td>
                                <td style="padding: 10px 0;"><span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-weight: 600;">10:00 AM</span></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f8fafc;">
                                <td style="padding: 10px 0; font-weight: 600; color: #1e293b;">Siti Aminah</td>
                                <td style="padding: 10px 0; color: #475569;">Hospital Universiti Sains Malaysia
                                </td>
                                <td style="padding: 10px 0;"><span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-weight: 600;">02:30 PM</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="info-box">
                <h3><span style="color: #0077b6;">»</span> Recent Activity Updates (Today)</h3>
                <?php if (!empty($recent_logs)): ?>
                    <?php foreach ($recent_logs as $log): ?>
                        <div class="log-item <?php echo strtolower($log['status']); ?>">
                            <strong><?php echo htmlspecialchars($log['time']); ?></strong> - 
                            Resident <strong><?php echo htmlspecialchars($log['resident_name']); ?></strong> 
                            was marked as <span style="font-weight: bold;"><?php echo strtoupper($log['status']); ?></span>.
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748b; font-size: 14px; margin: 0;">No logs updated yet for today's shifts.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-right">
            <div class="info-box">
                <h3><span style="color: #0077b6;">»</span> Nurse Routine Checklist</h3>
                <div class="todo-item">
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#334155; cursor:pointer;">
                        <input type="checkbox"> <span>Morning vitals checkup</span>
                    </label>
                </div>
                <div class="todo-item">
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#334155; cursor:pointer;">
                        <input type="checkbox"> <span>Verify breakfast medications</span>
                    </label>
                </div>
                <div class="todo-item">
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#334155; cursor:pointer;">
                        <input type="checkbox"> <span>Update resident health logs</span>
                    </label>
                </div>
                <div class="todo-item">
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#334155; cursor:pointer;">
                        <input type="checkbox"> <span>Handover report preparation</span>
                    </label>
                </div>
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