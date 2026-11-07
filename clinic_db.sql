CREATE DATABASE IF NOT EXISTS clinic_db;
USE clinic_db;

-- 1. DEPARTMENTS
CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- 2. DOCTORS
CREATE TABLE IF NOT EXISTS doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    department_id INT NOT NULL,
    work_start VARCHAR(20) DEFAULT '09:00 AM',
    work_end VARCHAR(20) DEFAULT '05:00 PM',
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE
);

-- 3. PATIENTS
CREATE TABLE IF NOT EXISTS patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL
);

-- 4. APPOINTMENTS (Acts as Booking)
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time VARCHAR(20) NOT NULL,
    status ENUM('Booked', 'Completed', 'Cancelled') DEFAULT 'Booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    UNIQUE(doctor_id, appointment_date, appointment_time)
);

-- 5. PAYMENTS
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('Pending', 'Paid') DEFAULT 'Pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
);

-- 6. MEDICAL RECORDS
CREATE TABLE IF NOT EXISTS medical_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT UNIQUE NOT NULL,
    diagnosis TEXT NOT NULL,
    prescription TEXT NOT NULL,
    record_date DATE NOT NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
);

-- 3 Specified Departments
INSERT INTO departments (name, description) VALUES 
('General Medicine', 'Primary healthcare and general medical consultations'),
('Dentistry', 'Oral health, teeth cleaning, and dental care'),
('Dermatology', 'Skin, hair, and nail health treatments');

-- 5 Specific Doctors Assigned across General Medicine (1), Dentistry (2), and Dermatology (3)
INSERT INTO doctors (name, email, department_id, work_start, work_end) VALUES 
('Dr. Andrew Ras', 'andrew.ras@clinic.ph', 1, '08:00 AM', '04:00 PM'),
('Dr. Christe Mae Castillon', 'christemae.castillon@clinic.ph', 2, '08:30 AM', '04:30 PM'),
('Dr. Reivein Shean Andolana', 'reiveinshean.andolana@clinic.ph', 3, '09:00 AM', '05:00 PM'),
('Dr. Jemfel Kurt Bersabe', 'jemfelkurt.bersabe@clinic.ph', 1, '10:00 AM', '05:00 PM'),
('Dr. Brandon Jay Doluta', 'brandonjay.doluta@clinic.ph', 2, '08:00 AM', '05:00 PM');