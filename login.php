<?php
require_once 'config.php';
include 'includes/header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = '<div class="alert alert-danger">Please enter email and password.</div>';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check if user is banned
            if (isset($user['is_banned']) && $user['is_banned'] == 1) {
                $message = '<div class="alert alert-danger">Your account has been permanently banned due to platform policy violations.</div>';
            } else {
                // Check if NGO is approved
                if ($user['role'] == 'ngo') {
                    $stmt_ngo = $conn->prepare("SELECT status FROM ngos WHERE user_id = ?");
                    $stmt_ngo->execute([$user['id']]);
                    $ngo = $stmt_ngo->fetch();

                    if ($ngo && $ngo['status'] == 'pending') {
                        $message = '<div class="alert alert-warning">Your NGO account is awaiting admin approval.</div>';
                    } elseif ($ngo && $ngo['status'] == 'rejected') {
                        $message = '<div class="alert alert-danger">Your NGO account has been rejected.</div>';
                    } else {
                        // Login
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_role'] = $user['role'];
                        header("Location: ngo/dashboard.php");
                        exit;
                    }
                } elseif ($user['role'] == 'admin') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = 'Admin';
                    $_SESSION['user_role'] = 'admin';
                    header("Location: admin/dashboard.php");
                    exit;
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = 'user';
                    header("Location: user/dashboard.php");
                    exit;
                }
            }
        } else {
            $message = '<div class="alert alert-danger">Invalid email or password.</div>';
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5 animate-on-scroll">
            <div class="card shadow">
                <div class="card-header bg-white text-center border-0 pt-4">
                    <h2 class="fw-bold text-primary">Welcome Back</h2>
                    <p class="text-muted">Login to continue</p>
                </div>
                <div class="card-body p-4">
                    <?= $message ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="loginPassword" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('loginPassword', 'toggleIcon')">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <script>
                            function togglePassword(inputId, iconId) {
                                const passwordInput = document.getElementById(inputId);
                                const icon = document.getElementById(iconId);

                                if (passwordInput.type === "password") {
                                    passwordInput.type = "text";
                                    icon.classList.remove("bi-eye");
                                    icon.classList.add("bi-eye-slash");
                                } else {
                                    passwordInput.type = "password";
                                    icon.classList.remove("bi-eye-slash");
                                    icon.classList.add("bi-eye");
                                }
                            }
                        </script>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill">Login</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <small>Don't have an account? <a href="register.php">Register</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>