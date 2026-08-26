<?php
require_once '../config.php';
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

// Handle Approvals
if (isset($_GET['approve_ngo'])) {
    $conn->prepare("UPDATE ngos SET status='approved' WHERE id=?")->execute([$_GET['approve_ngo']]);
    echo "<script>window.location.href='dashboard.php';</script>";
}
if (isset($_GET['reject_ngo'])) {
    $conn->prepare("UPDATE ngos SET status='rejected' WHERE id=?")->execute([$_GET['reject_ngo']]);
    echo "<script>window.location.href='dashboard.php';</script>";
}
if (isset($_GET['approve_post'])) {
    $conn->prepare("UPDATE adoption_posts SET status='approved' WHERE id=?")->execute([$_GET['approve_post']]);
    echo "<script>window.location.href='dashboard.php';</script>";
}
if (isset($_GET['delete_post'])) {
    $conn->prepare("DELETE FROM adoption_posts WHERE id=?")->execute([$_GET['delete_post']]);
    echo "<script>window.location.href='dashboard.php';</script>";
}
if (isset($_GET['delete_vet'])) {
    $conn->prepare("DELETE FROM vet_salon WHERE id=?")->execute([$_GET['delete_vet']]);
    echo "<script>window.location.href='dashboard.php';</script>";
}

// Handle Penalties/Bans
if (isset($_GET['ban_user'])) {
    $conn->prepare("UPDATE users SET is_banned = 1 WHERE id = ?")->execute([$_GET['ban_user']]);
    echo "<script>alert('User banned!'); window.location.href='dashboard.php';</script>";
}

// Handle Adoption Application Approvals
if (isset($_GET['approve_app'])) {
    $conn->prepare("UPDATE adoption_applications SET status='approved' WHERE id=?")->execute([$_GET['approve_app']]);
    echo "<script>window.location.href='dashboard.php';</script>";
}
if (isset($_GET['reject_app'])) {
    $conn->prepare("UPDATE adoption_applications SET status='rejected' WHERE id=?")->execute([$_GET['reject_app']]);
    echo "<script>window.location.href='dashboard.php';</script>";
}


// Handle Vet Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_vet'])) {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $location = $_POST['location'];
    $phone = $_POST['phone'];
    $services = $_POST['services'];

    // Validation: Phone number must be exactly 10 digits
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo "<script>alert('Invalid phone number. It must be exactly 10 digits.'); window.history.back();</script>";
        exit;
    }

    $conn->prepare("INSERT INTO vet_salon (name, type, location, phone, services) VALUES (?, ?, ?, ?, ?)")
        ->execute([$name, $type, $location, $phone, $services]);
    echo "<script>window.location.href='dashboard.php';</script>";
}

// Fetch Data
$pending_ngos = $conn->query("SELECT n.*, u.email, u.phone, u.name as registrar_name FROM ngos n JOIN users u ON n.user_id = u.id WHERE n.status='pending'")->fetchAll();
$pending_posts = $conn->query("SELECT p.*, u.name as poster_name, u.email as poster_email FROM adoption_posts p JOIN users u ON p.user_id = u.id WHERE p.status='pending'")->fetchAll();
$pending_apps = $conn->query("SELECT a.*, p.pet_name, u.name as applicant_name, u.email as applicant_email FROM adoption_applications a JOIN adoption_posts p ON a.adoption_post_id = p.id JOIN users u ON a.user_id = u.id WHERE a.status='pending'")->fetchAll();
$vets = $conn->query("SELECT * FROM vet_salon ORDER BY id DESC")->fetchAll();
?>

<div class="container mt-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="fw-bold text-gradient">Admin Dashboard</h1>
        <span class="badge bg-dark rounded-pill px-3 py-2">System Administrator</span>
    </div>

    <div class="row">
        <!-- Pending NGOs -->
        <div class="col-md-6 mb-4">
            <div class="glass-card h-100 p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-warning text-white rounded-circle p-2 me-2">
                        <i class="bi bi-building"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Pending NGO Approvals</h5>
                </div>

                <?php if (count($pending_ngos) > 0): ?>
                    <ul class="list-group list-group-flush bg-transparent">
                        <?php foreach ($pending_ngos as $ngo): ?>
                            <li class="list-group-item bg-transparent border-bottom py-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="text-primary fs-5">
                                            <?= htmlspecialchars($ngo['ngo_name']) ?>
                                        </strong>
                                        <div class="text-muted small mt-1">
                                            <i class="bi bi-geo-alt-fill me-1"></i>
                                            <strong>Address:</strong> <?= htmlspecialchars($ngo['address']) ?>,
                                            <?= htmlspecialchars($ngo['city']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-person-fill me-1"></i>
                                            <strong>Registrar:</strong> <?= htmlspecialchars($ngo['registrar_name']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-gear-fill me-1"></i>
                                            <strong>Services:</strong> <?= htmlspecialchars($ngo['services']) ?>
                                        </div>
                                        <div class="row mt-2 g-2">
                                            <div class="col-md-6 text-muted small">
                                                <i class="bi bi-envelope-fill me-1"></i>
                                                <strong>Email:</strong> <?= htmlspecialchars($ngo['email']) ?>
                                            </div>
                                            <div class="col-md-6 text-muted small">
                                                <i class="bi bi-telephone-fill me-1"></i>
                                                <strong>Phone:</strong> <?= htmlspecialchars($ngo['phone']) ?>
                                            </div>
                                            <div class="col-12 text-muted small mt-1">
                                                <i class="bi bi-info-circle-fill me-1"></i>
                                                <strong>Mission:</strong> <?= htmlspecialchars($ngo['description']) ?>
                                            </div>
                                        </div>
                                        <?php if ($ngo['registration_certificate']): ?>
                                            <div class="mt-3">
                                                <a href="<?= BASE_URL . $ngo['registration_certificate'] ?>" target="_blank"
                                                    class="btn btn-sm btn-info py-1 px-3 rounded-pill text-white shadow-sm">
                                                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> View Registration Certificate
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="dashboard.php?approve_ngo=<?= $ngo['id'] ?>"
                                            class="btn btn-sm btn-success rounded-pill px-3" title="Approve">
                                            <i class="bi bi-check-lg me-1"></i> Approve
                                        </a>
                                        <a href="dashboard.php?reject_ngo=<?= $ngo['id'] ?>"
                                            class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Reject">
                                            <i class="bi bi-x-lg me-1"></i> Reject
                                        </a>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-check-circle fs-4 mb-2 d-block"></i>
                        No pending NGO approvals.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Adoption Posts -->
        <div class="col-md-6 mb-4">
            <div class="glass-card h-100 p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-info text-white rounded-circle p-2 me-2">
                        <i class="bi bi-card-image"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Pending Adoption Posts</h5>
                </div>

                <?php if (count($pending_posts) > 0): ?>
                    <ul class="list-group list-group-flush bg-transparent">
                        <?php foreach ($pending_posts as $post): ?>
                            <li class="list-group-item bg-transparent border-bottom">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>
                                            <?= htmlspecialchars($post['pet_name']) ?>
                                        </strong>
                                        <span class="badge bg-light text-dark border"><?= $post['pet_type'] ?></span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="dashboard.php?approve_post=<?= $post['id'] ?>"
                                            class="btn btn-sm btn-success rounded-pill" title="Approve"><i
                                                class="bi bi-check-lg"></i></a>
                                        <a href="dashboard.php?delete_post=<?= $post['id'] ?>"
                                            class="btn btn-sm btn-danger rounded-pill" title="Delete"><i
                                                class="bi bi-trash"></i></a>
                                    </div>
                                </div>
                                <div class="bg-light bg-opacity-50 p-2 rounded mt-2" style="max-height: 100px; overflow-y: auto;">
                                    <small class="text-muted d-block" style="font-size: 0.85rem;">
                                        <?= nl2br(htmlspecialchars($post['description'])) ?>
                                    </small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted">
                                        Posted by: <strong><?= htmlspecialchars($post['poster_name']) ?></strong> (<?= htmlspecialchars($post['poster_email']) ?>)
                                    </small>
                                    <?php if ($post['photo']): ?>
                                        <a href="<?= BASE_URL . $post['photo'] ?>" target="_blank"
                                            class="btn btn-xs btn-outline-primary py-0 px-2 rounded-pill">View Photo</a>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-images fs-4 mb-2 d-block"></i>
                        No pending adoption posts.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Adoption Applications -->
        <div class="col-md-12 mb-4">
            <div class="glass-card p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-vibrant-violet text-white rounded-circle p-2 me-2">
                        <i class="bi bi-heart-fill"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Pending Adoption Applications</h5>
                </div>

                <?php if (count($pending_apps) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Pet</th>
                                    <th>Applicant</th>
                                    <th>Reason / Message</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_apps as $app): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($app['pet_name']) ?></td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($app['applicant_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($app['applicant_email']) ?></small>
                                        </td>
                                        <td>
                                            <div class="bg-light p-2 rounded small text-muted" style="max-height: 80px; overflow-y: auto;">
                                                <?= nl2br(htmlspecialchars($app['message'])) ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="<?= BASE_URL . $app['id_proof_path'] ?>" target="_blank" 
                                                   class="btn btn-sm btn-outline-info rounded-pill px-3" title="View ID Proof">
                                                    ID Proof
                                                </a>
                                                <a href="dashboard.php?approve_app=<?= $app['id'] ?>" 
                                                   class="btn btn-sm btn-success rounded-pill px-3">
                                                    Approve
                                                </a>
                                                <a href="dashboard.php?reject_app=<?= $app['id'] ?>" 
                                                   class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                    Reject
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-heartbreak fs-4 mb-2 d-block"></i>
                        No pending adoption applications.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>



    <!-- Manage Vets -->
    <div class="glass-card p-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <div class="bg-dark text-white rounded-circle p-2 me-2">
                    <i class="bi bi-briefcase"></i>
                </div>
                <h5 class="mb-0 fw-bold">Manage Vets & Salons</h5>
            </div>
            <button class="btn btn-sm btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addVetModal">
                <i class="bi bi-plus-lg"></i> Add New
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Phone</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vets as $v): ?>
                        <tr>
                            <td class="fw-bold">
                                <?= htmlspecialchars($v['name']) ?>
                            </td>
                            <td>
                                <?php if ($v['type'] == 'vet'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success">Vet Clinic</span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary">Pet Salon</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="bi bi-geo-alt text-muted"></i> <?= htmlspecialchars($v['location']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($v['phone']) ?>
                            </td>
                            <td class="text-end">
                                <a href="dashboard.php?delete_vet=<?= $v['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-pill"
                                   onclick="return confirm('Are you sure you want to remove this entry?')">
                                    <i class="bi bi-trash me-1"></i> Remove
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Vet Modal -->
<div class="modal fade" id="addVetModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Vet / Salon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="add_vet" value="1">
                <div class="mb-2"><label>Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-2"><label>Type</label>
                    <select name="type" class="form-select">
                        <option value="vet">Vet Clinic</option>
                        <option value="salon">Pet Salon</option>
                    </select>
                </div>
                <div class="mb-2"><label>Location</label><input type="text" name="location" class="form-control"
                        required></div>
                <div class="mb-2"><label>Phone</label>
                    <input type="tel" name="phone" class="form-control" required pattern="[0-9]{10}"
                        title="Please enter a 10-digit phone number"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                    <small class="text-muted">Format: 10 digits (e.g., 9876543210)</small>
                </div>
                <div class="mb-2"><label>Services</label><textarea name="services" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>


<style>
    .bg-vibrant-violet {
        background-color: #8a2be2;
    }
    .text-gradient {
        background: linear-gradient(45deg, #0d6efd, #8a2be2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }
</style>

<?php include '../includes/footer.php'; ?>