<?php
include 'db_connect.php';
session_start();

$admin_roles = ['admin', 'administrator', 'management', 'administrative staff'];
$clinical_roles = ['nurse', 'caregiver', 'physiotherapist', 'staff'];
$allowed_roles = array_merge($admin_roles, $clinical_roles);
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), $allowed_roles)) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['name'];$user_role = $_SESSION['role'];$message = "";
$message_type = "";
date_default_timezone_set('Asia/Kuala_Lumpur');
$current_date = date('Y-m-d');
$app_base = '/caremeds/';

if (!empty($_SESSION['med_message'])) {
    $message = $_SESSION['med_message'];
    $message_type = $_SESSION['med_message_type'] ?? 'success';
    unset($_SESSION['med_message'], $_SESSION['med_message_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete_schedule') {
        $delete_id = $_POST['schedule_id'] ?? '';
        if ($delete_id !== '') {
            $delete_stmt = $conn->prepare("DELETE FROM schedule WHERE schedule_ID = ?");
            if ($delete_stmt) {
                $delete_stmt->bind_param("s", $delete_id);
                if ($delete_stmt->execute()) {
                    $message = 'Medication schedule deleted successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to delete medication schedule.';
                    $message_type = 'error';
                }
                $delete_stmt->close();
            }
        }
    } elseif ($action === 'edit_schedule') {
        $schedule_id = $_POST['schedule_id'] ?? '';
        $resident_id = $_POST['resident_id'] ?? '';
        $medicine_id = $_POST['medicine_id'] ?? '';
        $dosage = trim($_POST['dosage'] ?? '');
        $frequency = trim($_POST['frequency'] ?? '');
        $dose_time = $_POST['dose_time'] ?? '';
        $schedule_date = $_POST['schedule_date'] ?? $current_date;

        if ($schedule_id !== '' && $resident_id !== '' && $medicine_id !== '' && $dosage !== '' && $frequency !== '' && $dose_time !== '') {
            $edit_stmt = $conn->prepare("UPDATE schedule SET resident_ID = ?, medicine_ID = ?, dosage = ?, frequency = ?, time = ?, date = ? WHERE schedule_ID = ?");
            if ($edit_stmt) {
                $edit_stmt->bind_param("sssssss", $resident_id, $medicine_id, $dosage, $frequency, $dose_time, $schedule_date, $schedule_id);
                if ($edit_stmt->execute()) {
                    $message = 'Medication schedule updated successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to update medication schedule.';
                    $message_type = 'error';
                }
                $edit_stmt->close();
            }
        } else {
            $message = 'Please complete all edit fields before saving.';
            $message_type = 'error';
        }
    } else {
        $resident_id = $_POST['resident_id'] ?? '';
        $med_names = $_POST['medicine_name'] ?? [];
        $dosages = $_POST['dosage'] ?? [];
        $frequencies = $_POST['frequency'] ?? [];
        $dose_times = $_POST['dose_time'] ?? [];

        if (!empty($resident_id) && !empty($med_names)) {
            $conn->begin_transaction();
            try {
                $sql = "INSERT INTO schedule (schedule_ID, resident_ID, medicine_ID, dosage, frequency, time, date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                for ($i = 0; $i < count($med_names); $i++) {
                    if (empty($med_names[$i])) continue;

                    $schedule_id = "SCH" . uniqid() . rand(10, 99);
                    $status = 'Pending';

                    $stmt->bind_param("ssssssss",
                        $schedule_id,$resident_id,
                        $med_names[$i],
                        $dosages[$i],
                        $frequencies[$i],
                        $dose_times[$i],
                        $current_date,$status
                    );
                    $stmt->execute();
                }
                $resident_name_query = $conn->prepare("SELECT resident_name FROM resident WHERE resident_ID = ?");
                $resident_name = '';
                if ($resident_name_query) {
                    $resident_name_query->bind_param("s", $resident_id);
                    $resident_name_query->execute();
                    $resident_name_result = $resident_name_query->get_result();
                    if ($resident_name_result && $resident_name_result->num_rows > 0) {
                        $resident_name_data = $resident_name_result->fetch_assoc();
                        $resident_name = $resident_name_data['resident_name'] ?? '';
                    }
                    $resident_name_query->close();
                }

                $conn->commit();
                $_SESSION['med_message'] = 'Successfully saved medication schedule for ' . ($resident_name !== '' ? $resident_name : 'the selected resident') . '.';
                $_SESSION['med_message_type'] = 'success';
                header("Location: track_schedule.php?saved=1");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Database Error: " . $e->getMessage();
                $message_type = "error";
            }
        }
    }
}

$resident_options_html = '';
$residents_query = $conn->query("SELECT resident_ID, resident_name, room_name FROM RESIDENT ORDER BY resident_name ASC");
if ($residents_query) {
    while ($res = $residents_query->fetch_assoc()) {
        $resident_options_html .= '<option value="' . htmlspecialchars($res['resident_ID']) . '">' . htmlspecialchars($res['resident_name']) . ' (' . htmlspecialchars($res['room_name'] ?? 'No Room') . ')</option>';
    }
}

$med_options = '';
$medicines_query = $conn->query("SELECT medicine_name, medicine_ID FROM medicine ORDER BY medicine_name ASC");
if ($medicines_query) {
    while ($med = $medicines_query->fetch_assoc()) {
        $med_options .= '<option value="' . htmlspecialchars($med['medicine_ID']) . '">' . htmlspecialchars($med['medicine_name']) . '</option>';
    }
}

$saved_schedules = [];
$schedules_query = $conn->query("SELECT s.schedule_ID, s.resident_ID, r.resident_name, r.room_name, s.medicine_ID, m.medicine_name, s.dosage, s.frequency, s.time, s.date, s.status FROM schedule s JOIN resident r ON s.resident_ID = r.resident_ID LEFT JOIN medicine m ON s.medicine_ID = m.medicine_ID ORDER BY s.date DESC, s.time ASC");
if ($schedules_query) {
    while ($row = $schedules_query->fetch_assoc()) {
        $saved_schedules[] = $row;
    }
}

$grouped_saved_schedules = [];
foreach ($saved_schedules as $schedule) {
    $resident_id = $schedule['resident_ID'] ?? 'unknown';
    $grouped_saved_schedules[$resident_id][] = $schedule;
}

$edit_schedule = null;
$edit_id = $_GET['edit_id'] ?? '';
if ($edit_id !== '') {
    $edit_stmt = $conn->prepare("SELECT s.schedule_ID, s.resident_ID, r.resident_name, r.room_name, s.medicine_ID, m.medicine_name, s.dosage, s.frequency, s.time, s.date, s.status FROM schedule s JOIN resident r ON s.resident_ID = r.resident_ID LEFT JOIN medicine m ON s.medicine_ID = m.medicine_ID WHERE s.schedule_ID = ?");
    if ($edit_stmt) {
        $edit_stmt->bind_param("s", $edit_id);
        $edit_stmt->execute();
        $edit_schedule = $edit_stmt->get_result()->fetch_assoc();
        $edit_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Schedule Creator | CAREMEDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/track_schedule.css?v=<?php echo time(); ?>">
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
            <li><a href="<?php echo $app_base; ?>dashboard.php"><span class="menu-icon">•</span> Dashboard</a></li>
            <li><a href="<?php echo $app_base; ?>manage_residents.php"><span class="menu-icon">•</span> Residents</a></li>
            <li><a href="<?php echo $app_base; ?>track_schedule.php" class="active"><span class="menu-icon">•</span> Medication Tracking</a></li>
            <li><a href="<?php echo $app_base; ?>appointments.php"><span class="menu-icon">•</span> Hospital Appointments</a></li>
            <li><a href="<?php echo $app_base; ?>manage_staff.php"><span class="menu-icon">•</span> Staff</a></li>
            <?php if (in_array(strtolower($user_role), ['admin', 'administrator'])): ?>
            <li><a href="<?php echo $app_base; ?>reports.php"><span class="menu-icon">•</span> Reports</a></li>
            <?php endif; ?>
            <li class="logout-item"><a href="<?php echo $app_base; ?>logout.php"><span class="menu-icon">↳</span> Logout</a></li>
        </ul>
    </div>

    <main class="main-content">
        <div class="top-header">
            <div class="welcome-text">
                <h2>Medication Tracking</h2>
                <p>Add & Edit Medication Schedule. Each schedule stays active for 24 hours and needs to be refreshed for the next day.</p>
            </div>
            <div class="header-right">
                <span class="role-badge"><?php echo strtoupper(htmlspecialchars($user_role)); ?> PORTAL</span>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="system-alert <?php echo $message_type; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; background: <?php echo $message_type === 'success' ? '#eef7f2' : '#ffeef0'; ?>; color: <?php echo $message_type === 'success' ? '#2d6a4f' : '#d90429'; ?>; font-weight: 600;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="track_schedule.php" method="POST">
            <div class="card-box">
                <h3>Select Resident</h3>
                <select name="resident_id" required>
                    <option value="" disabled selected>Select Resident</option>
                    <?php echo $resident_options_html; ?>
                </select>
            </div>

            <div class="grid-layout">
                <div class="card-box">
                    <h3>Create Medicine Schedule</h3>
                    <div id="medicines-container">
                        <div class="medicine-block" id="med-block-1">
                            <h4>Medication Item #1</h4>
                            <div class="flex-row">
                                <div class="form-group">
                                    <select name="medicine_name[]" required>
                                        <option value="" disabled selected>Select Medicine Name</option>
                                        <?php echo $med_options; ?>
                                    </select>
                                </div>
                                <div class="form-group"><input type="text" name="dosage[]" placeholder="Dosage (e.g. 500mg)" required></div>
                            </div>
                            <div class="flex-row">
                                <div class="form-group"><input type="text" name="frequency[]" placeholder="Frequency (e.g. Twice daily)" required></div>
                                <div class="form-group"><input type="time" name="dose_time[]" required></div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-add-more-meds" onclick="addNewMedicineBlock()">+ Add Another Medicine</button>
                </div>
                <div class="card-box">
                    <h3>Preview</h3>
                    <p>Schedule details will appear here.</p>
                </div>
            </div>
            <button type="submit" class="btn-add-more-meds" style="background: #2d6a4f; margin-top: 20px;">Save All Schedules</button>
        </form>

        <?php if ($edit_schedule): ?>
            <div class="card-box" style="margin-top: 20px;">
                <h3>Edit Saved Schedule</h3>
                <form action="track_schedule.php" method="POST">
                    <input type="hidden" name="action" value="edit_schedule">
                    <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($edit_schedule['schedule_ID']); ?>">
                    <div class="flex-row">
                        <div class="form-group">
                            <label>Resident</label>
                            <select name="resident_id" required>
                                <option value="" disabled>Select Resident</option>
                                <?php echo str_replace('value="' . htmlspecialchars($edit_schedule['resident_ID']) . '"', 'value="' . htmlspecialchars($edit_schedule['resident_ID']) . '" selected', $resident_options_html); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Medicine</label>
                            <select name="medicine_id" required>
                                <option value="" disabled>Select Medicine</option>
                                <?php
                                    $medicine_options_html = '';
                                    $medicine_query = $conn->query("SELECT medicine_name, medicine_ID FROM medicine ORDER BY medicine_name ASC");
                                    if ($medicine_query) {
                                        while ($medicine = $medicine_query->fetch_assoc()) {
                                            $selected_attr = ($medicine['medicine_ID'] === $edit_schedule['medicine_ID']) ? ' selected' : '';
                                            $medicine_options_html .= '<option value="' . htmlspecialchars($medicine['medicine_ID']) . '"' . $selected_attr . '>' . htmlspecialchars($medicine['medicine_name']) . '</option>';
                                        }
                                    }
                                    echo $medicine_options_html;
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="flex-row">
                        <div class="form-group">
                            <label>Dosage</label>
                            <input type="text" name="dosage" value="<?php echo htmlspecialchars($edit_schedule['dosage'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Frequency</label>
                            <input type="text" name="frequency" value="<?php echo htmlspecialchars($edit_schedule['frequency'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="flex-row">
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="dose_time" value="<?php echo htmlspecialchars($edit_schedule['time'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="schedule_date" value="<?php echo htmlspecialchars($edit_schedule['date'] ?? $current_date); ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-add-more-meds" style="background: #2563eb; margin-top: 10px;">Save Changes</button>
                    <a href="track_schedule.php" class="btn-add-more-meds" style="background: #64748b; margin-top: 10px; text-decoration: none; display: inline-block;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>

        <div class="card-box" style="margin-top: 20px;">
            <h3>Saved Medication Schedules</h3>
            <?php if (!empty($grouped_saved_schedules)): ?>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($grouped_saved_schedules as $resident_id => $resident_schedules): ?>
                        <?php $resident_name = $resident_schedules[0]['resident_name'] ?? '-'; ?>
                        <div style="border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; background:#f8fafc;">
                            <button type="button" onclick="toggleResident('<?php echo htmlspecialchars($resident_id); ?>')" style="width:100%; text-align:left; background:none; border:none; padding:12px 14px; font-weight:700; color:#0f172a; cursor:pointer; display:flex; align-items:center; justify-content:space-between;">
                                <span><?php echo htmlspecialchars($resident_name); ?></span>
                                <span id="arrow-<?php echo htmlspecialchars($resident_id); ?>" style="font-size:16px;">▾</span>
                            </button>
                            <div id="details-<?php echo htmlspecialchars($resident_id); ?>" style="display:none; padding:0 14px 12px 14px;">
                                <?php foreach ($resident_schedules as $schedule): ?>
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:8px 0; border-top:1px solid #e2e8f0;">
                                        <div>
                                            <div style="font-weight:600; color:#111827;"><?php echo htmlspecialchars($schedule['medicine_name'] ?? '-'); ?></div>
                                            <div style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($schedule['dosage'] ?? '-'); ?> · <?php echo htmlspecialchars($schedule['frequency'] ?? '-'); ?> · <?php echo htmlspecialchars($schedule['time'] ?? '-'); ?> · <?php echo htmlspecialchars($schedule['date'] ?? '-'); ?></div>
                                        </div>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <a href="track_schedule.php?edit_id=<?php echo urlencode($schedule['schedule_ID']); ?>" style="color:#16a34a; font-size:18px; text-decoration:none;" title="Edit">✎</a>
                                            <form action="track_schedule.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_schedule">
                                                <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($schedule['schedule_ID']); ?>">
                                                <button type="submit" style="color:#dc2626; background:none; border:none; padding:0; cursor:pointer; font-size:18px;" title="Delete">✕</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No saved medication schedules yet.</p>
            <?php endif; ?>
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

    <script>
        let medicineCount = 1;
        const masterMedOptions = `<?php echo addslashes($med_options); ?>`;

        function addNewMedicineBlock() {
            medicineCount++;
            const container = document.getElementById('medicines-container');
            const newBlock = document.createElement('div');
            newBlock.className = 'medicine-block';
            newBlock.id = `med-block-${medicineCount}`;
            newBlock.innerHTML = `
                <button type="button" class="remove-med-btn" onclick="removeMedicineBlock(${medicineCount})">Remove</button>
                <h4>Medication Item #${medicineCount}</h4>
                <div class="flex-row">
                    <div class="form-group">
                        <select name="medicine_name[]" required>
                            <option value="" disabled selected>Select Medicine Name</option>
                            ${masterMedOptions}
                        </select>
                    </div>
                    <div class="form-group"><input type="text" name="dosage[]" placeholder="Dosage (e.g. 500mg)" required></div>
                </div>
                <div class="flex-row">
                    <div class="form-group"><input type="text" name="frequency[]" placeholder="Frequency (e.g. Twice daily)" required></div>
                    <div class="form-group"><input type="time" name="dose_time[]" required></div>
                </div>`;
            container.appendChild(newBlock);
        }

        function removeMedicineBlock(id) { 
            const block = document.getElementById(`med-block-${id}`);
            if (block) block.remove(); 
        }

        function toggleResident(residentId) {
            const details = document.getElementById(`details-${residentId}`);
            const arrow = document.getElementById(`arrow-${residentId}`);
            if (!details || !arrow) return;

            const isVisible = details.style.display === 'block';
            details.style.display = isVisible ? 'none' : 'block';
            arrow.textContent = isVisible ? '▸' : '▾';
        }
    </script>
</body>
</html>