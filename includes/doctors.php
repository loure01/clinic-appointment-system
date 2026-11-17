<?php

function getAllDoctors($conn) {

    $sql = "SELECT
                doctors.*,
                departments.name AS dept_name
            FROM doctors
            LEFT JOIN departments
                ON doctors.department_id = departments.department_id
            ORDER BY doctors.name ASC";

    $result = $conn->query($sql);

    $doctors = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
    }

    return $doctors;
}


function getDoctorById($conn, $doctor_id) {

    $stmt = $conn->prepare(
        "SELECT
            doctors.*,
            departments.name AS dept_name
         FROM doctors
         LEFT JOIN departments
            ON doctors.department_id = departments.department_id
         WHERE doctors.doctor_id = ?"
    );

    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

?>