<?php
require_once '../config.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'ngo') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get NGO ID
$stmt_n = $conn->prepare("SELECT id, ngo_name, status FROM ngos WHERE user_id = ?");
$stmt_n->execute([$user_id]);
$my_ngo = $stmt_n->fetch();
$ngo_id = $my_ngo['id'];

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $req_id = $_GET['id'];
    if ($_GET['action'] == 'accept') {
        $stmt = $conn->prepare("UPDATE rescue_requests SET assigned_ngo_id = ?, status = 'in_progress' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$ngo_id, $req_id]);
        header("Location: dashboard.php");
        exit;
    }
}

// Handle Delete Adoption Post
if (isset($_GET['delete_post'])) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $post_id = $_GET['delete_post'];
    $user_id_check = $_SESSION['user_id'] ?? null;

    // Verify ownership before deleting
    $stmt = $conn->prepare("SELECT id FROM adoption_posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id_check]);
    if ($stmt->fetch()) {
        $conn->prepare("DELETE FROM adoption_posts WHERE id = ?")->execute([$post_id]);
        header("Location: dashboard.php?msg=Post removed successfully.");
        exit;
    }
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $req_id = $_POST['req_id'];
    $new_status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE rescue_requests SET status = ? WHERE id = ? AND assigned_ngo_id = ?");
    $stmt->execute([$new_status, $req_id, $ngo_id]);
    header("Location: dashboard.php?msg=Status updated successfully");
    exit;
}

// Handle Adoption Application Actions
if (isset($_GET['app_action']) && isset($_GET['app_id'])) {
    $app_id = $_GET['app_id'];
    $app_action = $_GET['app_action'];

    // Verify ownership (the post must belong to this NGO user)
    $stmt_v = $conn->prepare("SELECT a.*, p.pet_name, p.id as pet_id FROM adoption_applications a JOIN adoption_posts p ON a.adoption_post_id = p.id WHERE a.id = ?");
    $stmt_v->execute([$app_id]);
    $app_data = $stmt_v->fetch();

    if ($app_data && $conn->query("SELECT user_id FROM adoption_posts WHERE id = " . $app_data['pet_id'])->fetchColumn() == $user_id) {
        if ($app_action == 'approve') {
            try {
                $conn->beginTransaction();

                // 1. Approve this application
                $conn->prepare("UPDATE adoption_applications SET status = 'approved' WHERE id = ?")->execute([$app_id]);

                // 2. Reject all other pending applications for the same pet
                $conn->prepare("UPDATE adoption_applications SET status = 'rejected' WHERE adoption_post_id = ? AND id != ? AND status = 'pending'")->execute([$app_data['pet_id'], $app_id]);

                // 3. Mark pet as Adopted
                $conn->prepare("UPDATE adoption_posts SET adoption_status = 'adopted' WHERE id = ?")->execute([$app_data['pet_id']]);

                // 4. Record Ownership
                $conn->prepare("INSERT INTO pet_ownership (pet_id, adopter_id, status) VALUES (?, ?, 'active')")->execute([$app_data['pet_id'], $app_data['user_id']]);

                // 5. Schedule first Follow-up (4 Months from now)
                $due_date = date('Y-m-d', strtotime('+4 months'));
                $stmt_f = $conn->prepare("INSERT INTO ownership_followups (pet_id, adopter_id, status, due_date) VALUES (?, ?, 'pending', ?)");
                $stmt_f->execute([$app_data['pet_id'], $app_data['user_id'], $due_date]);

                $conn->commit();
                header("Location: dashboard.php?msg=Adoption Approved!");
                exit;
            } catch (Exception $e) {
                $conn->rollBack();
                header("Location: dashboard.php?msg=Error: " . urlencode($e->getMessage()));
                exit;
            }
        } elseif ($app_action == 'reject') {
            $conn->prepare("UPDATE adoption_applications SET status = 'rejected' WHERE id = ?")->execute([$app_id]);
            header("Location: dashboard.php?msg=Application rejected");
            exit;
        }
    }
}

include '../includes/header.php';
require_once '../includes/followup_reminders.php';

// Fetch Pending Requests (Available)
$stmt_pending = $conn->query("SELECT r.*, COALESCE(u.name, r.guest_name) as reporter_name, COALESCE(u.phone, r.guest_contact) as reporter_phone 
                              FROM rescue_requests r 
                              LEFT JOIN users u ON r.user_id = u.id 
                              WHERE r.status = 'pending' AND r.assigned_ngo_id IS NULL 
                              ORDER BY r.created_at DESC");
$pending_requests = $stmt_pending->fetchAll();

// Fetch My Active Rescues
$stmt_my = $conn->prepare("SELECT r.*, COALESCE(u.name, r.guest_name) as reporter_name, COALESCE(u.phone, r.guest_contact) as reporter_phone 
                           FROM rescue_requests r 
                           LEFT JOIN users u ON r.user_id = u.id 
                           WHERE r.assigned_ngo_id = ? 
                           ORDER BY r.created_at DESC");
$stmt_my->execute([$ngo_id]);
$my_rescues = $stmt_my->fetchAll();

// Fetch Applications for NGO's adoption posts
$stmt_app = $conn->prepare("SELECT a.*, p.pet_name, u.name as applicant_name, u.phone as applicant_phone 
                            FROM adoption_applications a 
                            JOIN adoption_posts p ON a.adoption_post_id = p.id 
                            JOIN users u ON a.user_id = u.id 
                            WHERE p.user_id = ? ORDER BY a.created_at DESC");
$stmt_app->execute([$user_id]);
$ngo_apps = $stmt_app->fetchAll();

// Fetch Adopted Pets for this NGO
$stmt_adopted = $conn->prepare("SELECT o.*, p.pet_name, p.photo, u.name as adopter_name, u.phone as adopter_phone, 
                                (SELECT status FROM ownership_followups WHERE pet_id = o.pet_id ORDER BY requested_at DESC LIMIT 1) as last_followup_status
                                FROM pet_ownership o 
                                JOIN adoption_posts p ON o.pet_id = p.id 
                                JOIN users u ON o.adopter_id = u.id 
                                WHERE p.user_id = ? AND o.status = 'active' ORDER BY o.adoption_date DESC");
$stmt_adopted->execute([$user_id]);
$my_adopted_pets = $stmt_adopted->fetchAll();

// Fetch Pending Follow-ups I need to RECEIVE
$stmt_rec = $conn->prepare("SELECT f.*, p.pet_name, u.name as adopter_name 
                           FROM ownership_followups f 
                           JOIN adoption_posts p ON f.pet_id = p.id 
                           JOIN users u ON f.adopter_id = u.id
                           WHERE p.user_id = ? AND f.status = 'pending'");
$stmt_rec->execute([$user_id]);
$followups_to_receive = $stmt_rec->fetchAll();

// Fetch Donations Received
$stmt_don = $conn->prepare("SELECT d.*, u.name as donor_name, u.email as donor_email, u.phone as donor_phone 
                             FROM donations d 
                             JOIN users u ON d.user_id = u.id 
                             WHERE d.ngo_id = ? ORDER BY d.date DESC");
$stmt_don->execute([$ngo_id]);
$received_donations = $stmt_don->fetchAll();

// Fetch NGO's own adoption posts
$stmt_posts = $conn->prepare("SELECT * FROM adoption_posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt_posts->execute([$user_id]);
$ngo_posts = $stmt_posts->fetchAll();

$total_received = array_sum(array_column($received_donations, 'amount'));

// Process simulated reminders
processFollowupReminders($conn);
?>

<div class="container mt-5">
    <?php if (isset($_GET['msg'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });

                Toast.fire({
                    icon: 'success',
                    title: '<?= htmlspecialchars($_GET['msg']) ?>'
                });
            });
        </script>
    <?php endif; ?>
    <div class="glass-card p-4 mb-5 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="fw-bold mb-1">HELLO, <span
                    class="text-gradient"><?= htmlspecialchars($my_ngo['ngo_name']) ?></span></h1>
            <p class="text-muted mb-0">Welcome back to your rescue command center.</p>
            <div class="mt-3">
                <a href="<?= BASE_URL ?>adoption_form.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i> Post Pet for Adoption
                </a>
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-success rounded-pill px-3 py-2 fs-6 mb-2 d-block">
                <i class="bi bi-patch-check-fill"></i> <?= ucfirst($my_ngo['status']) ?>
            </span>
            <div class="glass-card py-2 px-3 border-success" style="background: rgba(16, 172, 132, 0.1);">
                <small class="text-muted d-block"
                    style="font-size: 0.7rem; font-weight: bold; text-transform: uppercase;">Total Received</small>
                <h4 class="fw-bold text-success mb-0">₹<?= number_format($total_received, 2) ?></h4>
            </div>
        </div>
    </div>
    <!-- Follow-ups I need to RECEIVE -->
    <?php if (count($followups_to_receive) > 0): ?>
        <div class="glass-card p-4 mb-5 border-warning border-2" style="border-left-width: 5px;">
            <h5 class="fw-bold text-warning mb-3">
                <i class="bi bi-clock-history me-2"></i> Upcoming Post-Adoption Updates
            </h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr class="small">
                            <th>Pet Name</th>
                            <th>Adopter</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>View Updates</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($followups_to_receive as $fr): ?>
                            <?php
                            // Fetch completed updates for this follow-up
                            $stmt_updates = $conn->prepare("SELECT fu.*, f.due_date 
                                                           FROM followup_updates fu 
                                                           JOIN ownership_followups f ON fu.followup_id = f.id 
                                                           WHERE f.pet_id = ? 
                                                           ORDER BY fu.submitted_at DESC LIMIT 1");
                            $stmt_updates->execute([$fr['pet_id']]);
                            $latest_update = $stmt_updates->fetch();
                            ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($fr['pet_name']) ?></td>
                                <td><?= htmlspecialchars($fr['adopter_name']) ?></td>
                                <td>
                                    <?php
                                    $is_overdue = strtotime($fr['due_date']) < time();
                                    ?>
                                    <span class="<?= $is_overdue ? 'text-danger fw-bold' : '' ?>">
                                        <?= date('M d, Y', strtotime($fr['due_date'])) ?>
                                        <?php if ($is_overdue): ?> <i
                                                class="bi bi-exclamation-triangle-fill"></i><?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-warning-subtle text-warning border border-warning rounded-pill"
                                        style="font-size: 0.65rem;">Pending Update</span>
                                </td>
                                <td>
                                    <?php if ($latest_update): ?>
                                        <button class="btn btn-sm btn-info rounded-pill" data-bs-toggle="modal"
                                            data-bs-target="#updateModal<?= $fr['pet_id'] ?>">
                                            <i class="bi bi-eye"></i> View Last Update
                                        </button>

                                    <?php else: ?>
                                        <span class="text-muted small">No updates yet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    <!-- New Requests -->
    <div class="mb-5">
        <h4 class="fw-bold mb-3 d-flex align-items-center text-danger">
            <i class="bi bi-bell-fill me-2"></i> Rescue Alerts (<?= count($pending_requests) ?>)
        </h4>

        <?php if (count($pending_requests) > 0): ?>
            <div class="row g-4">
                <?php foreach ($pending_requests as $req): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="glass-card h-100 border-danger border-2" style="border-left-width: 5px;">
                            <?php if ($req['photo']): ?>
                                <div class="card-img-wrapper" style="height: 180px;">
                                    <img src="<?= BASE_URL . $req['photo'] ?>" class="w-100 h-100 object-fit-cover"
                                        alt="Injured Animal">
                                </div>
                            <?php endif; ?>
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title text-danger fw-bold mb-0">
                                        <?= htmlspecialchars($req['animal_type']) ?>
                                    </h5>
                                    <span
                                        class="badge bg-danger-subtle text-danger border border-danger"><?= htmlspecialchars($req['condition_type']) ?></span>
                                </div>

                                <p class="card-text text-muted small mb-3">
                                    <i class="bi bi-geo-alt-fill text-danger"></i>
                                    <?php if (!empty($req['latitude']) && !empty($req['longitude'])): ?>
                                        <?= $req['latitude'] ?>, <?= $req['longitude'] ?>
                                        <br>
                                        <a href="https://www.google.com/maps?q=<?= $req['latitude'] ?>,<?= $req['longitude'] ?>"
                                            target="_blank"
                                            class="text-danger small fw-bold text-decoration-none mt-1 d-inline-block">
                                            <i class="bi bi-map"></i> View on Map
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($req['location']) ?>
                                    <?php endif; ?>
                                </p>

                                <div class="bg-light rounded p-2 mb-3 small text-muted">
                                    <?= htmlspecialchars($req['description']) ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-white rounded-circle border p-1 me-2">
                                            <i class="bi bi-person-fill text-secondary"></i>
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars($req['reporter_name']) ?></small>
                                    </div>
                                    <a href="tel:<?= $req['reporter_phone'] ?>"
                                        class="btn btn-sm btn-outline-secondary rounded-pill">
                                        <i class="bi bi-telephone-fill"></i>
                                    </a>
                                </div>

                                <a href="dashboard.php?action=accept&id=<?= $req['id'] ?>"
                                    class="btn btn-emergency w-100 rounded-pill shadow-sm">
                                    <i class="bi bi-shield-plus"></i> ACCEPT RESCUE
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass-card p-4 text-center text-muted">
                <i class="bi bi-check-circle-fill fs-1 text-success mb-3 d-block"></i>
                <h5 class="fw-bold">All Quiet!</h5>
                <p class="mb-0">No pending rescue requests at the moment. Great job!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- My Active Rescues -->
    <div class="glass-card p-4">
        <div class="d-flex align-items-center mb-4">
            <div class="bg-primary text-white rounded-circle p-2 me-2">
                <i class="bi bi-list-check"></i>
            </div>
            <h5 class="mb-0 fw-bold">My Assigned Rescues</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Animal</th>
                        <th>Location</th>
                        <th>Reporter</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($my_rescues) > 0): ?>
                        <?php foreach ($my_rescues as $mr): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($mr['photo']): ?>
                                            <img src="<?= BASE_URL . $mr['photo'] ?>" class="rounded-circle me-2" width="40"
                                                height="40" style="object-fit:cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded-circle me-2 d-flex align-items-center justify-content-center"
                                                style="width:40px;height:40px;"><i class="bi bi-paw"></i></div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($mr['animal_type']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($mr['condition_type']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small><i class="bi bi-geo-alt text-muted"></i>
                                        <?php if (!empty($mr['latitude']) && !empty($mr['longitude'])): ?>
                                            <?= $mr['latitude'] ?>, <?= $mr['longitude'] ?>
                                            <a href="https://www.google.com/maps?q=<?= $mr['latitude'] ?>,<?= $mr['longitude'] ?>"
                                                target="_blank" class="text-primary ms-1" title="View on Map"><i
                                                    class="bi bi-map-fill"></i></a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($mr['location']) ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($mr['reporter_name']) ?>
                                    <div class="small text-muted"><?= $mr['reporter_phone'] ?></div>
                                </td>
                                <td>
                                    <?php if ($mr['status'] == 'rescued'): ?>
                                        <span class="badge bg-success rounded-pill">Rescued</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark rounded-pill">In Progress</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="req_id" value="<?= $mr['id'] ?>">
                                        <select name="status" class="form-select form-select-sm rounded-pill shadow-sm"
                                            style="width: 130px; border-color: rgba(16, 172, 132, 0.2);">
                                            <option value="in_progress" <?= $mr['status'] == 'in_progress' ? 'selected' : '' ?>>In
                                                Progress</option>
                                            <option value="rescued" <?= $mr['status'] == 'rescued' ? 'selected' : '' ?>>Rescued
                                            </option>
                                        </select>
                                        <button type="submit" name="update_status"
                                            class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">You haven't accepted any rescues yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- My Adoption Posts Section -->
    <div class="glass-card p-4 mt-5">
        <div class="d-flex align-items-center mb-4">
            <div class="bg-primary text-white rounded-circle p-2 me-2">
                <i class="bi bi-megaphone-fill"></i>
            </div>
            <h5 class="mb-0 fw-bold">My Adoption Posts</h5>
        </div>

        <?php if (count($ngo_posts) > 0): ?>
            <div class="row g-3">
                <?php foreach ($ngo_posts as $a): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 bg-white rounded shadow-sm d-flex align-items-center h-100 transition-hover border">
                            <img src="<?= BASE_URL . ($a['photo'] ?: 'assets/images/placeholder.jpg') ?>"
                                class="rounded me-3 object-fit-cover" width="70" height="70">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($a['pet_name']) ?></h6>
                                    <?php
                                    $statusClass = 'bg-secondary';
                                    if ($a['status'] == 'approved')
                                        $statusClass = 'bg-success';
                                    else if ($a['status'] == 'pending')
                                        $statusClass = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $statusClass ?> rounded-pill" style="font-size: 0.65rem;">
                                        <?= ucfirst($a['status']) ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <p class="small text-muted mb-0">
                                        <?= htmlspecialchars($a['pet_type']) ?> | <?= ucfirst($a['adoption_status']) ?>
                                    </p>
                                    <a href="dashboard.php?delete_post=<?= $a['id'] ?>" 
                                       class="text-danger small" 
                                       onclick="return confirm('Remove this post from the adoption page?')"
                                       title="Delete Post">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-4 text-muted border rounded border-dashed">
                <i class="bi bi-emoji-neutral fs-3 mb-2 d-block"></i>
                You haven't posted any pets for adoption yet.
                <br>
                <a href="<?= BASE_URL ?>adoption_form.php" class="btn btn-sm btn-primary mt-2 rounded-pill">Post One Now</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Adoption Applications -->
    <div class="glass-card p-4 mt-5">
        <div class="d-flex align-items-center mb-4">
            <div class="bg-vibrant-violet text-white rounded-circle p-2 me-2">
                <i class="bi bi-heart-fill text-white"></i>
            </div>
            <h5 class="mb-0 fw-bold">Adoption Applications</h5>
        </div>

        <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Pet</th>
                                <th>Applicant</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($ngo_apps) > 0): ?>
                                <?php foreach ($ngo_apps as $nap): ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?= htmlspecialchars($nap['pet_name']) ?></td>
                                        <td>
                                            <div class="fw-bold fs-6 mb-0"><?= htmlspecialchars($nap['full_name'] ?: $nap['applicant_name']) ?></div>
                                            <div class="text-muted small mb-0"><i class="bi bi-telephone-fill" style="font-size: 0.7rem;"></i> <?= htmlspecialchars($nap['phone_number'] ?: $nap['applicant_phone']) ?></div>
                                            <div class="text-muted" style="font-size: 0.7rem; line-height: 1.2;">
                                                <i class="bi bi-geo-alt-fill" style="font-size: 0.7rem;"></i> <?= htmlspecialchars($nap['address'] ?: 'No address provided') ?>
                                            </div>
                                        </td>
                                <td>
                                    <div class="bg-light p-2 rounded small text-muted" style="max-height: 80px; min-width: 150px; overflow-y: auto; font-size: 0.8rem;">
                                        <?= nl2br(htmlspecialchars($nap['message'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="badge rounded-pill bg-<?= $nap['status'] == 'approved' ? 'success' : ($nap['status'] == 'rejected' ? 'danger' : 'warning text-dark') ?>">
                                        <?= ucfirst($nap['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($nap['status'] == 'pending'): ?>
                                        <div class="d-flex gap-2">
                                            <a href="<?= BASE_URL . $nap['id_proof_path'] ?>" target="_blank"
                                                class="btn btn-sm btn-outline-info rounded-pill" title="View ID Proof"><i
                                                    class="bi bi-file-earmark-person"></i></a>
                                            <a href="dashboard.php?app_action=approve&app_id=<?= $nap['id'] ?>"
                                                class="btn btn-sm btn-success rounded-pill"
                                                onclick="return confirm('Are you sure you want to approve this adoption and finalize the agreement?')"><i
                                                    class="bi bi-check"></i></a>
                                            <a href="dashboard.php?app_action=reject&app_id=<?= $nap['id'] ?>"
                                                class="btn btn-sm btn-danger rounded-pill"><i class="bi bi-x"></i></a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No applications received yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Adopted Pets Tracking -->
    <div class="glass-card p-4 mt-5">
        <div class="d-flex align-items-center mb-4">
            <div class="bg-success text-white rounded-circle p-2 me-2">
                <i class="bi bi-house-check-fill"></i>
            </div>
            <h5 class="mb-0 fw-bold">Adopted Pets Tracking</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Pet</th>
                        <th>Adopter</th>
                        <th>Adoption Date</th>
                        <th>Follow-up</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($my_adopted_pets) > 0): ?>
                        <?php foreach ($my_adopted_pets as $pet): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= BASE_URL . $pet['photo'] ?>" class="rounded me-2" width="40" height="40"
                                            style="object-fit:cover;">
                                        <span class="fw-bold"><?= htmlspecialchars($pet['pet_name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($pet['adopter_name']) ?>
                                    <div class="small text-muted"><?= htmlspecialchars($pet['adopter_phone']) ?></div>
                                </td>
                                <td><?= date('M d, Y', strtotime($pet['adoption_date'])) ?></td>
                                <td>
                                    <?php if ($pet['last_followup_status'] == 'pending'): ?>
                                        <span class="badge bg-warning text-dark rounded-pill">Requested</span>
                                    <?php elseif ($pet['last_followup_status'] == 'completed'): ?>
                                        <span class="badge bg-success rounded-pill">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="dashboard.php?req_followup=1&pet_id=<?= $pet['pet_id'] ?>&adopter_id=<?= $pet['adopter_id'] ?>"
                                        class="btn btn-sm btn-primary rounded-pill <?= $pet['last_followup_status'] == 'pending' ? 'disabled' : '' ?>">
                                        Request Update
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No pets adopted yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Donation History -->
    <div class="glass-card p-4 mt-5 mb-5">
        <div class="d-flex align-items-center mb-4">
            <div class="bg-vibrant-pink text-white rounded-circle p-2 me-2">
                <i class="bi bi-piggy-bank-fill text-white"></i>
            </div>
            <h5 class="mb-0 fw-bold">Donation Transaction History</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Donor Name</th>
                        <th>Contact Info</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($received_donations) > 0): ?>
                        <?php foreach ($received_donations as $don): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($don['donor_name']) ?></td>
                                <td>
                                    <div class="small"><?= htmlspecialchars($don['donor_email']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($don['donor_phone']) ?></div>
                                </td>
                                <td><?= date('M d, Y', strtotime($don['date'])) ?></td>
                                <td class="text-end text-success fw-bold">
                                    ₹<?= number_format($don['amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No donations received yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-vibrant-pink {
        background-color: #ff3366;
    }
</style>
<!-- Modals for Updates -->
<?php foreach ($followups_to_receive as $fr): ?>
    <?php
    $stmt_updates = $conn->prepare("SELECT fu.*, f.due_date 
                                   FROM followup_updates fu 
                                   JOIN ownership_followups f ON fu.followup_id = f.id 
                                   WHERE f.pet_id = ? 
                                   ORDER BY fu.submitted_at DESC LIMIT 1");
    $stmt_updates->execute([$fr['pet_id']]);
    $latest_update = $stmt_updates->fetch();
    ?>
    <?php if ($latest_update): ?>
        <div class="modal fade" id="updateModal<?= $fr['pet_id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">Latest Update:
                            <?= htmlspecialchars($fr['pet_name']) ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <img src="<?= BASE_URL . $latest_update['photo_path'] ?>" class="img-fluid rounded shadow"
                                style="width: 100%; height: auto;">
                        </div>
                        <div class="mb-3">
                            <strong>Message:</strong>
                            <p class="text-muted"><?= htmlspecialchars($latest_update['message']) ?>
                            </p>
                        </div>
                        <div class="mb-3">
                            <strong>Location:</strong>
                            <p class="text-muted">
                                <i class="bi bi-geo-alt-fill text-danger"></i>
                                Lat: <?= $latest_update['latitude'] ?>,
                                Lng: <?= $latest_update['longitude'] ?>
                                <?php if ($latest_update['latitude'] && $latest_update['longitude']): ?>
                                    <br>
                                    <a href="https://www.google.com/maps?q=<?= $latest_update['latitude'] ?>,<?= $latest_update['longitude'] ?>"
                                        target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="bi bi-map"></i> View on Map
                                    </a>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <strong>Submitted:</strong>
                            <p class="text-muted">
                                <?= date('M d, Y h:i A', strtotime($latest_update['submitted_at'])) ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <a href="<?= BASE_URL . $latest_update['photo_path'] ?>" target="_blank"
                                class="btn btn-secondary btn-sm">View Original Image</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>