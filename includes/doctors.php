<?php
function getAllDoctors($conn) {
    
    $sql = "SELECT d.doctor_id, d.name, d.email, d.work_start, d.work_end, dep.name AS dept_name 
            FROM doctors d 
            JOIN departments dep ON d.department_id = dep.department_id 
            ORDER BY d.name ASC";
    return $conn->query($sql);
}
?>