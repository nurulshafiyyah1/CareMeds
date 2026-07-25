<?php

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['record_id'] ?? $_POST['appointment_id'] ?? '';
    $note_date = $_POST['note_date'] ?? date('Y-m-d');
    $note_text = trim($_POST['note_text'] ?? '');

    if (empty($appointment_id) || empty($note_text)) {
        header("Location: appointments.php?error=validation");
        exit;
    }

    add_timeline_note($appointment_id, $note_date, $note_text);

    header("Location: appointments.php?action=view&id=" . urlencode($appointment_id) . "&tab=timeline&success_note=1");
    exit;
} else {
    header("Location: appointments.php");
    exit;
}
