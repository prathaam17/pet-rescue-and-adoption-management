<?php
require_once 'config.php';
include 'includes/header.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = trim($_POST['phone']);
    $role = $_POST['role']; // 'user' or 'ngo'

    // Validation
    $field_errors = [];

    // 1. Name: No numbers or special characters
    if (empty($name)) {
        $field_errors['name'] = "Name is required.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $field_errors['name'] = "Name should only contain letters and spaces.";
    }

    // 2. Phone: EXACTLY 10 digits
    if (empty($phone)) {
        $field_errors['phone'] = "Phone number is required.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $field_errors['phone'] = "Phone number must be exactly 10 digits.";
    }

    // 3. Email: Valid format
    if (empty($email)) {
        $field_errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $field_errors['email'] = "Please enter a valid email address.";
    }

    // 4. Password: Min 6 chars and at least one number
    if (empty($_POST['password'])) {
        $field_errors['password'] = "Password is required.";
    } elseif (strlen($_POST['password']) < 6) {
        $field_errors['password'] = "Password must be at least 6 characters long.";
    } elseif (!preg_match('/[0-9]/', $_POST['password'])) {
        $field_errors['password'] = "Password must contain at least one number.";
    }

    if ($role == 'ngo') {
        if (empty($_POST['ngo_name']))
            $field_errors['ngo_name'] = "NGO Name is required.";
        if (empty($_POST['address']))
            $field_errors['address'] = "Address is required.";
        if (empty($_POST['city']))
            $field_errors['city'] = "City is required.";
        if (empty($_POST['description']))
            $field_errors['description'] = "Description is required.";
        if (!isset($_FILES['registration_certificate']) || $_FILES['registration_certificate']['error'] != 0) {
            $field_errors['registration_certificate'] = "Registration certificate is required for NGOs.";
        }
    }

    if (!empty($field_errors)) {
        $message = '<div class="alert alert-danger">Please correct the errors in the form.</div>';
    } else {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $phone, $role]);
            $user_id = $conn->lastInsertId();

            if ($role == 'ngo') {
                // Handle File Upload
                $cert_path = '';
                if (isset($_FILES['registration_certificate']) && $_FILES['registration_certificate']['error'] == 0) {
                    $target_dir = "uploads/certificates/";
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $file_ext = pathinfo($_FILES["registration_certificate"]["name"], PATHINFO_EXTENSION);
                    $file_name = "cert_" . time() . "_" . uniqid() . "." . $file_ext;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES["registration_certificate"]["tmp_name"], $target_file)) {
                        $cert_path = $target_file;
                    }
                }

                // If NGO, create entry in ngos table
                $ngo_name = trim($_POST['ngo_name']);
                $address = trim($_POST['address']);
                $city = trim($_POST['city']);
                $desc = trim($_POST['description']);
                $services = trim($_POST['services']);

                $stmt_ngo = $conn->prepare("INSERT INTO ngos (user_id, ngo_name, address, city, description, services, registration_certificate, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt_ngo->execute([$user_id, $ngo_name, $address, $city, $desc, $services, $cert_path]);

                $msg_text = "Registration successful! Your NGO account is pending approval.";
            } else {
                $msg_text = "Registration successful! Please login.";
            }

            $conn->commit();
            $message = '<div class="alert alert-success">' . $msg_text . ' <a href="login.php">Login here</a></div>';
            // Clear inputs on success
            $name = $email = $phone = $role = '';
            $_POST = [];

        } catch (PDOException $e) {
            // Attempt to rollback, but silence if connection is already lost
            try {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
            } catch (Exception $rollbackError) {
                // Connection likely dead, ignore rollback error
            }
            if ($e->getCode() == 23000) {
                $field_errors['email'] = "Email already exists.";
                $message = '<div class="alert alert-danger">Email already exists.</div>';
            } else {
                $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 animate-on-scroll">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Create an Account</h4>
                </div>
                <div class="card-body p-4">
                    <?= $message ?>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-4 text-center">
                            <label class="form-label d-block fw-bold">I want to register as:</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="role" id="roleUser" value="user"
                                    <?= (!isset($_POST['role']) || $_POST['role'] == 'user') ? 'checked' : '' ?>
                                    onchange="toggleNgoFields()">
                                <label class="btn btn-outline-primary" for="roleUser"><i
                                        class="bi bi-person me-2"></i>Normal User</label>

                                <input type="radio" class="btn-check" name="role" id="roleNgo" value="ngo"
                                    <?= (isset($_POST['role']) && $_POST['role'] == 'ngo') ? 'checked' : '' ?>
                                    onchange="toggleNgoFields()">
                                <label class="btn btn-outline-primary" for="roleNgo"><i
                                        class="bi bi-building me-2"></i>NGO</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="name"
                                class="form-control <?= isset($field_errors['name']) ? 'is-invalid' : '' ?>"
                                value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                            <?php if (isset($field_errors['name'])): ?>
                                <div class="invalid-feedback"><?= $field_errors['name'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email"
                                class="form-control <?= isset($field_errors['email']) ? 'is-invalid' : '' ?>"
                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                            <?php if (isset($field_errors['email'])): ?>
                                <div class="invalid-feedback"><?= $field_errors['email'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <div class="input-group has-validation">
                                <input type="password" name="password" id="regPassword"
                                    class="form-control <?= isset($field_errors['password']) ? 'is-invalid' : '' ?>">
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('regPassword', 'regIcon')">
                                    <i class="bi bi-eye" id="regIcon"></i>
                                </button>
                                <?php if (isset($field_errors['password'])): ?>
                                    <div class="invalid-feedback"><?= $field_errors['password'] ?></div>
                                <?php endif; ?>
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
                        <div class="mb-3">
                            <label>Phone Number</label>
                            <input type="text" name="phone"
                                class="form-control <?= isset($field_errors['phone']) ? 'is-invalid' : '' ?>"
                                value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"
                                maxlength="10">
                            <?php if (isset($field_errors['phone'])): ?>
                                <div class="invalid-feedback"><?= $field_errors['phone'] ?></div>
                            <?php endif; ?>
                        </div>



                        <!-- NGO Fields (Hidden by default) -->
                        <div id="ngoFields"
                            style="display: <?= (isset($_POST['role']) && $_POST['role'] == 'ngo') ? 'block' : 'none' ?>;">
                            <hr>
                            <h6 class="text-primary">NGO Details</h6>
                            <div class="mb-3">
                                <label>NGO Name</label>
                                <input type="text" name="ngo_name"
                                    class="form-control <?= isset($field_errors['ngo_name']) ? 'is-invalid' : '' ?>"
                                    value="<?= isset($_POST['ngo_name']) ? htmlspecialchars($_POST['ngo_name']) : '' ?>">
                                <?php if (isset($field_errors['ngo_name'])): ?>
                                    <div class="invalid-feedback"><?= $field_errors['ngo_name'] ?></div><?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label>Address</label>
                                <input type="text" name="address"
                                    class="form-control <?= isset($field_errors['address']) ? 'is-invalid' : '' ?>"
                                    value="<?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?>">
                                <?php if (isset($field_errors['address'])): ?>
                                    <div class="invalid-feedback"><?= $field_errors['address'] ?></div><?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label>City</label>
                                <input type="text" name="city"
                                    class="form-control <?= isset($field_errors['city']) ? 'is-invalid' : '' ?>"
                                    value="<?= isset($_POST['city']) ? htmlspecialchars($_POST['city']) : '' ?>">
                                <?php if (isset($field_errors['city'])): ?>
                                    <div class="invalid-feedback"><?= $field_errors['city'] ?></div><?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label>Description/Mission</label>
                                <textarea name="description"
                                    class="form-control <?= isset($field_errors['description']) ? 'is-invalid' : '' ?>"
                                    rows="2"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                                <?php if (isset($field_errors['description'])): ?>
                                    <div class="invalid-feedback"><?= $field_errors['description'] ?></div><?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label>Services (e.g., Rescue, Shelter, Adoption)</label>
                                <input type="text" name="services" class="form-control"
                                    placeholder="Comma separated services"
                                    value="<?= isset($_POST['services']) ? htmlspecialchars($_POST['services']) : '' ?>">
                            </div>
                            <div class="mb-3">
                                <label>NGO Registration Certificate (PDF or Image)</label>
                                <input type="file" name="registration_certificate"
                                    class="form-control <?= isset($field_errors['registration_certificate']) ? 'is-invalid' : '' ?>"
                                    id="certInput">
                                <?php if (isset($field_errors['registration_certificate'])): ?>
                                    <div class="invalid-feedback"><?= $field_errors['registration_certificate'] ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Required for NGO verification</small>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill">Register</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <small>Already have an account? <a href="login.php">Login</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleNgoFields() {
        var role = document.querySelector('input[name="role"]:checked').value;
        var fields = document.getElementById('ngoFields');

        if (role === 'ngo') {
            fields.style.display = 'block';
        } else {
            fields.style.display = 'none';
        }
    }

    // Call once on page load to set correct state
    // Real-time validation
    document.addEventListener('DOMContentLoaded', function() {
        toggleNgoFields(); // Restore this call
        const nameInput = document.querySelector('input[name="name"]');
        const phoneInput = document.querySelector('input[name="phone"]');

        const validateName = (val) => /^[a-zA-Z\s]*$/.test(val);
        const validatePhone = (val) => /^\d*$/.test(val) && val.length <= 10;

        nameInput?.addEventListener('input', function() {
            if (!validateName(this.value)) {
                this.classList.add('is-invalid');
                let feedback = this.nextElementSibling;
                if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    this.after(feedback);
                }
                feedback.textContent = "Name should only contain letters and spaces.";
                feedback.style.display = 'block';
            } else {
                this.classList.remove('is-invalid');
                let feedback = this.nextElementSibling;
                if (feedback?.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            }
        });

        phoneInput?.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            if (this.value.length > 0 && this.value.length < 10) {
                this.classList.add('is-invalid');
                let feedback = this.nextElementSibling;
                if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    this.after(feedback);
                }
                feedback.textContent = "Phone number must be exactly 10 digits.";
                feedback.style.display = 'block';
            } else {
                this.classList.remove('is-invalid');
                let feedback = this.nextElementSibling;
                if (feedback?.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>