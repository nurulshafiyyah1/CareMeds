<?php
include 'db_connect.php';
session_start();

$admin_roles = ['admin', 'administrator', 'management'];
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), $admin_roles)) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];

$today_date = date('Y-m-d');
$due_query = $conn->query("SELECT COUNT(*) AS total FROM SCHEDULE WHERE date = '$today_date' AND status = 'Pending'");
$meds_due_today = $due_query ? $due_query->fetch_assoc()['total'] : 0;

$success_msg = "";
$error_msg = "";

$rooms_list = [];
$room_query = $conn->query("SELECT room_name FROM ROOM ORDER BY room_name ASC");
if ($room_query) {
    while ($r = $room_query->fetch_assoc()) {
        $rooms_list[] = $r['room_name'];
    }
}

if (empty($rooms_list)) {
    $rooms_list = ['MELUR', 'CHEMPAKA', 'KASTURI', 'MAWAR', 'SEROJA'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $resident_id = $_POST['resident_id'];
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $room = $_POST['room_name'];

    try {
        $stmt = $conn->prepare("INSERT INTO RESIDENT (resident_ID, resident_name, age, gender, room_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $resident_id, $name, $age, $gender, $room);
        if ($stmt->execute()) {
            header("Location: manage_residents.php?success=Resident profile saved successfully!");
            exit();
        }
    } catch (Exception $e) {
        $error_msg = "✕ Error: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $resident_id = $_POST['resident_id'];
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $room = $_POST['room_name'];

    try {
        $stmt = $conn->prepare("UPDATE RESIDENT SET resident_name = ?, age = ?, gender = ?, room_name = ? WHERE resident_ID = ?");
        $stmt->bind_param("sisss", $name, $age, $gender, $room, $resident_id);
        if ($stmt->execute()) {
            header("Location: manage_residents.php?success=Profile updated successfully!");
            exit();
        }
    } catch (Exception $e) {
        $error_msg = "✕ Error: " . $e->getMessage();
    }
}

if (isset($_GET['delete_id'])) {
    try {
        $delete_id = $_GET['delete_id'];
        $stmt = $conn->prepare("DELETE FROM RESIDENT WHERE resident_ID = ?");
        $stmt->bind_param("s", $delete_id);
        if ($stmt->execute()) {
            header("Location: manage_residents.php?success=Resident profile deleted.");
            exit();
        }
    } catch (Exception $e) {
        $error_msg = "✕ Delete Failed: Data Dependency Conflict.";
    }
}

if (isset($_GET['success'])) {
    $success_msg = htmlspecialchars($_GET['success']);
}

$residents = $conn->query("SELECT * FROM RESIDENT ORDER BY resident_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents | CAREMEDS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/residents.css?v=<?php echo time(); ?>">
    <style>
    
        .my-custom-modal { 
            display: none !important; 
            position: fixed !important; 
            top: 0 !important; 
            left: 0 !important; 
            width: 100% !important; 
            height: 100% !important; 
            background: rgba(0,0,0,0.5) !important; 
            justify-content: center !important; 
            align-items: center !important; 
            z-index: 99999 !important; 
        }
        .my-modal-box { 
            background: white !important; 
            width: 100% !important; 
            max-width: 450px !important; 
            padding: 25px !important; 
            border-radius: 12px !important; 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1) !important; 
            box-sizing: border-box !important;
        }
        .form-group { margin-bottom: 14px; text-align: left; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #475569; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
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
        <li><a href="/caremeds/dashboard.php"><span class="menu-icon">•</span> Dashboard</a></li>
        <li><a href="/caremeds/manage_residents.php" class="active"><span class="menu-icon">•</span> Residents</a></li>
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
            <h2>Resident Management Directory</h2>
            <p>CareMeds Medication Management & Tracking System</p>
        </div>
        <div class="header-right">
            <span class="role-badge"><?php echo strtoupper(htmlspecialchars($user_role)); ?> PORTAL</span>
        </div>
    </div>

    <div class="management-container" style="margin-top: 20px;">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <p style="color: #666; margin: 0; font-size: 14px;">View, configure, or adjust active nursing facility profiles.</p>
            <button class="btn-primary" style="border: none; border-radius: 6px; background-color: #0077b6; color: white; font-weight: 600; font-size: 14px; cursor: pointer; height: 38px; padding: 0 20px;" onclick="openAddModal()">» Add New Resident</button>
        </div>

        <?php if ($success_msg): ?><div style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;"><?php echo $success_msg; ?></div><?php endif; ?>
        <?php if ($error_msg): ?><div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;"><?php echo $error_msg; ?></div><?php endif; ?>

        <div class="table-responsive">
            <table class="management-table" style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <thead>
                    <tr style="background: #f8fafc; text-align: left; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 14px 16px; color: #475569;">Resident ID</th>
                        <th style="padding: 14px 16px; color: #475569;">Full Name</th>
                        <th style="padding: 14px 16px; color: #475569;">Age</th>
                        <th style="padding: 14px 16px; color: #475569;">Gender</th>
                        <th style="padding: 14px 16px; color: #475569;">Room Name</th>
                        <th style="padding: 14px 16px; color: #475569;">Actions</th>
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
                                <td style="padding: 14px 16px;">
                                    <div style="display: flex; gap: 8px;">
                                        <button type="button" style="padding: 6px 12px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; font-weight: 500; cursor: pointer;" 
                                                onclick="openEditModal('<?php echo htmlspecialchars($row['resident_ID']); ?>', '<?php echo htmlspecialchars(addslashes($row['resident_name'])); ?>', '<?php echo $row['age']; ?>', '<?php echo $row['gender']; ?>', '<?php echo htmlspecialchars(addslashes($row['room_name'])); ?>')">Edit</button>
                                        <button type="button" style="padding: 6px 12px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 4px; font-size: 13px; color: #991b1b; cursor: pointer;" 
                                                onclick="openDeleteModal('<?php echo htmlspecialchars($row['resident_ID']); ?>', '<?php echo htmlspecialchars(addslashes($row['resident_name'])); ?>')">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
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


<div id="residentModal" class="my-custom-modal">
    <div class="my-modal-box">
        <h3 id="modalTitle" style="margin: 0 0 15px 0; color: #1e293b; font-size: 18px;">Add Resident Profile</h3>
        <form method="POST" action="manage_residents.php">
            <input type="hidden" name="action" id="modalAction" value="add">
            
            <div class="form-group">
                <label>Resident ID</label>
                <input type="text" name="resident_id" id="modalResidentId" placeholder="e.g. RES010" required>
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" id="modalName" required>
            </div>
            <div class="form-group">
                <label>Age</label>
                <input type="number" name="age" id="modalAge" required>
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" id="modalGender" required>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <label>Room Name</label>
                <select name="room_name" id="modalRoom" required>
                    <option value="" disabled selected>Select Room</option>
                    <?php foreach ($rooms_list as $room): ?>
                        <option value="<?php echo htmlspecialchars($room); ?>"><?php echo htmlspecialchars($room); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" style="background:#64748b; color:white; border:none; padding:10px 18px; border-radius:6px; cursor:pointer;" onclick="closeResidentModal()">Cancel</button>
                <button type="submit" style="background:#0077b6; color:white; border:none; padding:10px 18px; border-radius:6px; cursor:pointer;">Save Profile</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="my-custom-modal">
    <div class="my-modal-box" style="text-align: center; max-width: 400px;">
        <div style="font-size: 40px; color: #e63946; margin-bottom: 10px;">⚠️</div>
        <h3 style="margin: 0 0 10px 0;">Delete Resident Profile?</h3>
        <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">Are you sure you want to delete <strong id="delete_name_display"></strong>?</p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" style="background:#64748b; color:white; border:none; padding: 10px 20px; border-radius:6px; cursor:pointer;" onclick="closeDeleteModal()">Cancel</button>
            <a id="confirm_delete_btn" href="#" style="background:#e63946; color:white; padding: 10px 20px; border-radius:6px; text-decoration:none; font-size:14px; font-weight:600; display: inline-block; line-height: 20px;">Confirm Delete</a>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = "Add Resident Profile";
    document.getElementById('modalAction').value = "add";
    document.getElementById('modalResidentId').value = "";
    document.getElementById('modalResidentId').disabled = false;
    if(document.getElementById('hiddenEditId')) document.getElementById('hiddenEditId').remove();
    
    document.getElementById('modalName').value = "";
    document.getElementById('modalAge').value = "";
    document.getElementById('modalGender').value = "male";
    document.getElementById('modalRoom').value = "";
    
    document.getElementById('residentModal').style.setProperty('display', 'flex', 'important');
}

function openEditModal(id, name, age, gender, room) {
    document.getElementById('modalTitle').textContent = "Modify Resident Profile";
    document.getElementById('modalAction').value = "edit";
    
    var idInput = document.getElementById('modalResidentId');
    idInput.value = id;
    idInput.disabled = true; 
    
    if(!document.getElementById('hiddenEditId')) {
        var hiddenId = document.createElement('input');
        hiddenId.type = 'hidden';
        hiddenId.name = 'resident_id';
        hiddenId.id = 'hiddenEditId';
        hiddenId.value = id;
        document.getElementById('modalAction').form.appendChild(hiddenId);
    } else {
        document.getElementById('hiddenEditId').value = id;
    }

    document.getElementById('modalName').value = name;
    document.getElementById('modalAge').value = age;
    document.getElementById('modalGender').value = gender.toLowerCase();
    document.getElementById('modalRoom').value = room.toUpperCase();
    
    document.getElementById('residentModal').style.setProperty('display', 'flex', 'important');
}

function closeResidentModal() { 
    document.getElementById('residentModal').style.setProperty('display', 'none', 'important'); 
}

function openDeleteModal(id, name) {
    document.getElementById('delete_name_display').textContent = name;
    document.getElementById('confirm_delete_btn').href = 'manage_residents.php?delete_id=' + encodeURIComponent(id);
    document.getElementById('deleteModal').style.setProperty('display', 'flex', 'important');
}

function closeDeleteModal() { 
    document.getElementById('deleteModal').style.setProperty('display', 'none', 'important'); 
}
</script>

</body>
</html>