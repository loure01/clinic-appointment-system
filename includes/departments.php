<?php
require_once __DIR__ . '/../config/db.php';

// Fetch all medical departments
function getAllDepartments() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY department_id ASC");
    return $stmt->fetchAll();
}
?>