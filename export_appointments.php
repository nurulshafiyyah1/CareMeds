<?php

require_once __DIR__ . '/db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$appointments = get_appointments();
$patients = get_patients();
$nurses = get_nurses();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=caremeds_appointments_export_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Appointment ID',
    'Patient ID',
    'Patient Name',
    'Patient Age',
    'Patient Gender',
    'Hospital',
    'Doctor',
    'Appointment Date & Time',
    'Type',
    'Status',
    'Symptoms / Reason',
    'Accompanying Nurse',
    'Special Notes'
]);

$patients_map = [];
foreach ($patients as $p) {
    $patients_map[$p['id']] = $p;
}
$nurses_map = [];
foreach ($nurses as $n) {
    $nurses_map[$n['id']] = $n['name'];
}

foreach ($appointments as $a) {
    $patient_id = $a['patient_id'];
    $p_name = $patients_map[$patient_id]['name'] ?? 'Unknown';
    $p_age = $patients_map[$patient_id]['age'] ?? '-';
    $p_gender = $patients_map[$patient_id]['gender'] ?? '-';
    $nurse_name = $nurses_map[$a['nurse_id']] ?? 'Unknown';

    fputcsv($output, [
        $a['appointment_id'],
        $a['patient_id'],
        $p_name,
        $p_age,
        $p_gender,
        $a['hospital'],
        $a['doctor'],
        $a['date_time'],
        $a['type'],
        $a['status'],
        $a['symptoms'],
        $nurse_name,
        $a['notes']
    ]);
}

fclose($output);
exit();
