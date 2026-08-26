<?php
require_once 'config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login to apply for adoption.'); window.location.href='" . BASE_URL . "login.php';</script>";
    exit;
}

$post_id = $_GET['id'] ?? null;

if (!$post_id) {
    header("Location: adoption.php");
    exit;
}

// Fetch Pet details
$stmt = $conn->prepare("SELECT * FROM adoption_posts WHERE id = ?");
$stmt->execute([$post_id]);
$pet = $stmt->fetch();

if (!$pet) {
    die("Pet not found.");
}

// Check if already applied
$stmt_check = $conn->prepare("SELECT count(*) FROM adoption_applications WHERE user_id = ? AND adoption_post_id = ?");
$stmt_check->execute([$_SESSION['user_id'], $post_id]);
if ($stmt_check->fetchColumn() > 0) {
    echo "<script>alert('You have already applied for this pet.'); window.location.href='" . BASE_URL . "adoption.php';</script>";
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $msg = trim($_POST['message']);
    $agreement = isset($_POST['agreement']) ? 1 : 0;

    // File Upload for ID Proof
    $id_proof_path = '';
    if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] == 0) {
        $max_size = 4 * 1024 * 1024; // 4MB
        if ($_FILES['id_proof']['size'] > $max_size) {
            $message = '<div class="alert alert-danger">ID Proof size exceeds the 4MB limit.</div>';
        } else {
            $upload_dir = 'uploads/id_proofs/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);
            $filename = time() . '_id_' . basename($_FILES['id_proof']['name']);
            if (move_uploaded_file($_FILES['id_proof']['tmp_name'], $upload_dir . $filename)) {
                $id_proof_path = 'uploads/id_proofs/' . $filename;
            }
        }
    }

    if (empty($full_name) || empty($phone_number) || empty($address) || empty($msg) || !$id_proof_path) {
        $message = '<div class="alert alert-danger">Please fill all fields and upload your ID proof.</div>';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
        $message = '<div class="alert alert-danger">Name should only contain letters and spaces.</div>';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone_number)) {
        $message = '<div class="alert alert-danger">Phone number must be exactly 10 digits.</div>';
    } elseif (!$agreement) {
        $message = '<div class="alert alert-danger">You must accept the Digital Adoption Agreement.</div>';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO adoption_applications (user_id, adoption_post_id, full_name, phone_number, address, message, id_proof_path, status, agreement_accepted, agreement_timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 1, CURRENT_TIMESTAMP)");
            $stmt->execute([$_SESSION['user_id'], $post_id, $full_name, $phone_number, $address, $msg, $id_proof_path]);

            $user_role = $_SESSION['user_role'];
            $dashboard_link = ($user_role == 'ngo') ? 'ngo/dashboard.php' : 'user/dashboard.php';
            $message = '<div class="alert alert-success">Application submitted successfully! <a href="' . $dashboard_link . '">Check Status</a></div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-lg border-0 overflow-hidden">
                <div class="row g-0">
                    <div class="col-md-4 bg-light d-none d-md-block">
                        <img src="<?= BASE_URL . ($pet['photo'] ?: 'assets/images/placeholder.jpg') ?>"
                            class="img-fluid h-100 object-fit-cover" alt="Pet Photo">

                    </div>
                    <div class="col-md-8">
                        <div class="card-body p-4 p-lg-5">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-primary-soft p-3 rounded-circle me-3">
                                    <i class="bi bi-heart-fill text-primary fs-3"></i>
                                </div>
                                <div>
                                    <h2 class="fw-bold mb-0">Adoption Application</h2>
                                    <p class="text-muted mb-0 d-flex align-items-center gap-2">
                                        Applying for: <?= htmlspecialchars($pet['pet_name']) ?>
                                        <button type="button" class="btn btn-xs btn-outline-primary border-0 p-0 shadow-none" 
                                                onclick="showFullImage('<?= BASE_URL . ($pet['photo'] ?: 'assets/images/placeholder.jpg') ?>', '<?= htmlspecialchars($pet['pet_name']) ?>')"
                                                title="View Full Photo">
                                            <i class="bi bi-zoom-in fs-5"></i>
                                        </button>
                                    </p>
                                </div>
                            </div>

                            <?= $message ?>

                            <form method="POST" enctype="multipart/form-data">
                                <h5 class="fw-bold border-bottom pb-2 mb-3">1. Personal Information</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-600">Full Name</label>
                                        <input type="text" name="full_name" class="form-control"
                                            placeholder="As per Govt ID" required pattern="[a-zA-Z\s]+"
                                            title="Only letters and spaces allowed"
                                            value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-600">Phone Number</label>
                                        <input type="text" name="phone_number" class="form-control"
                                            placeholder="Your contact number" required pattern="\d{10}" maxlength="10"
                                            title="Exactly 10 digits required"
                                            value="<?= isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : '' ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-600">Current Address</label>
                                        <textarea name="address" class="form-control" rows="2"
                                            placeholder="Full residential address"
                                            required><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
                                    </div>
                                </div>

                                <h5 class="fw-bold border-bottom pb-2 mb-3">2. Identity Verification</h5>
                                <div class="mb-4">
                                    <label class="form-label fw-600">Upload Government ID Proof <span
                                            class="text-danger">*</span></label>
                                    <input type="file" name="id_proof" class="form-control" accept="image/*,.pdf"
                                        required>
                                    <small class="text-muted">Upload Aadhaar, Voter ID, or DL. Max size: 4MB.</small>
                                </div>

                                <h5 class="fw-bold border-bottom pb-2 mb-3">3. Motivation</h5>
                                <div class="mb-4">
                                    <label class="form-label fw-600">Why do you want to adopt this pet?</label>
                                    <textarea name="message" class="form-control" rows="4"
                                        placeholder="Tell us about your home, family, and experience with pets."
                                        required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                                </div>

                                <h5 class="fw-bold border-bottom pb-2 mb-3">4. Digital Adoption Agreement</h5>
                                <div class="glass-card p-3 mb-4 bg-light border"
                                    style="height: 150px; overflow-y: scroll; font-size: 0.85rem;">
                                    <p><strong>Terms and Conditions:</strong></p>
                                    <ol>
                                        <li><strong>No Abandonment:</strong> I promise never to abandon this pet. If I
                                            can no longer care for them, I will contact the NGO/Shelter.</li>
                                        <li><strong>Full Responsibility:</strong> I agree to provide high-quality food,
                                            safe shelter, and required medical care (vaccinations, checkups).</li>
                                        <li><strong>Communication:</strong> I agree to inform the NGO immediately if the
                                            pet is lost, stolen, or passed away.</li>
                                        <li><strong>Follow-ups:</strong> I agree to provide quarterly updates (photo +
                                            geo-location) as requested by the system.</li>
                                        <li><strong>Legal Consent:</strong> I understand that failing to meet these
                                            terms may result in pet repossession and a ban from the platform.</li>
                                    </ol>
                                </div>
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" name="agreement" id="agreeCheck"
                                        required>
                                    <label class="form-check-label" for="agreeCheck">
                                        I have read and accept the Digital Adoption Agreement.
                                    </label>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold">Submit
                                        Application</button>
                                    <a href="adoption.php" class="btn btn-link text-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-primary-soft {
        background-color: rgba(16, 172, 132, 0.1);
    }

    .fw-600 {
        font-weight: 600;
    }
</style>

<!-- Image Viewer Modal -->
<div class="modal fade" id="imageViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="imageViewerTitle">Pet Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="full-image-container shadow-sm">
                    <img src="" id="fullPetImage" class="img-fluid" alt="Full pet image">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showFullImage(src, name) {
    document.getElementById('fullPetImage').src = src;
    document.getElementById('imageViewerTitle').innerText = name + "'s Photo";
    var myModal = new bootstrap.Modal(document.getElementById('imageViewerModal'));
    myModal.show();
}
</script>

<?php include 'includes/footer.php'; ?>