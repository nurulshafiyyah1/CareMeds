<?php
include 'db_connect.php';
session_start();

$clinical_roles = ['nurse', 'caregiver', 'physiotherapist', 'staff'];
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), $clinical_roles)) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'];
$message = '';
$message_type = '';

date_default_timezone_set('Asia/Kuala_Lumpur');
$db_date = date('Y-m-d');
$previous_day_date = date('Y-m-d', strtotime('-1 day'));

$reset_stmt = $conn->prepare("UPDATE schedule SET status = 'Pending' WHERE date < ?");
if ($reset_stmt) {
    $reset_stmt->bind_param("s", $db_date);
    $reset_stmt->execute();
    $reset_stmt->close();
}

if (!empty($_GET['saved'])) {
    $message = 'Medication schedule saved successfully.';
    $message_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $row_keys = $_POST['row_key'] ?? [];
    $statuses = $_POST['status'] ?? [];

    if (!empty($row_keys)) {
        $updated_count = 0;
        foreach ($row_keys as $index => $row_key) {
            if (!isset($statuses[$index])) {
                continue;
            }

            $parts = explode('|', $row_key);
            $resident_id = $parts[0] ?? '';
            $medicine_id = $parts[1] ?? '';
            $dosage = $parts[2] ?? '';
            $frequency = $parts[3] ?? '';
            $time_value = $parts[4] ?? '';
            $schedule_date = $parts[5] ?? $db_date;
            $selected_status = strtoupper(trim($statuses[$index]));

            if ($resident_id === '' || $medicine_id === '') {
                continue;
            }

            $db_status = match ($selected_status) {
                'YES' => 'Given',
                'NO' => 'Pending',
                'N/A' => 'N/A',
                default => 'Pending',
            };

            $stmt = $conn->prepare("UPDATE schedule SET status = ? WHERE resident_ID = ? AND medicine_ID = ? AND dosage = ? AND frequency = ? AND time = ? AND date = ?");
            $stmt->bind_param("sssssss", $db_status, $resident_id, $medicine_id, $dosage, $frequency, $time_value, $schedule_date);
            if ($stmt->execute()) {
                $updated_count++;
            }
        }

        if ($updated_count > 0) {
            $message = 'Medication status updated successfully.';
            $message_type = 'success';
        } else {
            $message = 'No medication status was updated.';
            $message_type = 'error';
        }
    }
}

$scheduled_items = [];
$med_query = $conn->query("SELECT s.*, r.resident_name, m.medicine_name, m.description FROM schedule s JOIN resident r ON s.resident_ID = r.resident_ID LEFT JOIN medicine m ON s.medicine_ID = m.medicine_ID WHERE s.date = '$db_date' OR s.date = '$previous_day_date' ORDER BY s.date DESC, s.time ASC, r.resident_name ASC");
if ($med_query) {
    while ($row = $med_query->fetch_assoc()) {
        $scheduled_items[] = $row;
    }
}

$grouped_items = [];
foreach ($scheduled_items as $item) {
    $resident_key = $item['resident_ID'] ?? ($item['resident_name'] ?? 'unknown');
    $grouped_items[$resident_key][] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Medication Tracking | CAREMEDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <style>
        body { background: #f8fafc; }
        .page-card { background: white; padding: 24px; border-radius: 16px; box-shadow: 0 8px 20px rgba(15,23,42,0.06); margin-top: 20px; }
        .status-group { display: flex; flex-direction: row; gap: 10px; align-items: center; flex-wrap: nowrap; }
        .status-option { display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 8px; cursor: pointer; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 999px; background: #f8fafc; min-width: 110px; transition: all 0.2s ease; }
        .status-label { font-size: 12px; color: #475569; font-weight: 700; text-align: center; white-space: nowrap; }
        .status-box { width: 28px; height: 28px; border: 2px solid #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; background: white; color: #64748b; }
        .status-option input { display: none; }
        .status-option input:checked + .status-label { color: #0f172a; }
        .status-option input:checked ~ .status-box { border-color: #16a34a; background: #ecfdf3; color: #15803d; box-shadow: inset 0 0 0 1px rgba(22, 163, 74, 0.15); }
        .status-option.pending input:checked ~ .status-box { border-color: #dc2626; background: #fef2f2; color: #dc2626; box-shadow: inset 0 0 0 1px rgba(220, 38, 38, 0.15); }
        .status-option.na input:checked ~ .status-box { border-color: #d97706; background: #fffbeb; color: #b45309; box-shadow: inset 0 0 0 1px rgba(217, 119, 6, 0.15); }
        .empty-state { padding: 20px; color: #64748b; text-align: center; background: #f8fafc; border-radius: 10px; }
        .resident-group { margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; background: #f8fafc; }
        .resident-group-toggle { width: 100%; display: flex; align-items: center; justify-content: space-between; background: transparent; border: none; padding: 0; cursor: pointer; font: inherit; text-align: left; }
        .resident-group-header { font-size: 16px; font-weight: 800; color: #111111; }
        .resident-group-arrow { font-size: 18px; color: #64748b; transition: transform 0.2s ease; }
        .resident-group.collapsed .resident-group-body { display: none; }
        .resident-group.expanded .resident-group-arrow { transform: rotate(90deg); }
        .resident-group-body { margin-top: 12px; }
        .table-responsive table { width: 100%; border-collapse: collapse; }
        .table-responsive th, .table-responsive td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .table-responsive thead th { color: #ffffff; font-weight: 800; text-align: center; background: #1b4332; }
        .table-responsive tbody tr { background: #fcfffd; color: #111111; }
        .table-responsive tbody tr:nth-child(even) { background: #f6fcf8; }
        .table-responsive td { text-align: center; vertical-align: middle; color: #111111; }
        .table-responsive td:first-child { text-align: left; }
        .btn-save { background: #2563eb; color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 15px; }
        .msg-success { padding: 10px 12px; border-radius: 8px; background: #ecfdf3; color: #166534; margin-bottom: 15px; }
        .msg-error { padding: 10px 12px; border-radius: 8px; background: #fef2f2; color: #b91c1c; margin-bottom: 15px; }
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
        <li><a href="nurse_dashboard.php"><span class="menu-icon">•</span> Dashboard</a></li>
        <li><a href="nurse_residents.php"><span class="menu-icon">•</span> Residents</a></li>
        <li><a href="nurse_medicine.php" class="active"><span class="menu-icon">•</span> Medication Tracking</a></li>
        <li><a href="appointments.php"><span class="menu-icon">•</span> Hospital Appointments</a></li>
        <li class="logout-item"><a href="logout.php"><span class="menu-icon">↳</span> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="top-header">
        <div class="welcome-text">
            <h2>Medication Administration</h2>
            <p>Record whether each resident's medicine was given, not given, or not applicable. Schedules remain active for 24 hours before they need to be refreshed for the next day.</p>
        </div>
        <div class="header-right">
            <span class="role-badge" style="background: #e0f2fe; color: #0369a1;">NURSE PORTAL</span>
        </div>
    </div>

    <div class="page-card">
        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_type === 'success' ? 'msg-success' : 'msg-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="nurse_medicine.php">
            <?php if (!empty($scheduled_items)): ?>
                <?php $form_index = 0; foreach ($grouped_items as $resident_items): ?>
                    <?php $resident_name = $resident_items[0]['resident_name'] ?? 'Unknown Resident'; ?>
                    <div class="resident-group collapsed">
                        <button type="button" class="resident-group-toggle" aria-expanded="false" onclick="toggleResidentGroup(this)">
                            <span class="resident-group-header"><?php echo htmlspecialchars($resident_name); ?></span>
                            <span class="resident-group-arrow">▸</span>
                        </button>
                        <div class="resident-group-body">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Medicine</th>
                                            <th>Time</th>
                                            <th>Dosage</th>
                                            <th>Frequency</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resident_items as $item): ?>
                                            <?php
                                                $current_status = strtoupper((string)($item['status'] ?? ''));
                                                $selected_yes = $current_status === 'GIVEN' ? 'checked' : '';
                                                $selected_no = in_array($current_status, ['MISSED', 'PENDING']) ? 'checked' : '';
                                                $selected_na = $current_status === 'N/A' ? 'checked' : '';
                                            ?>
                                            <tr>
                                                <td>
                                                    <div><strong><?php echo htmlspecialchars($item['medicine_name'] ?? ($item['medicine_ID'] ?? '-')); ?></strong></div>
                                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Purpose: <?php echo htmlspecialchars($item['description'] ?? 'No purpose recorded'); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['time'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($item['dosage'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($item['frequency'] ?? '-'); ?></td>
                                                <td>
                                                    <input type="hidden" name="row_key[<?php echo $form_index; ?>]" value="<?php echo htmlspecialchars(($item['resident_ID'] ?? '') . '|' . ($item['medicine_ID'] ?? '') . '|' . ($item['dosage'] ?? '') . '|' . ($item['frequency'] ?? '') . '|' . ($item['time'] ?? '') . '|' . ($item['date'] ?? $db_date)); ?>">
                                                    <div class="status-group">
                                                        <label class="status-option">
                                                            <input type="radio" name="status[<?php echo $form_index; ?>]" value="YES" <?php echo $selected_yes; ?>>
                                                            <span class="status-label">Administered</span>
                                                            <span class="status-box">✓</span>
                                                        </label>
                                                        <label class="status-option pending">
                                                            <input type="radio" name="status[<?php echo $form_index; ?>]" value="NO" <?php echo $selected_no; ?>>
                                                            <span class="status-label">Pending</span>
                                                            <span class="status-box">✕</span>
                                                        </label>
                                                        <label class="status-option na">
                                                            <input type="radio" name="status[<?php echo $form_index; ?>]" value="N/A" <?php echo $selected_na; ?>>
                                                            <span class="status-label">N/A</span>
                                                            <span class="status-box">⚠</span>
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php $form_index++; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn-save">Save Medication Status</button>
            <?php else: ?>
                <div class="empty-state">No medication schedules found for today.</div>
            <?php endif; ?>
        </form>
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

<script>
function toggleResidentGroup(button) {
    const group = button.closest('.resident-group');
    if (!group) return;

    const isCollapsed = group.classList.contains('collapsed');
    group.classList.toggle('collapsed', !isCollapsed);
    group.classList.toggle('expanded', isCollapsed);
    button.setAttribute('aria-expanded', isCollapsed ? 'true' : 'false');

    const arrow = button.querySelector('.resident-group-arrow');
    if (arrow) {
        arrow.textContent = isCollapsed ? '▾' : '▸';
    }
}
</script>

</body>
</html>
