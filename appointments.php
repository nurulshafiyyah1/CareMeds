<?php

require_once __DIR__ . '/db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];

$name_parts = explode(' ', trim($user_name));
$user_first_name = $name_parts[0];

$today_str = "2026-07-15";

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = $_GET['id'];
    delete_appointment($delete_id);
    header("Location: appointments.php");
    exit;
}

$all_appointments = get_appointments();
$patients = get_patients();
$nurses = get_nurses();

$total_appointments_count = count($all_appointments);

$today_appointments_count = 0;
foreach ($all_appointments as $a) {
    if (substr($a['date_time'], 0, 10) === $today_str) {
        $today_appointments_count++;
    }
}

$upcoming_appointments_count = 0;
foreach ($all_appointments as $a) {
    if (($a['status'] === 'Scheduled' || $a['status'] === 'Rescheduled') && substr($a['date_time'], 0, 10) >= $today_str) {
        $upcoming_appointments_count++;
    }
}

$completed_appointments_count = 0;
foreach ($all_appointments as $a) {
    if ($a['status'] === 'Completed') {
        $completed_appointments_count++;
    }
}

$filter_search = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? '';
$filter_hospital = $_GET['hospital'] ?? '';
$filter_doctor = $_GET['doctor'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_date = $_GET['date'] ?? '';

$filtered_appointments = array_filter($all_appointments, function($a) use ($filter_search, $filter_status, $filter_hospital, $filter_doctor, $filter_type, $filter_date, $patients, $today_str) {
  
    if ($filter_search !== '') {
        $search_lc = strtolower($filter_search);
        
        $patient_name = '';
        foreach ($patients as $p) {
            if ($p['id'] === $a['patient_id']) {
                $patient_name = strtolower($p['name']);
                break;
            }
        }
        
        $patient_id_lc = strtolower($a['patient_id']);
        $appointment_id_lc = strtolower($a['appointment_id']);
        $hospital_lc = strtolower($a['hospital']);
        $doctor_lc = strtolower($a['doctor']);
        
        $match_search = (strpos($patient_name, $search_lc) !== false) || 
                       (strpos($patient_id_lc, $search_lc) !== false) || 
                       (strpos($appointment_id_lc, $search_lc) !== false) || 
                       (strpos($hospital_lc, $search_lc) !== false) || 
                       (strpos($doctor_lc, $search_lc) !== false);
        
        if (!$match_search) return false;
    }

    if ($filter_status !== '' && $a['status'] !== $filter_status) {
        return false;
    }

    if ($filter_hospital !== '' && $a['hospital'] !== $filter_hospital) {
        return false;
    }

    if ($filter_doctor !== '' && $a['doctor'] !== $filter_doctor) {
        return false;
    }

    if ($filter_type !== '' && $a['type'] !== $filter_type) {
        return false;
    }

    if ($filter_date !== '') {
        $record_date_str = substr($a['date_time'], 0, 10); 
        if ($filter_date === 'today') {
            if ($record_date_str !== $today_str) return false;
        } elseif ($filter_date === 'week') {
            $diff = abs(strtotime($today_str) - strtotime($record_date_str));
            $days = ceil($diff / (60 * 60 * 24));
            if ($days > 7) return false;
        } elseif ($filter_date === 'month') {
            if (substr($record_date_str, 0, 7) !== '2026-07') return false;
        }
    }

    return true;
});

usort($filtered_appointments, function($a, $b) {
    return strtotime($b['date_time']) - strtotime($a['date_time']);
});

$page_size = 5;
$total_entries = count($filtered_appointments);
$total_pages = max(1, ceil($total_entries / $page_size));
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages) $current_page = $total_pages;

$start_index = ($current_page - 1) * $page_size;
$paginated_appointments = array_slice($filtered_appointments, $start_index, $page_size);

$unique_hospitals = array_unique(array_column($all_appointments, 'hospital'));
$unique_doctors = array_unique(array_column($all_appointments, 'doctor'));
$unique_types = array_unique(array_column($all_appointments, 'type'));

$view_record = null; 
$view_patient = null;
$view_nurse = null;
$view_modal_active = false;
$active_view_tab = $_GET['tab'] ?? 'summary';

if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $view_id = $_GET['id'];
    foreach ($all_appointments as $a) {
        if ($a['appointment_id'] === $view_id) {
            $view_record = $a;
            $view_modal_active = true;
            
            foreach ($patients as $p) {
                if ($p['id'] === $view_record['patient_id']) {
                    $view_patient = $p;
                    break;
                }
            }
            foreach ($nurses as $n) {
                if ($n['id'] === $view_record['nurse_id']) {
                    $view_nurse = $n;
                    break;
                }
            }
            break;
        }
    }
}

function format_display_date($date_str) {
    if (!$date_str) return "-";
    $parts = explode(' ', trim($date_str));
    $date_part = $parts[0];
    $time_part = isset($parts[1]) ? $parts[1] : '';

    if (strpos($date_part, '-') !== false) {
        $d_parts = explode('-', $date_part);
        if (count($d_parts) === 3) {
            $formatted = "{$d_parts[2]}/{$d_parts[1]}/{$d_parts[0]}";
            if ($time_part) {
                $formatted .= " " . $time_part;
            }
            return $formatted;
        }
    }
    return $date_str;
}

function get_status_class($status) {
    if (!$status) return "stable";
    $status_lc = strtolower($status);
    if ($status_lc === 'cancelled') return 'critical'; 
    if ($status_lc === 'rescheduled') return 'monitoring'; 
    if ($status_lc === 'scheduled') return 'light-blue'; 
    if ($status_lc === 'completed') return 'recovered'; 
    return 'stable';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Appointments | CAREMEDS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
   
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/appointments.css?v=<?php echo time(); ?>">
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
            <?php 
            $admin_roles = ['admin', 'administrator', 'management', 'administrative staff'];
            if (in_array(strtolower($user_role), $admin_roles)): 
            ?>
                <li><a href="/caremeds/dashboard.php"><span class="menu-icon">•</span> Dashboard</a></li>
                <li><a href="/caremeds/manage_residents.php"><span class="menu-icon">•</span> Residents</a></li>
                <li><a href="/caremeds/track_schedule.php"><span class="menu-icon">•</span> Medication Tracking</a></li>
                <li><a href="/caremeds/appointments.php" class="active"><span class="menu-icon">•</span> Hospital Appointments</a></li>
                <li><a href="/caremeds/manage_staff.php"><span class="menu-icon">•</span> Staff</a></li>
                <?php if (in_array(strtolower($user_role), ['admin', 'administrator'])): ?>
                <li><a href="/caremeds/reports.php"><span class="menu-icon">•</span> Reports</a></li>
                <?php endif; ?>
            <?php else: ?>
                <li><a href="nurse_dashboard.php"><span class="menu-icon">•</span> Dashboard</a></li>
                <li><a href="nurse_residents.php"><span class="menu-icon">•</span> Residents</a></li>
                <li><a href="track_schedule.php"><span class="menu-icon">•</span> Medication Tracking</a></li>
                <li><a href="appointments.php" class="active"><span class="menu-icon">•</span> Hospital Appointments</a></li>
            <?php endif; ?>
            <li class="logout-item"><a href="logout.php"><span class="menu-icon">↳</span> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
 
        <div class="top-header">
            <div class="welcome-text">
                <h2>Hospital Appointment Management</h2>
                <p>Manage hospital appointments, follow-up visits, specialist consultations, and patient schedules from a centralized platform.</p>
            </div>
            <div class="header-right" style="display: flex; align-items: center; gap: 15px;">
                <?php 
                $admin_roles = ['admin', 'administrator', 'management', 'administrative staff'];
                if (!in_array(strtolower($user_role), $admin_roles)): 
                ?>
                    <span class="role-badge" style="background: #e0f2fe; color: #0369a1; border: none;">NURSE PORTAL</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="summary-container appointments-summary">
            <div class="metric-card shadow-sm">
                <div class="card-icon-wrapper bg-green-soft">📋</div>
                <div class="card-data">
                    <span class="card-label">Total Appointments</span>
                    <span class="card-number"><?php echo $total_appointments_count; ?></span>
                </div>
            </div>
            <div class="metric-card shadow-sm">
                <div class="card-icon-wrapper bg-green-soft">🕒</div>
                <div class="card-data">
                    <span class="card-label">Today's Appointments</span>
                    <span class="card-number"><?php echo $today_appointments_count; ?></span>
                </div>
            </div>
            <div class="metric-card shadow-sm">
                <div class="card-icon-wrapper bg-green-soft">⏳</div>
                <div class="card-data">
                    <span class="card-label">Upcoming Appointments</span>
                    <span class="card-number"><?php echo $upcoming_appointments_count; ?></span>
                </div>
            </div>
            <div class="metric-card shadow-sm">
                <div class="card-icon-wrapper bg-green-soft" style="color: var(--primary-green);">✅</div>
                <div class="card-data">
                    <span class="card-label">Completed Appointments</span>
                    <span class="card-number text-green"><?php echo $completed_appointments_count; ?></span>
                </div>
            </div>
        </div>

        <section class="search-filter-section">
            <form action="appointments.php" method="GET" id="filter-form">
                <div class="search-wrapper">
                    <i data-lucide="search" class="search-icon"></i>
                    <input type="text" name="search" id="search-input" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Search by Patient Name, ID, Appointment ID, Hospital, or Doctor..." onchange="this.form.submit()">
                </div>
                
                <div class="filters-wrapper">
                    <div class="filter-group">
                        <label for="filter-status">Status</label>
                        <select name="status" id="filter-status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <?php
                            $statuses_opts = ['Scheduled', 'Completed', 'Cancelled', 'Rescheduled'];
                            foreach ($statuses_opts as $s_opt) {
                                $selected = ($filter_status === $s_opt) ? 'selected' : '';
                                echo "<option value=\"$s_opt\" $selected>$s_opt</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter-hospital">Hospital</label>
                        <select name="hospital" id="filter-hospital" onchange="this.form.submit()">
                            <option value="">All Hospitals</option>
                            <?php
                            foreach ($unique_hospitals as $hosp) {
                                if (empty($hosp)) continue;
                                $selected = ($filter_hospital === $hosp) ? 'selected' : '';
                                echo "<option value=\"".htmlspecialchars($hosp)."\" $selected>".htmlspecialchars($hosp)."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter-doctor">Doctor</label>
                        <select name="doctor" id="filter-doctor" onchange="this.form.submit()">
                            <option value="">All Doctors</option>
                            <?php
                            foreach ($unique_doctors as $doc) {
                                if (empty($doc)) continue;
                                $selected = ($filter_doctor === $doc) ? 'selected' : '';
                                echo "<option value=\"".htmlspecialchars($doc)."\" $selected>".htmlspecialchars($doc)."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter-type">Type</label>
                        <select name="type" id="filter-type" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <?php
                            foreach ($unique_types as $typ) {
                                if (empty($typ)) continue;
                                $selected = ($filter_type === $typ) ? 'selected' : '';
                                echo "<option value=\"".htmlspecialchars($typ)."\" $selected>".htmlspecialchars($typ)."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter-date">Date Range</label>
                        <select name="date" id="filter-date" onchange="this.form.submit()">
                            <option value="">All Time</option>
                            <option value="today" <?php echo ($filter_date === 'today') ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo ($filter_date === 'week') ? 'selected' : ''; ?>>Next 7 Days</option>
                            <option value="month" <?php echo ($filter_date === 'month') ? 'selected' : ''; ?>>This Month (July)</option>
                        </select>
                    </div>
                    
                    <a href="appointments.php" class="action-btn" title="Reset Filters" style="display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; padding: 0; background-color: var(--white); border: 1px solid var(--border); border-radius: 6px;">
                        <i data-lucide="refresh-cw" style="width: 18px; height: 18px; color: var(--text-muted);"></i>
                    </a>
                </div>
            </form>
        </section>

        <section class="records-table-section" id="directory">
            <div class="table-header-bar">
                <h2>Hospital Appointments Directory</h2>
                <div class="header-action-wrapper" style="display: flex; gap: 10px;">
                    <a href="export_appointments.php" class="action-btn" style="padding: 10px 18px; background-color: var(--white); border: 1px solid var(--primary); color: var(--primary); display: flex; align-items: center; gap: 6px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 13px;">
                        <i data-lucide="download" style="width: 16px; height: 16px;"></i> Export CSV
                    </a>
                    <button onclick="window.print()" class="action-btn" style="padding: 10px 18px; background-color: var(--white); border: 1px solid var(--primary); color: var(--primary); display: flex; align-items: center; gap: 6px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer;">
                        <i data-lucide="printer" style="width: 16px; height: 16px;"></i> Print / PDF
                    </button>
                    <button class="action-btn" id="btn-add-record-table" style="padding: 10px 18px; background-color: var(--primary); color: #fff; border: none; border-radius: 6px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Book Appointment
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="records-table">
                    <thead>
                        <tr>
                            <th>App ID</th>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th>Hospital</th>
                            <th>Doctor</th>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_entries === 0): ?>
                            <tr>
                                <td colspan="9" style="padding: 0;">
                                    <div class="empty-state-wrapper">
                                        <div class="empty-icon"><i data-lucide="calendar-x"></i></div>
                                        <h3>No Appointments Booked</h3>
                                        <p>Try adjusting your search criteria or filters to locate the appointment.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paginated_appointments as $record): 
                                $patient_obj = null;
                                foreach ($patients as $p) {
                                    if ($p['id'] === $record['patient_id']) {
                                        $patient_obj = $p;
                                        break;
                                    }
                                }
                                $status_cls = get_status_class($record['status']);
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($record['appointment_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($record['patient_id']); ?></td>
                                    <td>
                                        <span><?php echo htmlspecialchars($patient_obj ? $patient_obj['name'] : 'Unknown'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['hospital']); ?></td>
                                    <td><?php echo htmlspecialchars($record['doctor']); ?></td>
                                    <td><?php echo format_display_date($record['date_time']); ?></td>
                                    <td><?php echo htmlspecialchars($record['type']); ?></td>
                                    <td><span class="badge badge-<?php echo $status_cls; ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="appointments.php?action=view&id=<?php echo urlencode($record['appointment_id']); ?>&search=<?php echo urlencode($filter_search); ?>&status=<?php echo urlencode($filter_status); ?>&hospital=<?php echo urlencode($filter_hospital); ?>&doctor=<?php echo urlencode($filter_doctor); ?>&type=<?php echo urlencode($filter_type); ?>&date=<?php echo urlencode($filter_date); ?>&page=<?php echo $current_page; ?>#directory" class="btn-icon btn-icon-view" title="View Details">
                                                <i data-lucide="eye"></i>
                                            </a>
                                            <button class="btn-icon btn-icon-edit btn-edit-appointment" 
                                                    data-appointment-id="<?php echo htmlspecialchars($record['appointment_id']); ?>"
                                                    data-patient-id="<?php echo htmlspecialchars($record['patient_id']); ?>"
                                                    data-patient-name="<?php echo htmlspecialchars($patient_obj ? $patient_obj['name'] : ''); ?>"
                                                    data-patient-age="<?php echo htmlspecialchars($patient_obj ? $patient_obj['age'] : ''); ?>"
                                                    data-patient-gender="<?php echo htmlspecialchars($patient_obj ? $patient_obj['gender'] : ''); ?>"
                                                    data-patient-contact="<?php echo htmlspecialchars($patient_obj ? $patient_obj['contact'] : ''); ?>"
                                                    data-patient-allergies="<?php echo htmlspecialchars($patient_obj ? $patient_obj['allergies'] : ''); ?>"
                                                    data-patient-chronic="<?php echo htmlspecialchars($patient_obj ? $patient_obj['chronic'] : ''); ?>"
                                                    data-hospital="<?php echo htmlspecialchars($record['hospital']); ?>"
                                                    data-doctor="<?php echo htmlspecialchars($record['doctor']); ?>"
                                                    data-date-time="<?php echo htmlspecialchars(str_replace(' ', 'T', $record['date_time'])); ?>"
                                                    data-type="<?php echo htmlspecialchars($record['type']); ?>"
                                                    data-status="<?php echo htmlspecialchars($record['status']); ?>"
                                                    data-symptoms="<?php echo htmlspecialchars($record['symptoms']); ?>"
                                                    data-nurse-id="<?php echo htmlspecialchars($record['nurse_id']); ?>"
                                                    data-notes="<?php echo htmlspecialchars($record['notes']); ?>"
                                                    title="Edit Appointment" style="background: none; border: none; cursor: pointer;">
                                                <i data-lucide="edit-3"></i>
                                            </button>
                                            <a href="appointments.php?action=delete&id=<?php echo urlencode($record['appointment_id']); ?>" class="btn-icon btn-icon-delete" onclick="return confirm('Are you sure you want to delete this appointment?')" title="Delete Appointment">
                                                <i data-lucide="trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_entries > 0): ?>
                <div class="table-pagination">
                    <div class="pagination-info">
                        Showing <?php echo $start_index + 1; ?> to <?php echo min($start_index + $page_size, $total_entries); ?> of <?php echo $total_entries; ?> entries
                    </div>
                    <div class="pagination-controls">
                 
                        <?php if ($current_page > 1): ?>
                            <a href="appointments.php?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($filter_search); ?>&status=<?php echo urlencode($filter_status); ?>&hospital=<?php echo urlencode($filter_hospital); ?>&doctor=<?php echo urlencode($filter_doctor); ?>&type=<?php echo urlencode($filter_type); ?>&date=<?php echo urlencode($filter_date); ?>#directory" class="btn-pagination">Previous</a>
                        <?php else: ?>
                            <button class="btn-pagination" disabled>Previous</button>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="appointments.php?page=<?php echo $i; ?>&search=<?php echo urlencode($filter_search); ?>&status=<?php echo urlencode($filter_status); ?>&hospital=<?php echo urlencode($filter_hospital); ?>&doctor=<?php echo urlencode($filter_doctor); ?>&type=<?php echo urlencode($filter_type); ?>&date=<?php echo urlencode($filter_date); ?>#directory" class="btn-pagination <?php echo ($i === $current_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="appointments.php?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($filter_search); ?>&status=<?php echo urlencode($filter_status); ?>&hospital=<?php echo urlencode($filter_hospital); ?>&doctor=<?php echo urlencode($filter_doctor); ?>&type=<?php echo urlencode($filter_type); ?>&date=<?php echo urlencode($filter_date); ?>#directory" class="btn-pagination">Next</a>
                        <?php else: ?>
                            <button class="btn-pagination" disabled>Next</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
      
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

    <div class="modal-overlay hidden" id="add-record-modal">
        <div class="modal-container modal-large">
            <div class="modal-header">
                <div class="modal-title-wrapper">
                    <div class="modal-icon blue">
                        <i data-lucide="calendar-plus"></i>
                    </div>
                    <h2>Book Hospital Appointment</h2>
                </div>
                <button class="modal-close-btn" id="btn-close-add-modal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <form action="add_appointment.php" method="POST" id="health-record-form" novalidate>
           
                <div class="form-tabs">
                    <button type="button" class="form-tab-btn active" data-tab="tab-patient">1. Patient Profile</button>
                    <button type="button" class="form-tab-btn" data-tab="tab-vitals">2. Hospital & Doctor</button>
                    <button type="button" class="form-tab-btn" data-tab="tab-treatment">3. Notes & Accompany</button>
                </div>

                <div class="modal-body">
               
                    <div class="form-tab-content active" id="tab-patient">
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label for="form-patient-name" class="required">Patient Name</label>
                                <input type="text" name="patient_name" id="form-patient-name" list="patients-list" placeholder="Type or select a Patient Name..." required autocomplete="off">
                                <datalist id="patients-list">
                                    <?php foreach ($patients as $p): ?>
                                        <option value="<?php echo htmlspecialchars($p['name']); ?>"><?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['id']; ?>)</option>
                                    <?php endforeach; ?>
                                </datalist>
                                <div class="validation-message">Please enter a patient name.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="form-patient-id" class="required">Patient ID</label>
                                <input type="text" name="patient_id" id="form-patient-id" placeholder="e.g. P007" required>
                                <div class="validation-message">Please enter a patient ID.</div>
                            </div>
                        </div>

                        <div class="grid grid-3">
                            <div class="form-group">
                                <label for="form-patient-age" class="required">Age</label>
                                <input type="number" name="patient_age" id="form-patient-age" placeholder="e.g. 40" required>
                                <div class="validation-message">Please enter patient age.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="form-patient-gender" class="required">Gender</label>
                                <select name="patient_gender" id="form-patient-gender" required>
                                    <option value="" disabled selected>Select Gender...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <div class="validation-message">Please select gender.</div>
                            </div>

                            <div class="form-group">
                                <label for="form-patient-contact">Contact Number</label>
                                <input type="text" name="patient_contact" id="form-patient-contact" placeholder="e.g. +6012-3456789">
                            </div>
                        </div>
                        
                        <div class="grid grid-2 mt-4">
                            <div class="form-group">
                                <label for="form-patient-allergies">Allergies</label>
                                <input type="text" name="patient_allergies" id="form-patient-allergies" placeholder="e.g. Peanut allergy, Penicillin">
                            </div>
                            <div class="form-group">
                                <label for="form-patient-chronic">Chronic Diseases</label>
                                <input type="text" name="patient_chronic" id="form-patient-chronic" placeholder="e.g. Diabetes, Hypertension">
                            </div>
                        </div>
                    </div>

                    <div class="form-tab-content" id="tab-vitals">
                        <div class="grid grid-3">
                            <div class="form-group">
                                <label for="form-appointment-id">Appointment ID (Auto)</label>
                                <?php
                                $max_id = 0;
                                foreach ($all_appointments as $a) {
                                    $num = (int)str_replace('AP', '', $a['appointment_id']);
                                    if ($num > $max_id) $max_id = $num;
                                }
                                $next_app_id = 'AP' . str_pad($max_id + 1, 3, '0', STR_PAD_LEFT);
                                ?>
                                <input type="text" name="appointment_id" id="form-appointment-id" value="<?php echo $next_app_id; ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="form-date-time" class="required">Appointment Date & Time</label>
                                <input type="datetime-local" name="date_time" id="form-date-time" required>
                                <div class="validation-message">Please select appointment date & time.</div>
                            </div>

                            <div class="form-group">
                                <label for="form-type" class="required">Appointment Type</label>
                                <select name="type" id="form-type" required>
                                    <option value="" disabled selected>Select Type...</option>
                                    <option value="Consultation">Consultation</option>
                                    <option value="Follow-up">Follow-up</option>
                                    <option value="Therapy">Therapy</option>
                                    <option value="Surgery">Surgery</option>
                                    <option value="Checkup">Checkup</option>
                                </select>
                                <div class="validation-message">Please select appointment type.</div>
                            </div>
                        </div>

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label for="form-hospital" class="required">Hospital / Medical Center</label>
                                <input type="text" name="hospital" id="form-hospital" list="hospitals-list" placeholder="e.g. Hospital Universiti Sains Malaysia" required>
                                <datalist id="hospitals-list">
                                    <option value="Hospital Universiti Sains Malaysia">
                                    <option value="Klinik Kesihatan Kota Bharu">
                                    <option value="Hospital Raja Perempuan Zainab">
                                </datalist>
                                <div class="validation-message">Please enter hospital name.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="form-doctor" class="required">Attending Doctor</label>
                                <input type="text" name="doctor" id="form-doctor" list="doctors-list" placeholder="e.g. Dr. Adam Malik" required>
                                <datalist id="doctors-list">
                                    <option value="Dr. Adam Malik">
                                    <option value="Dr. Sarah Lim">
                                    <option value="Dr. Rajesh Kumar">
                                    <option value="Dr. Siti Aminah">
                                </datalist>
                                <div class="validation-message">Please enter doctor name.</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-tab-content" id="tab-treatment">
                        <div class="form-group">
                            <label for="form-symptoms" class="required">Symptoms / Reason for Visit</label>
                            <textarea name="symptoms" id="form-symptoms" rows="3" placeholder="Describe symptoms or reasons (e.g. Mild chest tightness, routine blood pressure review)" required></textarea>
                            <div class="validation-message">Please describe the symptoms or reason.</div>
                        </div>

                        <div class="grid grid-2 mt-3">
                            <div class="form-group">
                                <label for="form-nurse" class="required">Accompanying Nurse</label>
                                <select name="nurse_id" id="form-nurse" required>
                                    <option value="" disabled selected>Select Accompanying Nurse...</option>
                                    <?php foreach ($nurses as $n): ?>
                                        <option value="<?php echo $n['id']; ?>"><?php echo htmlspecialchars($n['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="validation-message">Please select an accompanying nurse.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="form-status" class="required">Appointment Status</label>
                                <select name="status" id="form-status" required>
                                    <option value="Scheduled" selected>Scheduled</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="Rescheduled">Rescheduled</option>
                                </select>
                                <div class="validation-message">Please select status.</div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <label for="form-notes">Special Care Notes / Instructions</label>
                            <textarea name="notes" id="form-notes" rows="3" placeholder="Special instructions (e.g. Bring wheelchair, fasting 8 hours prior)..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <div class="modal-footer-nav">
                        <button type="button" class="btn btn-secondary hidden" id="btn-form-prev">Previous</button>
                        <button type="button" class="btn btn-primary" id="btn-form-next">Next</button>
                    </div>
                    <div class="modal-footer-actions">
                        <button type="button" class="btn btn-secondary" id="btn-cancel-record">Cancel</button>
                        <button type="submit" class="btn btn-success" id="btn-save-record">Save Appointment</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay hidden" id="edit-record-modal">
        <div class="modal-container modal-large">
            <div class="modal-header header-gradient-blue" style="background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%); color: #fff;">
                <div class="modal-title-wrapper" style="color: #fff;">
                    <div class="modal-icon text-white" style="background: rgba(255,255,255,0.15);">
                        <i data-lucide="edit-3"></i>
                    </div>
                    <h2>Edit / Reschedule Appointment</h2>
                </div>
                <button class="modal-close-btn close-btn-white" id="btn-close-edit-modal" style="color: #fff; background: none; border: none;">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <form action="edit_appointment.php" method="POST" id="edit-appointment-form">
                <input type="hidden" name="appointment_id" id="edit-appointment-id">
                
                <div class="modal-body">
                 
                    <h3 style="margin: 0 0 14px 0; border-bottom: 1px solid var(--border); padding-bottom: 6px; font-size: 15px; color: var(--primary-green);"><i data-lucide="user" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i> 1. Patient Information</h3>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label for="edit-patient-name" class="required">Patient Name</label>
                            <input type="text" name="patient_name" id="edit-patient-name" list="patients-list-edit" placeholder="Enter patient name..." required autocomplete="off">
                            <datalist id="patients-list-edit">
                                <?php foreach ($patients as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p['name']); ?>"><?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['id']; ?>)</option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-patient-id" class="required">Patient ID</label>
                            <input type="text" name="patient_id" id="edit-patient-id" required>
                        </div>
                    </div>

                    <div class="grid grid-3">
                        <div class="form-group">
                            <label for="edit-patient-age" class="required">Age</label>
                            <input type="number" name="patient_age" id="edit-patient-age" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-patient-gender" class="required">Gender</label>
                            <select name="patient_gender" id="edit-patient-gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit-patient-contact">Contact Number</label>
                            <input type="text" name="patient_contact" id="edit-patient-contact">
                        </div>
                    </div>
                    
                    <div class="grid grid-2 mt-3">
                        <div class="form-group">
                            <label for="edit-patient-allergies">Allergies</label>
                            <input type="text" name="patient_allergies" id="edit-patient-allergies">
                        </div>
                        <div class="form-group">
                            <label for="edit-patient-chronic">Chronic Diseases</label>
                            <input type="text" name="patient_chronic" id="edit-patient-chronic">
                        </div>
                    </div>

                    <h3 style="margin: 20px 0 14px 0; border-bottom: 1px solid var(--border); padding-bottom: 6px; font-size: 15px; color: var(--primary-green);"><i data-lucide="calendar" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i> 2. Appointment Scheduling</h3>
                    <div class="grid grid-3">
                        <div class="form-group">
                            <label for="edit-date-time" class="required">Date & Time</label>
                            <input type="datetime-local" name="date_time" id="edit-date-time" required>
                        </div>

                        <div class="form-group">
                            <label for="edit-type" class="required">Appointment Type</label>
                            <select name="type" id="edit-type" required>
                                <option value="Consultation">Consultation</option>
                                <option value="Follow-up">Follow-up</option>
                                <option value="Therapy">Therapy</option>
                                <option value="Surgery">Surgery</option>
                                <option value="Checkup">Checkup</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit-status" class="required">Appointment Status</label>
                            <select name="status" id="edit-status" required>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Rescheduled">Rescheduled</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-2 mt-3">
                        <div class="form-group">
                            <label for="edit-hospital" class="required">Hospital / Clinic</label>
                            <input type="text" name="hospital" id="edit-hospital" list="hospitals-list-edit" required>
                            <datalist id="hospitals-list-edit">
                                <option value="Hospital Universiti Sains Malaysia">
                                <option value="Klinik Kesihatan Kota Bharu">
                                <option value="Hospital Raja Perempuan Zainab">
                            </datalist>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-doctor" class="required">Attending Doctor</label>
                            <input type="text" name="doctor" id="edit-doctor" list="doctors-list-edit" required>
                            <datalist id="doctors-list-edit">
                                <option value="Dr. Adam Malik">
                                <option value="Dr. Sarah Lim">
                                <option value="Dr. Rajesh Kumar">
                                <option value="Dr. Siti Aminah">
                            </datalist>
                        </div>
                    </div>

                    <h3 style="margin: 20px 0 14px 0; border-bottom: 1px solid var(--border); padding-bottom: 6px; font-size: 15px; color: var(--primary-green);"><i data-lucide="heart" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i> 3. Clinical Reason & Notes</h3>
                    <div class="form-group">
                        <label for="edit-symptoms" class="required">Symptoms / Reason for Visit</label>
                        <textarea name="symptoms" id="edit-symptoms" rows="3" required></textarea>
                    </div>

                    <div class="grid grid-2 mt-3">
                        <div class="form-group">
                            <label for="edit-nurse" class="required">Accompanying Nurse</label>
                            <select name="nurse_id" id="edit-nurse" required>
                                <?php foreach ($nurses as $n): ?>
                                    <option value="<?php echo $n['id']; ?>"><?php echo htmlspecialchars($n['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-notes">Special Care Notes</label>
                            <textarea name="notes" id="edit-notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn-cancel-edit-record">Cancel</button>
                    <button type="submit" class="btn btn-success">Reschedule / Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($view_modal_active && $view_record): 
        $v_rec_date = substr($view_record['date_time'], 0, 10);
        $status_cls = get_status_class($view_record['status']);
    ?>
    <div class="modal-overlay" id="view-record-modal">
        <div class="modal-container modal-large detail-modal-container">
            <div class="modal-header header-gradient-blue" style="background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);">
                <div class="modal-title-wrapper text-white">
                    <div class="modal-icon bg-white-trans">
                        <i data-lucide="folder-heart"></i>
                    </div>
                    <div>
                        <h2 class="text-white" style="margin: 0; line-height: 1.2;">Appointment Details</h2>
                        <p class="text-white-dim text-sm" style="margin: 4px 0 0 0;">Appointment ID: <?php echo htmlspecialchars($view_record['appointment_id']); ?> | DateTime: <?php echo format_display_date($view_record['date_time']); ?></p>
                    </div>
                </div>
                
                <a href="appointments.php?search=<?php echo urlencode($filter_search); ?>&status=<?php echo urlencode($filter_status); ?>&hospital=<?php echo urlencode($filter_hospital); ?>&doctor=<?php echo urlencode($filter_doctor); ?>&type=<?php echo urlencode($filter_type); ?>&date=<?php echo urlencode($filter_date); ?>&page=<?php echo $current_page; ?>#directory" class="modal-close-btn text-white close-btn-white">
                    <i data-lucide="x"></i>
                </a>
            </div>

            <div class="view-modal-tabs">
                <button class="view-tab-btn <?php echo ($active_view_tab === 'summary') ? 'active' : ''; ?>" data-view-tab="view-tab-summary">
                    <i data-lucide="stethoscope"></i> Appointment Summary
                </button>
                <button class="view-tab-btn <?php echo ($active_view_tab === 'patient-profile') ? 'active' : ''; ?>" data-view-tab="view-tab-patient-profile">
                    <i data-lucide="user-cog"></i> Demographics & Allergies
                </button>
                <button class="view-tab-btn <?php echo ($active_view_tab === 'timeline') ? 'active' : ''; ?>" data-view-tab="view-tab-timeline">
                    <i data-lucide="history"></i> Timeline & Progress Notes
                </button>
            </div>

            <div class="modal-body detail-modal-body">
             
                <div class="view-tab-content <?php echo ($active_view_tab === 'summary') ? 'active' : ''; ?>" id="view-tab-summary">
                    <div class="detail-grid">
                        <div class="detail-section flex-span-2">
                            <h3 class="detail-sec-title"><i data-lucide="hospital"></i> Medical Center & Scheduling</h3>
                            <div class="vitals-grid" style="grid-template-columns: repeat(3, 1fr);">
                                <div class="vital-item">
                                    <div class="vital-label">Hospital / Center</div>
                                    <div class="vital-val" style="font-size: 16px; font-weight: 700; margin-top: 6px;"><?php echo htmlspecialchars($view_record['hospital']); ?></div>
                                </div>
                                <div class="vital-item">
                                    <div class="vital-label">Attending Doctor</div>
                                    <div class="vital-val" style="font-size: 16px; font-weight: 700; margin-top: 6px;"><?php echo htmlspecialchars($view_record['doctor']); ?></div>
                                </div>
                                <div class="vital-item">
                                    <div class="vital-label">Appointment Time</div>
                                    <div class="vital-val" style="font-size: 15px; font-weight: 700; margin-top: 6px;"><?php echo format_display_date($view_record['date_time']); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h3 class="detail-sec-title"><i data-lucide="activity"></i> Status & Type</h3>
                            <div class="diagnosis-status-card">
                                <div class="dia-row">
                                    <span class="dia-lbl">Type:</span>
                                    <strong class="dia-val"><?php echo htmlspecialchars($view_record['type']); ?></strong>
                                </div>
                                <div class="dia-row mt-3">
                                    <span class="dia-lbl">Current Status:</span>
                                    <span class="badge badge-<?php echo $status_cls; ?>"><?php echo htmlspecialchars($view_record['status']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="detail-grid mt-4">
                        <div class="detail-section">
                            <h3 class="detail-sec-title"><i data-lucide="file-warning"></i> Symptoms & Reason</h3>
                            <div class="treatment-med-card">
                                <div class="treat-block">
                                    <h4 style="margin:0 0 6px 0;">Reason for Visit</h4>
                                    <p class="notes-text"><?php echo nl2br(htmlspecialchars($view_record['symptoms'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h3 class="detail-sec-title"><i data-lucide="shield-alert"></i> Accompaniment & Care Notes</h3>
                            <div class="notes-display-box">
                                <p class="notes-text italic-desc"><?php echo nl2br(htmlspecialchars($view_record['notes'])); ?></p>
                            </div>
                            <div class="staff-tag-footer mt-3" style="display: flex; justify-content: space-between; font-size: 13px;">
                                <div><span class="lbl-small">Accompanying Nurse:</span> <strong><?php echo htmlspecialchars($view_nurse ? $view_nurse['name'] : 'Unknown'); ?></strong></div>
                                <div><span class="lbl-small">Logged Date:</span> <strong><?php echo substr($view_record['created_at'], 0, 10); ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="view-tab-content <?php echo ($active_view_tab === 'patient-profile') ? 'active' : ''; ?>" id="view-tab-patient-profile">
                    <div class="profile-card-layout">
                        <div class="profile-avatar-big">
                            <div class="profile-pid" style="font-size: 16px; padding: 10px 24px; border-radius: 12px; font-weight: 800; background-color: var(--green-glowing); color: var(--primary); text-align: center;">Patient ID: <?php echo htmlspecialchars($view_record['patient_id']); ?></div>
                        </div>
                        <div class="profile-details-list">
                            <h2 style="margin: 0 0 16px 0;"><?php echo htmlspecialchars($view_patient ? $view_patient['name'] : 'Unknown Patient'); ?></h2>
                            <div class="profile-meta-grid">
                                <div class="meta-cell">
                                    <span class="meta-label">Age</span>
                                    <span class="meta-val"><?php echo $view_patient ? $view_patient['age'] : '-'; ?> years</span>
                                </div>
                                <div class="meta-cell">
                                    <span class="meta-label">Gender</span>
                                    <span class="meta-val"><?php echo $view_patient ? $view_patient['gender'] : '-'; ?></span>
                                </div>
                                <div class="meta-cell">
                                    <span class="meta-label">Contact Number</span>
                                    <span class="meta-val"><?php echo $view_patient ? $view_patient['contact'] : '-'; ?></span>
                                </div>
                            </div>

                            <hr class="profile-hr">

                            <div class="medical-alerts-block">
                                <div class="alert-item-box red-light-bg">
                                    <div class="alert-icon-title">
                                        <i data-lucide="alert-octagon"></i>
                                        <span>Allergies</span>
                                    </div>
                                    <p class="danger-text"><?php echo htmlspecialchars($view_patient ? $view_patient['allergies'] : 'None'); ?></p>
                                </div>
                                <div class="alert-item-box orange-light-bg">
                                    <div class="alert-icon-title">
                                        <i data-lucide="shield-alert"></i>
                                        <span>Chronic Conditions</span>
                                    </div>
                                    <p class="warning-text"><?php echo htmlspecialchars($view_patient ? $view_patient['chronic'] : 'None'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="view-tab-content <?php echo ($active_view_tab === 'timeline') ? 'active' : ''; ?>" id="view-tab-timeline">
                    <div class="timeline-layout">
                        
                        <div class="timeline-sidebar">
                            <h3 class="detail-sec-title"><i data-lucide="edit-3"></i> Add Progress Note</h3>
                            <p class="desc-text">Log clinical observations or vital updates during this hospital visit.</p>
                            
                            <form action="add_note.php" method="POST" class="progress-note-form">
                                <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($view_record['appointment_id']); ?>">
                                <div class="form-group">
                                    <label for="form-note-date">Observation Date</label>
                                    <input type="date" name="note_date" id="form-note-date" value="<?php echo $today_str; ?>" required>
                                </div>
                                <div class="form-group" style="margin-top: 10px;">
                                    <label for="form-note-text">Observation Note</label>
                                    <textarea name="note_text" id="form-note-text" rows="3" placeholder="Appointment completed. Doctor adjusted prescriptions..." required></textarea>
                                </div>
                                <button type="submit" class="action-btn" style="width: 100%; border: none; margin-top: 14px; cursor: pointer; padding: 12px; background-color: var(--primary); color: #fff; border-radius: 6px; font-weight: 600;">
                                    <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Add Note
                                </button>
                            </form>
                        </div>

                        <div class="timeline-display">
                            <h3 class="detail-sec-title"><i data-lucide="route"></i> Appointment Progress Timeline</h3>
                            <div class="timeline-container">
                                <?php
                                $timeline_events = [];
                                
                                $timeline_events[] = [
                                    'date' => substr($view_record['created_at'], 0, 10),
                                    'title' => "Appointment Booked",
                                    'desc' => "Appointment " . $view_record['appointment_id'] . " scheduled at " . $view_record['hospital'] . " with " . $view_record['doctor'] . ". Accompaniment: Nurse " . ($view_nurse ? $view_nurse['name'] : 'Unknown'),
                                    'timestamp' => strtotime($view_record['created_at'])
                                ];
                                
                                if (isset($view_record['timeline']) && is_array($view_record['timeline'])) {
                                    foreach ($view_record['timeline'] as $idx => $p_note) {
                                        $timeline_events[] = [
                                            'date' => $p_note['date'],
                                            'title' => "Progress Log Entry",
                                            'desc' => $p_note['note'],
                                            'timestamp' => strtotime($p_note['date'] . ' 12:00:00') + $idx 
                                        ];
                                    }
                                }

                                usort($timeline_events, function($a, $b) {
                                    return $a['timestamp'] - $b['timestamp'];
                                });

                                foreach ($timeline_events as $ev):
                                ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <span class="timeline-time"><?php echo format_display_date($ev['date']); ?></span>
                                        <div class="timeline-content">
                                            <h4 class="timeline-title" style="margin:0 0 4px 0;"><?php echo htmlspecialchars($ev['title']); ?></h4>
                                            <p class="timeline-desc"><?php echo htmlspecialchars($ev['desc']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <a href="appointments.php?search=<?php echo urlencode($filter_search); ?>&status=<?php echo urlencode($filter_status); ?>&hospital=<?php echo urlencode($filter_hospital); ?>&doctor=<?php echo urlencode($filter_doctor); ?>&type=<?php echo urlencode($filter_type); ?>&date=<?php echo urlencode($filter_date); ?>&page=<?php echo $current_page; ?>#directory" class="action-btn" style="background-color: var(--border); color: var(--text-main); border: 1px solid var(--border); text-decoration: none; padding: 10px 18px; border-radius: 6px;">Close</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        const patientsData = <?php echo json_encode($patients); ?>;
        const nursesData = <?php echo json_encode($nurses); ?>;
    </script>
 
    <script src="app.js"></script>
</body>
</html>
