<?php
session_start();
$page_title = 'Login - Vehicle Rental';
$current_page = 'login';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        setFlash('error', 'Please enter email and password.');
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ($user['status'] === 'disabled') {
                setFlash('error', 'Your account has been disabled. Please contact support.');
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];

                if ($user['role'] === 'admin') {
                    redirect('admin/index.php');
                } else {
                    redirect('index.php');
                }
            } else {
                setFlash('error', 'Invalid email or password.');
            }
        } else {
            setFlash('error', 'Invalid email or password.');
        }
        $stmt->close();
    }
}

require_once 'includes/header.php';
?>

<div class="container auth-container">
    <div class="card" style="max-width: 400px; margin: 2rem auto;">
        <div class="card-header">
            <h3>Login</h3>
        </div>
        <div class="card-body">
            <?php
            $flash = getFlash();
            if ($flash) {
                echo '<div class="alert alert-' . sanitize($flash['type']) . '">' . sanitize($flash['message']) . '</div>';
            }
            ?>
            <form action="login.php" method="POST" onsubmit="return validateLoginForm(this)">
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%">Login</button>
            </form>
            <p style="margin-top: 1rem; text-align: center;">Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </div>
</div>

<script>
function validateLoginForm(form) {
    if(form.email.value.trim() === '') {
        alert('Please enter your email.');
        return false;
    }
    if(form.password.value === '') {
        alert('Please enter your password.');
        return false;
    }
    return true;
}
</script>

<?php require_once 'includes/footer.php'; ?>
