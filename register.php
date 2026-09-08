<?php
session_start();
$page_title = 'Register - Vehicle Rental';
$current_page = 'register';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    if (empty($full_name) || empty($email) || empty($phone) || empty($address) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email is already registered.";
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user';
        $status = 'active';

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, address, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $full_name, $email, $phone, $address, $hashed_password, $role, $status);

        if ($stmt->execute()) {
            setFlash('success', 'Registration successful. You can now login.');
            redirect('login.php');
        } else {
            $errors[] = "Registration failed. Please try again later.";
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        setFlash('error', implode("<br>", $errors));
    }
}

require_once 'includes/header.php';
?>

<div class="container auth-container">
    <div class="card" style="max-width: 500px; margin: 2rem auto;">
        <div class="card-header">
            <h3>Register</h3>
        </div>
        <div class="card-body">
            <?php
            $flash = getFlash();
            if ($flash) {
                echo '<div class="alert alert-' . sanitize($flash['type']) . '">' . sanitize($flash['message']) . '</div>';
            }
            ?>
            <form action="register.php" method="POST" onsubmit="return validateRegistrationForm(this)">
                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%">Register</button>
            </form>
            <p style="margin-top: 1rem; text-align: center;">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</div>

<script>
function validateRegistrationForm(form) {
    if (form.password.value.length < 6) {
        alert('Password must be at least 6 characters long.');
        return false;
    }
    if (form.password.value !== form.confirm_password.value) {
        alert('Passwords do not match.');
        return false;
    }
    return true;
}
</script>

<?php require_once 'includes/footer.php'; ?>
