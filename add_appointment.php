<?php


require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
    $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $patient_name = trim($_POST['patient_name'] ?? '');
    $patient_age = isset($_POST['patient_age']) ? (int)$_POST['patient_age'] : 0;
    $patient_gender = trim($_POST['patient_gender'] ?? '');
    $patient_contact = trim($_POST['patient_contact'] ?? '');
    $patient_allergies = trim($_POST['patient_allergies'] ?? 'None');
    $patient_chronic = trim($_POST['patient_chronic'] ?? 'None');


    $appointment_id = trim($_POST['appointment_id'] ?? '');
    $hospital = trim($_POST['hospital'] ?? '');
    $doctor = trim($_POST['doctor'] ?? '');
    $date_time = trim($_POST['date_time'] ?? ''); 
    $type = trim($_POST['type'] ?? 'Consultation');
    $status = trim($_POST['status'] ?? 'Scheduled');

    $symptoms = trim($_POST['symptoms'] ?? '');
    $nurse_id = trim($_POST['nurse_id'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$patient_id || empty($patient_name) || !$patient_age || empty($patient_gender) ||
        empty($hospital) || empty($doctor) || empty($date_time) || empty($type) || empty($status) || 
        empty($symptoms) || empty($nurse_id)) {
        header("Location: appointments.php?error=validation");
        exit;
    }

    add_new_patient($patient_id, $patient_name, $patient_age, $patient_gender, $patient_contact, $patient_allergies, $patient_chronic);

    if (empty($appointment_id)) {
        $appointments = get_appointments();
        $max_id = 0;
        foreach ($appointments as $a) {
            $num = (int)str_replace('AP', '', $a['appointment_id']);
            if ($num > $max_id) $max_id = $num;
        }
        $appointment_id = 'AP' . str_pad($max_id + 1, 3, '0', STR_PAD_LEFT);
    }

    $date_time_normalized = str_replace('T', ' ', $date_time);
    if (strlen($date_time_normalized) === 16) {
        $date_time_normalized .= ":00"; 
    }
    
    add_new_appointment([
        "appointment_id" => $appointment_id,
        "patient_id" => $patient_id,
        "hospital" => $hospital,
        "doctor" => $doctor,
        "date_time" => $date_time_normalized,
        "type" => $type,
        "symptoms" => $symptoms,
        "nurse_id" => $nurse_id,
        "status" => $status,
        "notes" => $notes
    ]);

    header("Location: appointments.php?success=1");
    exit;
} else {
    header("Location: appointments.php");
    exit;
}
