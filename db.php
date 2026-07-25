<?php

require_once __DIR__ . '/db_connect.php';

function get_patients() {
    global $conn;
    $sql = "SELECT * FROM resident ORDER BY resident_name ASC";
    $res = $conn->query($sql);
    $patients = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $patients[] = [
                "id" => $row['resident_ID'],
                "name" => $row['resident_name'],
                "age" => $row['age'] ?? 0,
                "gender" => $row['gender'] ?? 'Male',
                "contact" => $row['contact'] ?? '',
                "allergies" => $row['allergies'] ?? 'None',
                "chronic" => $row['chronic'] ?? 'None'
            ];
        }
    }
    return $patients;
}

function get_nurses() {
    global $conn;
    $sql = "SELECT * FROM staff ORDER BY staff_name ASC";
    $res = $conn->query($sql);
    $nurses = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $nurses[] = [
                "id" => $row['staff_ID'],
                "name" => $row['staff_name']
            ];
        }
    }
    return $nurses;
}

function get_appointments() {
    global $conn;
    $sql = "SELECT a.*, r.resident_name FROM appointment a JOIN resident r ON a.resident_ID = r.resident_ID ORDER BY a.date_time DESC";
    $res = $conn->query($sql);
    $appointments = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $app_id = $row['appointment_id'];
           
            $timeline = [];
            $stmt = $conn->prepare("SELECT * FROM appointment_timeline WHERE appointment_id = ? ORDER BY date ASC, note_id ASC");
            $stmt->bind_param("s", $app_id);
            $stmt->execute();
            $t_res = $stmt->get_result();
            while ($t_row = $t_res->fetch_assoc()) {
                $timeline[] = [
                    "date" => $t_row['date'],
                    "note" => $t_row['note']
                ];
            }
            $stmt->close();

            $appointments[] = [
                "appointment_id" => $row['appointment_id'],
                "patient_id" => $row['resident_ID'],
                "hospital" => $row['hospital'],
                "doctor" => $row['doctor'],
                "date_time" => substr($row['date_time'], 0, 16),
                "type" => $row['type'],
                "symptoms" => $row['symptoms'],
                "nurse_id" => $row['staff_ID'],
                "status" => $row['status'],
                "notes" => $row['notes'],
                "created_at" => $row['created_at'],
                "timeline" => $timeline
            ];
        }
    }
    return $appointments;
}

function save_appointments($appointments) {

}

function add_new_patient($patient_id, $name, $age, $gender, $contact, $allergies, $chronic) {
    global $conn;
  
    $stmt = $conn->prepare("SELECT resident_ID FROM resident WHERE resident_ID = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $stmt->store_result();
    $exists = ($stmt->num_rows > 0);
    $stmt->close();

    if ($exists) {
  
        $stmt_up = $conn->prepare("UPDATE resident SET resident_name = ?, age = ?, gender = ?, contact = ?, allergies = ?, chronic = ? WHERE resident_ID = ?");
        $stmt_up->bind_param("sissssi", $name, $age, $gender, $contact, $allergies, $chronic, $patient_id);
        $stmt_up->execute();
        $stmt_up->close();
    } else {
      
        $stmt_ins = $conn->prepare("INSERT INTO resident (resident_ID, resident_name, age, gender, contact, allergies, chronic) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_ins->bind_param("isissss", $patient_id, $name, $age, $gender, $contact, $allergies, $chronic);
        $stmt_ins->execute();
        $stmt_ins->close();
    }
}

function add_new_appointment($data) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO appointment (appointment_id, resident_ID, hospital, doctor, date_time, type, symptoms, staff_ID, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissssssss", 
        $data['appointment_id'], 
        $data['patient_id'], 
        $data['hospital'], 
        $data['doctor'], 
        $data['date_time'], 
        $data['type'], 
        $data['symptoms'], 
        $data['nurse_id'], 
        $data['status'], 
        $data['notes']
    );
    $stmt->execute();
    $stmt->close();
 
    $stmt_t = $conn->prepare("INSERT INTO appointment_timeline (appointment_id, date, note) VALUES (?, ?, ?)");
    $today = date('Y-m-d');

    $nurse_name = 'Nurse';
    $n_stmt = $conn->prepare("SELECT staff_name FROM staff WHERE staff_ID = ?");
    $n_stmt->bind_param("s", $data['nurse_id']);
    $n_stmt->execute();
    $n_res = $n_stmt->get_result()->fetch_assoc();
    if ($n_res) $nurse_name = $n_res['staff_name'];
    $n_stmt->close();

    $note = "Appointment booked. Accompanying Nurse " . $nurse_name . " assigned.";
    $stmt_t->bind_param("sss", $data['appointment_id'], $today, $note);
    $stmt_t->execute();
    $stmt_t->close();
}

function edit_existing_appointment($data) {
    global $conn;
  
    $stmt_old = $conn->prepare("SELECT status, date_time FROM appointment WHERE appointment_id = ?");
    $stmt_old->bind_param("s", $data['appointment_id']);
    $stmt_old->execute();
    $res = $stmt_old->get_result()->fetch_assoc();
    $stmt_old->close();
    
    $old_status = $res['status'] ?? '';
    $old_date_time = $res['date_time'] ?? '';

    $stmt = $conn->prepare("UPDATE appointment SET resident_ID = ?, hospital = ?, doctor = ?, date_time = ?, type = ?, symptoms = ?, staff_ID = ?, status = ?, notes = ? WHERE appointment_id = ?");
    $stmt->bind_param("isssssssss", 
        $data['patient_id'], 
        $data['hospital'], 
        $data['doctor'], 
        $data['date_time'], 
        $data['type'], 
        $data['symptoms'], 
        $data['nurse_id'], 
        $data['status'], 
        $data['notes'],
        $data['appointment_id']
    );
    $stmt->execute();
    $stmt->close();
    
    $today = date('Y-m-d');
    if ($old_status !== $data['status']) {
        $note = "Appointment status updated from '" . $old_status . "' to '" . $data['status'] . "'.";
        $stmt_t = $conn->prepare("INSERT INTO appointment_timeline (appointment_id, date, note) VALUES (?, ?, ?)");
        $stmt_t->bind_param("sss", $data['appointment_id'], $today, $note);
        $stmt_t->execute();
        $stmt_t->close();
    }
    if ($old_date_time !== $data['date_time']) {
        $note = "Appointment rescheduled to " . $data['date_time'] . ".";
        $stmt_t = $conn->prepare("INSERT INTO appointment_timeline (appointment_id, date, note) VALUES (?, ?, ?)");
        $stmt_t->bind_param("sss", $data['appointment_id'], $today, $note);
        $stmt_t->execute();
        $stmt_t->close();
    }
}

function delete_appointment($app_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM appointment WHERE appointment_id = ?");
    $stmt->bind_param("s", $app_id);
    $stmt->execute();
    $stmt->close();
}

function add_timeline_note($app_id, $date, $note) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO appointment_timeline (appointment_id, date, note) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $app_id, $date, $note);
    $stmt->execute();
    $stmt->close();
}
?>
