<?php

function getAllDepartments($conn) {

    $sql = "SELECT * FROM departments ORDER BY name ASC";
    $result = $conn->query($sql);

    $departments = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }
    }

    return $departments;
}

?>