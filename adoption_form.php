<?php
require_once 'config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pet_name = $_POST['pet_name'];
    $pet_type = $_POST['pet_type'];
    $gender = $_POST['gender'];
    $breed = trim($_POST['breed']);
    $age = trim($_POST['age']);
    $desc = $_POST['description'];
    $user_id = $_SESSION['user_id'];

    // Age validation: must contain a number
    if (!preg_match('/[0-9]/', $age)) {
        $message = '<div class="alert alert-danger">Age must contain at least one number (e.g., "2 years" or "6 months").</div>';
    }

    $photo_path = '';
    if (empty($message) && isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $max_size = 4 * 1024 * 1024; // 4MB
        if ($_FILES['photo']['size'] > $max_size) {
            $message = '<div class="alert alert-danger">Image size exceeds the 4MB limit.</div>';
        } else {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);
            $filename = time() . '_adopt_' . basename($_FILES['photo']['name']);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                $photo_path = 'uploads/' . $filename;
            }
        }
    }

    if (empty($message)) {
        if ($photo_path) {
            $stmt = $conn->prepare("INSERT INTO adoption_posts (user_id, pet_name, pet_type, gender, breed, age, description, photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $pet_name, $pet_type, $gender, $breed, $age, $desc, $photo_path]);
            $message = '<div class="alert alert-success">Adoption post submitted! Waiting for admin approval.</div>';
        } else {
            $message = '<div class="alert alert-danger">Please upload a photo of the pet.</div>';
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Post a Pet for Adoption</h4>
                </div>
                <div class="card-body">
                    <?= $message ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Pet Name</label>
                                <input type="text" name="pet_name" class="form-control"
                                    value="<?= isset($_POST['pet_name']) ? htmlspecialchars($_POST['pet_name']) : '' ?>"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Pet Type</label>
                                <select name="pet_type" class="form-select" required>
                                    <option value="" disabled selected>Select Type</option>
                                    <option value="Dog" <?= (isset($_POST['pet_type']) && $_POST['pet_type'] == 'Dog') ? 'selected' : '' ?>>Dog</option>
                                    <option value="Cat" <?= (isset($_POST['pet_type']) && $_POST['pet_type'] == 'Cat') ? 'selected' : '' ?>>Cat</option>
                                    <option value="Other" <?= (isset($_POST['pet_type']) && $_POST['pet_type'] == 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="Male" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Unknown" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Unknown') ? 'selected' : '' ?>>Unknown</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Breed</label>
                                <input type="text" name="breed" class="form-control" placeholder="e.g. Golden Retriever"
                                    value="<?= isset($_POST['breed']) ? htmlspecialchars($_POST['breed']) : '' ?>"
                                    required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Age</label>
                            <input type="text" name="age" class="form-control" placeholder="e.g. 2 years"
                                value="<?= isset($_POST['age']) ? htmlspecialchars($_POST['age']) : '' ?>"
                                pattern=".*[0-9].*" title="Age must contain at least one number (e.g. 2 years)"
                                required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                placeholder="Please provide details about the pet’s nature, vaccination status, whether it is good with children, and its diet."
                                required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Post</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>