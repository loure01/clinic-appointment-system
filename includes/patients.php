<?php

function getPatientByEmail($conn, $email) {

    $stmt = $conn->prepare(
        "SELECT * FROM patients WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}


function createPatient($conn, $name, $email, $phone) {

    $stmt = $conn->prepare(
        "INSERT INTO patients (name, email, phone)
         VALUES (?, ?, ?)"
    );

    $stmt->bind_param("sss", $name, $email, $phone);

    if ($stmt->execute()) {
        return $stmt->insert_id;
    }

    return false;
}

?>