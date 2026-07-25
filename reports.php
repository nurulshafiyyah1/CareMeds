<?php
require_once __DIR__ . '/db_connect.php';
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$allowed_roles = ['admin', 'administrator'];
if (!in_array(strtolower($user_role), $allowed_roles)) {
    if (in_array(strtolower($user_role), ['management', 'administrative staff'])) {
        header("Location: dashboard.php");
    } else {
        header("Location: nurse_dashboard.php");
    }
    exit();
}

$is_admin = true;

$med_stats = [
    'Pending' => 0,
    'Given' => 0,
    'Missed' => 0
];
$med_res = $conn->query("SELECT status, COUNT(*) AS count FROM schedule GROUP BY status");
if ($med_res) {
    while ($row = $med_res->fetch_assoc()) {
        $status = $row['status'];
        if (isset($med_stats[$status])) {
            $med_stats[$status] = (int)$row['count'];
        }
    }
}
$total_meds = array_sum($med_stats);

$app_stats = [
    'Scheduled' => 0,
    'Completed' => 0,
    'Cancelled' => 0,
    'Rescheduled' => 0
];
$app_res = $conn->query("SELECT status, COUNT(*) AS count FROM appointment GROUP BY status");
if ($app_res) {
    while ($row = $app_res->fetch_assoc()) {
        $status = $row['status'];
        if (isset($app_stats[$status])) {
            $app_stats[$status] = (int)$row['count'];
        }
    }
}
$total_apps = array_sum($app_stats);

$occupancy = [];
$occ_res = $conn->query("SELECT room_name, COUNT(*) AS count FROM resident GROUP BY room_name");
if ($occ_res) {
    while ($row = $occ_res->fetch_assoc()) {
        $room = $row['room_name'] ?? 'NO ROOM';
        $occupancy[$room] = (int)$row['count'];
    }
}

$staff_positions = [];
$stf_res = $conn->query("SELECT position, COUNT(*) AS count FROM staff GROUP BY position");
if ($stf_res) {
    while ($row = $stf_res->fetch_assoc()) {
        $pos = $row['position'] ?? 'Other';
        $staff_positions[$pos] = (int)$row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | CAREMEDS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/appointments.css?v=<?php echo time(); ?>">
    
    <style>
        @media print {
            .sidebar, .top-header, .action-print-btn, .sidebar-divider, .logout-item {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            body {
                background-color: white !important;
            }
            .report-card {
                box-shadow: none !important;
                border: 1px solid #e2e8f0 !important;
                page-break-inside: avoid;
            }
        }

        .reports-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
        }

        .report-card {
            background-color: var(--white);
            border: 1px solid var(--light-green);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.03);
        }

        .report-card h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
        }

        .report-stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #e2e8f0;
        }

        .report-stat-row:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
        }

        .stat-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--black);
        }

        .stat-progress-container {
            width: 120px;
            background-color: #e2e8f0;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }

        .stat-progress-bar {
            height: 100%;
            border-radius: 4px;
        }

        .stat-percentage {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            display: inline-block;
            width: 40px;
            text-align: right;
        }
    </style>
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
            <li><a href="/caremeds/dashboard.php"><span class="menu-icon">•</span> Dashboard</a></li>
            <li><a href="/caremeds/manage_residents.php"><span class="menu-icon">•</span> Residents</a></li>
            <li><a href="/caremeds/track_schedule.php"><span class="menu-icon">•</span> Medication Tracking</a></li>
            <li><a href="/caremeds/appointments.php"><span class="menu-icon">•</span> Hospital Appointments</a></li>
            <li><a href="/caremeds/manage_staff.php"><span class="menu-icon">•</span> Staff</a></li>
            <li><a href="/caremeds/reports.php" class="active"><span class="menu-icon">•</span> Reports</a></li>
            <li class="logout-item"><a href="/caremeds/logout.php"><span class="menu-icon">↳</span> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        
        <div class="top-header">
            <div class="welcome-text">
                <h2>Reports & Analytics</h2>
                <p>System reports summary, compliance, and demographic metrics</p>
            </div>
            <div class="header-right">
                <button onclick="window.print()" class="action-print-btn" style="display: flex; align-items: center; gap: 8px; background-color: var(--primary-green); color: white; border: none; font-weight: 600; padding: 10px 16px; border-radius: 6px; cursor: pointer;">
                    <i data-lucide="printer" style="width: 16px; height: 16px;"></i> Print Report
                </button>
            </div>
        </div>

        <div class="reports-grid">
            
            <div class="report-card">
                <h3><i data-lucide="activity" style="width: 18px; height: 18px; color: #10b981;"></i> Medication Compliance Report</h3>
                
                <div class="report-stat-row">
                    <span class="stat-label">Total Scheduled</span>
                    <span class="stat-value"><?php echo $total_meds; ?></span>
                </div>
                
                <?php
                $med_colors = [
                    'Given' => '#10b981',
                    'Pending' => '#3b82f6',
                    'Missed' => '#ef4444'
                ];
                foreach ($med_stats as $status => $count):
                    $pct = $total_meds > 0 ? ($count / $total_meds) * 100 : 0;
                    $color = $med_colors[$status] ?? '#6b7280';
                    ?>
                    <div class="report-stat-row">
                        <span class="stat-label"><?php echo $status; ?> Dosage</span>
                        <div>
                            <div class="stat-progress-container">
                                <div class="stat-progress-bar" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                            </div>
                            <span class="stat-percentage"><?php echo round($pct); ?>%</span>
                            <span class="stat-value" style="display: inline-block; width: 40px; text-align: right;"><?php echo $count; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="report-card">
                <h3><i data-lucide="calendar" style="width: 18px; height: 18px; color: #3b82f6;"></i> Hospital Appointments Report</h3>
                
                <div class="report-stat-row">
                    <span class="stat-label">Total Appointments</span>
                    <span class="stat-value"><?php echo $total_apps; ?></span>
                </div>
                
                <?php
                $app_colors = [
                    'Completed' => '#10b981',
                    'Scheduled' => '#3b82f6',
                    'Rescheduled' => '#f59e0b',
                    'Cancelled' => '#ef4444'
                ];
                foreach ($app_stats as $status => $count):
                    $pct = $total_apps > 0 ? ($count / $total_apps) * 100 : 0;
                    $color = $app_colors[$status] ?? '#6b7280';
                    ?>
                    <div class="report-stat-row">
                        <span class="stat-label"><?php echo $status; ?> Appointments</span>
                        <div>
                            <div class="stat-progress-container">
                                <div class="stat-progress-bar" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                            </div>
                            <span class="stat-percentage"><?php echo round($pct); ?>%</span>
                            <span class="stat-value" style="display: inline-block; width: 40px; text-align: right;"><?php echo $count; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="report-card">
                <h3><i data-lucide="home" style="width: 18px; height: 18px; color: #a855f7;"></i> Resident Room Occupancy</h3>
                <?php if (!empty($occupancy)): ?>
                    <?php 
                    $total_res = array_sum($occupancy);
                    foreach ($occupancy as $room => $count):
                        $pct = $total_res > 0 ? ($count / $total_res) * 100 : 0;
                        ?>
                        <div class="report-stat-row">
                            <span class="stat-label">Room <?php echo htmlspecialchars($room); ?></span>
                            <div>
                                <div class="stat-progress-container">
                                    <div class="stat-progress-bar" style="width: <?php echo $pct; ?>%; background-color: #a855f7;"></div>
                                </div>
                                <span class="stat-percentage"><?php echo round($pct); ?>%</span>
                                <span class="stat-value" style="display: inline-block; width: 40px; text-align: right;"><?php echo $count; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748b; text-align: center; padding: 20px 0;">No residents registered yet.</p>
                <?php endif; ?>
            </div>

            <div class="report-card">
                <h3><i data-lucide="users" style="width: 18px; height: 18px; color: #ec4899;"></i> Staffing Level Report</h3>
                <?php if (!empty($staff_positions)): ?>
                    <?php 
                    $total_stf = array_sum($staff_positions);
                    $pos_colors = [
                        'Nurse' => '#0284c7',
                        'Caregiver' => '#f59e0b',
                        'Physiotherapist' => '#10b981',
                        'Admin' => '#6366f1',
                        'Manager' => '#ec4899'
                    ];
                    foreach ($staff_positions as $pos => $count):
                        $pct = $total_stf > 0 ? ($count / $total_stf) * 100 : 0;
                        $color = $pos_colors[$pos] ?? '#6b7280';
                        ?>
                        <div class="report-stat-row">
                            <span class="stat-label"><?php echo htmlspecialchars($pos); ?>s</span>
                            <div>
                                <div class="stat-progress-container">
                                    <div class="stat-progress-bar" style="width: <?php echo $pct; ?>%; background-color: <?php echo $color; ?>;"></div>
                                </div>
                                <span class="stat-percentage"><?php echo round($pct); ?>%</span>
                                <span class="stat-value" style="display: inline-block; width: 40px; text-align: right;"><?php echo $count; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748b; text-align: center; padding: 20px 0;">No staff registered yet.</p>
                <?php endif; ?>
            </div>

        </div>

        <footer class="portal-footer no-print">
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

  
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
