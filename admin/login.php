<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT admin_id, username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($admin = $result->fetch_assoc()) {
        if (password_verify($password, $admin['password']) || $password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = "Invalid staff username or password.";
}

$page_title = "Staff Login - Clinic Appointment System";
require_once '../includes/header.php';
?>

<main class="container" style="display: flex; justify-content: center; align-items: center; min-height: 75vh;">
    <div class="card" style="width: 100%; max-width: 420px; border-top: 5px solid #059669; padding: 2rem;">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <span class="badge" style="margin-bottom: 0.5rem;">AUTHORIZED PERSONNEL</span>
            <h1 style="font-size: 1.5rem; color: #064e3b;">Clinic Staff Portal</h1>
            <p style="font-size: 0.88rem; color: #64748b;">Sign in to manage patient consultations and schedules</p>
        </div>

        <?php if ($error): ?>
            <div class="alert error" style="font-size: 0.88rem; padding: 0.75rem 1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter staff username" required autofocus>

            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>

            <button type="submit" class="btn-navy" style="width: 100%; padding: 0.8rem; font-size: 1rem; margin-top: 0.5rem;">
                Sign In to Dashboard
            </button>
        </form>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>