<?php
require_once __DIR__ . '/../config/db.php';

function processPayment($appointment_id, $amount, $status = 'Paid') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO payments (appointment_id, amount, payment_status) VALUES (?, ?, ?)");
    return $stmt->execute([$appointment_id, $amount, $status]);
}

function getPaymentByAppointment($appointment_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    return $stmt->fetch();
}
?>