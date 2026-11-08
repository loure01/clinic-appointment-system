<?php
require_once __DIR__ . '/../config/db.php';

//Fetch all the medical departments
function getAllDepartments($conn) {

    $sql = "SELECT * FROM departments ORDER BY name ASC";

    $result = $conn->query($sql);

    if (!$result) {
        die("Departments SQL Error: " . $conn->error);
    }

    return $result;
}
?>