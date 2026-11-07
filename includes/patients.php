<?php
require_once __DIR__ . '/../config/db.php';

function getOrCreatePatient($name, $email, $phone) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE email = ?");
    $stmt->execute([$email]);
    $patient = $stmt->fetch();

    if ($patient) {
        return $patient['patient_id'];
    }

    $stmt = $pdo->prepare("INSERT INTO patients (name, email, phone) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $phone]);
    return $pdo->lastInsertId();
}

function getPatientById($patient_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    return $stmt->fetch();
}
?>