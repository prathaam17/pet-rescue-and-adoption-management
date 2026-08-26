<?php
require_once '../config.php';

// Handle Follow-up Submission - Processed before Header
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_followup'])) {

    // Ensure session is started for user_id check (config might start it, but be safe)
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }

    $followup_id = $_POST['followup_id'];
    $message = trim($_POST['followup_message']);
    $lat = $_POST['latitude'] ?? null;
    $lng = $_POST['longitude'] ?? null;

    // File Upload for Follow-up Photo
    $photo_path = '';
    if (isset($_FILES['followup_photo']) && $_FILES['followup_photo']['error'] == 0) {
        $max_size = 4 * 1024 * 1024; // 4MB
        if ($_FILES['followup_photo']['size'] > $max_size) {
            header("Location: dashboard.php?msg=Image size exceeds the 4MB limit.");
            exit;
        } else {
            $upload_dir = '../uploads/followups/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);
            $filename = time() . '_fu_' . basename($_FILES['followup_photo']['name']);
            if (move_uploaded_file($_FILES['followup_photo']['tmp_name'], $upload_dir . $filename)) {
                $photo_path = 'uploads/followups/' . $filename;
            }
        }
    }

    if ($photo_path) {
        try {
            $conn->beginTransaction();

            // Insert update
            $stmt = $conn->prepare("INSERT INTO followup_updates (followup_id, photo_path, latitude, longitude, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$followup_id, $photo_path, $lat, $lng, $message]);

            // Mark follow-up as completed
            $stmt = $conn->prepare("UPDATE ownership_followups SET status = 'completed' WHERE id = ?");
            $stmt->execute([$followup_id]);

            // Schedule the NEXT Follow-up (4 Months from the previous due date or today)
            $stmt_get = $conn->prepare("SELECT pet_id, adopter_id FROM ownership_followups WHERE id = ?");
            $stmt_get->execute([$followup_id]);
            $old_fu = $stmt_get->fetch();

            $next_due_date = date('Y-m-d', strtotime('+4 months'));
            $stmt_next = $conn->prepare("INSERT INTO ownership_followups (pet_id, adopter_id, status, due_date) VALUES (?, ?, 'pending', ?)");
            $stmt_next->execute([$old_fu['pet_id'], $old_fu['adopter_id'], $next_due_date]);

            $conn->commit();
            header("Location: dashboard.php?msg=Follow-up submitted successfully! Next update scheduled for " . date('M d, Y', strtotime($next_due_date)));
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            header("Location: dashboard.php?msg=Error: " . $e->getMessage());
            exit;
        }
    } else {
        header("Location: dashboard.php?msg=Please upload a photo for the follow-up.");
        exit;
    }
}

// Handle Application Approval/Rejection - Processed before Header
if (isset($_GET['action']) && isset($_GET['app_id'])) {

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $user_id_check = $_SESSION['user_id'] ?? null;

    $action = $_GET['action'];
    $app_id = $_GET['app_id'];

    // Verify ownership
    $stmt_v = $conn->prepare("SELECT p.user_id FROM adoption_applications a JOIN adoption_posts p ON a.adoption_post_id = p.id WHERE a.id = ?");
    $stmt_v->execute([$app_id]);
    $owner = $stmt_v->fetchColumn();

    if ($owner == $user_id_check) {
        $stmt_app_data = $conn->prepare("SELECT a.*, p.pet_name FROM adoption_applications a JOIN adoption_posts p ON a.adoption_post_id = p.id WHERE a.id = ?");
        $stmt_app_data->execute([$app_id]);
        $app_data = $stmt_app_data->fetch();

        if ($action == 'approve' && $app_data) {
            try {
                $conn->beginTransaction();

                // 1. Approve application
                $conn->prepare("UPDATE adoption_applications SET status = 'approved' WHERE id = ?")->execute([$app_id]);

                // 2. Reject others
                $conn->prepare("UPDATE adoption_applications SET status = 'rejected' WHERE adoption_post_id = ? AND id != ? AND status = 'pending'")->execute([$app_data['adoption_post_id'], $app_id]);

                // 3. Update Pet Status
                $conn->prepare("UPDATE adoption_posts SET adoption_status = 'adopted' WHERE id = ?")->execute([$app_data['adoption_post_id']]);

                // 4. Record Ownership
                $conn->prepare("INSERT INTO pet_ownership (pet_id, adopter_id, status) VALUES (?, ?, 'active')")->execute([$app_data['adoption_post_id'], $app_data['user_id']]);

                // 5. Schedule first Follow-up
                $due_date = date('Y-m-d', strtotime('+4 months'));
                $conn->prepare("INSERT INTO ownership_followups (pet_id, adopter_id, status, due_date) VALUES (?, ?, 'pending', ?)")->execute([$app_data['adoption_post_id'], $app_data['user_id'], $due_date]);

                $conn->commit();
                header("Location: dashboard.php?msg=Adoption approved and finalized!");
                exit;
            } catch (Exception $e) {
                $conn->rollBack();
                header("Location: dashboard.php?msg=Error: " . $e->getMessage());
                exit;
            }
        } elseif ($action == 'reject') {
            $conn->prepare("UPDATE adoption_applications SET status = 'rejected' WHERE id = ?")->execute([$app_id]);
            header("Location: dashboard.php?msg=Application rejected");
            exit;
        }
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
        header("Location: dashboard.php?msg=Post removed successfully from adoption page.");
        exit;
    }
}

// Custom header handling for sub-folder if needed, or just use ../includes/header.php
// But header.php uses BASE_URL so it should be fine.
include '../includes/header.php';
require_once '../includes/followup_reminders.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Rescues
$stmt = $conn->prepare("SELECT * FROM rescue_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$rescues = $stmt->fetchAll();

// 2. Fetch My Adoption Posts
$stmt = $conn->prepare("SELECT * FROM adoption_posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$adoptions = $stmt->fetchAll();

// 3. Fetch My Adopted Pets
$stmt = $conn->prepare("SELECT o.*, p.pet_name, p.photo 
                       FROM pet_ownership o 
                       JOIN adoption_posts p ON o.pet_id = p.id 
                       WHERE o.adopter_id = ? AND o.status = 'active'
                       ORDER BY o.adoption_date DESC");
$stmt->execute([$user_id]);
$my_pets = $stmt->fetchAll();

// 4. Fetch Donations Made
$stmt = $conn->prepare("SELECT d.*, n.ngo_name 
                       FROM donations d 
                       JOIN ngos n ON d.ngo_id = n.id 
                       WHERE d.user_id = ? ORDER BY d.date DESC");
$stmt->execute([$user_id]);
$donations = $stmt->fetchAll();

// 5. Fetch Appointments
$stmt = $conn->prepare("SELECT a.*, vs.name as vet_name, vs.type 
                       FROM appointments a 
                       JOIN vet_salon vs ON a.vet_salon_id = vs.id 
                       WHERE a.user_id = ? ORDER BY a.date DESC, a.time DESC");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll();

// 6. Fetch Applications RECEIVED
$stmt = $conn->prepare("SELECT a.*, p.pet_name, u.name as applicant_name, u.phone as applicant_phone 
                       FROM adoption_applications a 
                       JOIN adoption_posts p ON a.adoption_post_id = p.id 
                       JOIN users u ON a.user_id = u.id 
                       WHERE p.user_id = ? ORDER BY a.created_at DESC");
$stmt->execute([$user_id]);
$apps_received = $stmt->fetchAll();

// 7. Fetch Applications SENT
$stmt = $conn->prepare("SELECT a.*, p.pet_name, p.photo 
                       FROM adoption_applications a 
                       JOIN adoption_posts p ON a.adoption_post_id = p.id 
                       WHERE a.user_id = ? ORDER BY a.created_at DESC");
$stmt->execute([$user_id]);
$apps_sent = $stmt->fetchAll();

// 8. Fetch Pending Follow-ups I need to SUBMIT
$stmt = $conn->prepare("SELECT f.*, p.pet_name 
                       FROM ownership_followups f 
                       JOIN adoption_posts p ON f.pet_id = p.id 
                       WHERE f.adopter_id = ? AND f.status = 'pending'");
$stmt->execute([$user_id]);
$pending_followups = $stmt->fetchAll();

// 9. Fetch Follow-ups I need to RECEIVE
$stmt = $conn->prepare("SELECT f.*, p.pet_name, u.name as adopter_name 
                       FROM ownership_followups f 
                       JOIN adoption_posts p ON f.pet_id = p.id 
                       JOIN users u ON f.adopter_id = u.id
                       WHERE p.user_id = ? AND f.status = 'pending'");
$stmt->execute([$user_id]);
$followups_to_receive = $stmt->fetchAll();

// 10. Stats Calculation
$total_donated = array_sum(array_column($donations, 'amount'));
$active_rescues = count(array_filter($rescues, function ($r) {
    return $r['status'] != 'rescued';
}));
$pets_saved = count(array_filter($rescues, function ($r) {
    return $r['status'] == 'rescued';
}));
$pets_adopted_by_me = count($my_pets);

// Process simulated reminders on load
processFollowupReminders($conn);
?>

<div class="container mt-5 mb-5">
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

    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 mb-4">
            <div class="glass-card p-4 text-center mb-4">
                <div class="position-relative d-inline-block mb-3">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name']) ?>&background=random&color=fff&size=128"
                        class="rounded-circle shadow-sm border border-4 border-white" width="100">
                    <span
                        class="position-absolute bottom-0 end-0 p-2 bg-success border border-light rounded-circle"></span>
                </div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($_SESSION['user_name']) ?></h5>
                <p class="text-muted small mb-3">Pet Lover & Guardian</p>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <div class="bg-light rounded p-2 flex-grow-1">
                        <div class="fw-bold text-primary">₹<?= number_format($total_donated) ?></div>
                        <div class="small text-muted" style="font-size: 0.6rem;">Donated</div>
                    </div>
                    <div class="bg-light rounded p-2 flex-grow-1">
                        <div class="fw-bold text-success"><?= $pets_saved ?></div>
                        <div class="small text-muted" style="font-size: 0.6rem;">Saved</div>
                    </div>
                    <div class="bg-light rounded p-2 flex-grow-1">
                        <div class="fw-bold text-info"><?= $pets_adopted_by_me ?></div>
                        <div class="small text-muted" style="font-size: 0.6rem;">Adopted</div>
                    </div>
                </div>


            </div>

            <div class="glass-card p-0 overflow-hidden">
                <div class="list-group list-group-flush bg-transparent">
                    <a href="#"
                        class="list-group-item list-group-item-action bg-transparent border-bottom px-4 py-3 active fw-bold text-primary">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="<?= BASE_URL ?>user/emergency.php"
                        class="list-group-item list-group-item-action bg-transparent border-bottom px-4 py-3 text-danger fw-bold">
                        <i class="bi bi-exclamation-circle me-2"></i> Report Rescue
                    </a>
                    <a href="<?= BASE_URL ?>adoption_form.php"
                        class="list-group-item list-group-item-action bg-transparent border-bottom px-4 py-3 text-muted hov-primary">
                        <i class="bi bi-paw me-2"></i> Post for Adoption
                    </a>
                    <a href="<?= BASE_URL ?>vets.php"
                        class="list-group-item list-group-item-action bg-transparent px-4 py-3 text-muted hov-primary">
                        <i class="bi bi-calendar-check me-2"></i> Book Vet
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">

            <!-- Follow-ups I need to RECEIVE -->
            <?php if (count($followups_to_receive) > 0): ?>
                <div class="glass-card p-4 mb-4 border-warning border-2" style="border-left-width: 5px;">
                    <h5 class="fw-bold text-warning mb-3">
                        <i class="bi bi-clock-history me-2"></i> Upcoming Updates (from Adopters)
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr class="text-muted small">
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
                                    // Fetch completed updates for this pet
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
                                            <span
                                                class="badge bg-warning-subtle text-warning border border-warning rounded-pill"
                                                style="font-size: 0.6rem;">Awaiting Update</span>
                                        </td>
                                        <td>
                                            <?php if ($latest_update): ?>
                                                <button class="btn btn-xs btn-info rounded-pill" data-bs-toggle="modal"
                                                    data-bs-target="#userUpdateModal<?= $fr['pet_id'] ?>">
                                                    <i class="bi bi-eye"></i> View
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

            <!-- Pending Follow-ups Section -->
            <?php if (count($pending_followups) > 0): ?>
                <div class="glass-card p-4 mb-4 border-info border-2" style="border-left-width: 5px;">
                    <h5 class="fw-bold text-info mb-3">
                        <i class="bi bi-camera-fill me-2"></i> Action Required: Post-Adoption Follow-ups
                    </h5>
                    <?php foreach ($pending_followups as $pf): ?>
                        <div class="bg-info bg-opacity-10 p-3 rounded mb-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1">Follow-up for <?= htmlspecialchars($pf['pet_name']) ?></h6>
                                    <small class="text-danger fw-600">Due by:
                                        <?= date('M d, Y', strtotime($pf['due_date'])) ?></small>
                                </div>
                                <button class="btn btn-primary btn-sm rounded-pill" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#pf_<?= $pf['id'] ?>">
                                    Submit Update
                                </button>
                            </div>
                            <div class="collapse" id="pf_<?= $pf['id'] ?>">
                                <form method="POST" enctype="multipart/form-data" class="bg-white p-3 rounded shadow-sm">
                                    <input type="hidden" name="followup_id" value="<?= $pf['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Recent Photo of
                                            <?= htmlspecialchars($pf['pet_name']) ?></label>
                                        <input type="file" name="followup_photo" class="form-control form-control-sm"
                                            accept="image/*" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Message / Health Update</label>
                                        <textarea name="followup_message" class="form-control form-control-sm" rows="2"
                                            placeholder="How is your pet doing?" required></textarea>
                                    </div>
                                    <div class="row gx-2 mb-3">
                                        <div class="col">
                                            <input type="text" name="latitude" id="lat_<?= $pf['id'] ?>"
                                                class="form-control form-control-sm" placeholder="Lat" readonly required>
                                        </div>
                                        <div class="col">
                                            <input type="text" name="longitude" id="lng_<?= $pf['id'] ?>"
                                                class="form-control form-control-sm" placeholder="Lng" readonly required>
                                        </div>
                                        <div class="col-auto">
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                onclick="getLocation(<?= $pf['id'] ?>)">
                                                <i class="bi bi-geo-alt"></i> Tag
                                            </button>
                                        </div>
                                    </div>
                                    <button type="submit" name="submit_followup"
                                        class="btn btn-success btn-sm w-100 rounded-pill fw-bold">Send Update</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <script>
                    function getLocation(id) {
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition(function (position) {
                                document.getElementById('lat_' + id).value = position.coords.latitude;
                                document.getElementById('lng_' + id).value = position.coords.longitude;
                            }, function (error) {
                                alert("Error getting location: " + error.message);
                            });
                        } else {
                            alert("Geolocation is not supported by this browser.");
                        }
                    }
                </script>
            <?php endif; ?>

            <!-- My Adopted Pets Section -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-success text-white rounded-circle p-2 me-2">
                        <i class="bi bi-house-heart-fill"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">My Adopted Pets</h5>
                </div>

                <?php if (count($my_pets) > 0): ?>
                    <div class="row g-3">
                        <?php foreach ($my_pets as $mp): ?>
                            <div class="col-md-6">
                                <div class="bg-light bg-opacity-50 p-3 rounded-4 border d-flex align-items-center">
                                    <img src="<?= BASE_URL . $mp['photo'] ?>"
                                        class="rounded-circle object-fit-cover me-3 shadow-sm" width="60" height="60">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($mp['pet_name']) ?></h6>
                                        <p class="text-muted small mb-0">Adopted on
                                            <?= date('M d, Y', strtotime($mp['adoption_date'])) ?>
                                        </p>
                                        <small
                                            class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill"
                                            style="font-size: 0.6rem;">Forever Home</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted border rounded border-dashed">
                        <i class="bi bi-heart-pulse fs-3 mb-2 d-block"></i>
                        No pets adopted yet. Start your journey today!
                    </div>
                <?php endif; ?>
            </div>

            <!-- Rescue Requests Section -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-danger text-white rounded-circle p-2 me-2">
                        <i class="bi bi-life-preserver"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">My Rescue Requests</h5>
                </div>

                <?php if (count($rescues) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Animal</th>
                                    <th>Status</th>
                                    <th>Assigned NGO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rescues as $r): ?>
                                    <tr>
                                        <td class="text-muted">
                                            <?= date('M d, Y', strtotime($r['created_at'])) ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($r['animal_type']) ?></div>
                                            <span
                                                class="badge bg-light text-secondary border"><?= htmlspecialchars($r['condition_type']) ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $badge = 'bg-secondary';
                                            $icon = 'bi-hourglass';
                                            if ($r['status'] == 'in_progress') {
                                                $badge = 'bg-warning text-dark';
                                                $icon = 'bi-arrow-repeat';
                                            }
                                            if ($r['status'] == 'rescued') {
                                                $badge = 'bg-success';
                                                $icon = 'bi-check-circle-fill';
                                            }
                                            ?>
                                            <span class="badge <?= $badge ?> rounded-pill">
                                                <i class="bi <?= $icon ?> me-1"></i>
                                                <?= ucfirst(str_replace('_', ' ', $r['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            if ($r['assigned_ngo_id']) {
                                                $stmt_n = $conn->prepare("SELECT ngo_name FROM ngos WHERE id = ?");
                                                $stmt_n->execute([$r['assigned_ngo_id']]);
                                                $ngo = $stmt_n->fetch();
                                                echo '<span class="text-primary fw-medium"><i class="bi bi-building"></i> ' . htmlspecialchars($ngo['ngo_name']) . '</span>';
                                            } else {
                                                echo '<span class="text-muted fst-italic small">Waiting for acceptance...</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted border rounded border-dashed">
                        <i class="bi bi-clipboard-x fs-3 mb-2 d-block"></i>
                        No rescue requests made yet.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Adoption Posts Section -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary text-white rounded-circle p-2 me-2">
                        <i class="bi bi-heart-fill"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">My Adoption Posts</h5>
                </div>

                <?php if (count($adoptions) > 0): ?>
                    <div class="row g-3">
                        <?php foreach ($adoptions as $a): ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-white rounded shadow-sm d-flex align-items-center h-100 transition-hover">
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
                                                <?= htmlspecialchars($a['pet_type']) ?>
                                            </p>
                                            <a href="dashboard.php?delete_post=<?= $a['id'] ?>" class="text-danger small"
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
                        You haven't posted any pets for adoption.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Adoption Applications RECEIVED -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-success text-white rounded-circle p-2 me-2">
                        <i class="bi bi-envelope-check"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Applications Received</h5>
                </div>

                <?php if (count($apps_received) > 0): ?>
                    <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Pet</th>
                                            <th>Applicant</th>
                                            <th>Message</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($apps_received as $ar): ?>
                                            <tr>
                                                <td class="fw-bold text-primary"><?= htmlspecialchars($ar['pet_name']) ?></td>
                                                <td>
                                                    <div class="fw-bold fs-6 mb-0"><?= htmlspecialchars($ar['full_name'] ?: $ar['applicant_name']) ?></div>
                                                    <div class="text-muted small mb-0"><i class="bi bi-telephone-fill" style="font-size: 0.7rem;"></i> <?= htmlspecialchars($ar['phone_number'] ?: $ar['applicant_phone']) ?></div>
                                                    <div class="text-muted" style="font-size: 0.7rem; line-height: 1.2;">
                                                        <i class="bi bi-geo-alt-fill" style="font-size: 0.7rem;"></i> <?= htmlspecialchars($ar['address'] ?: 'No address provided') ?>
                                                    </div>
                                                </td>
                                        <td>
                                            <div class="bg-light p-2 rounded small text-muted" style="max-height: 80px; min-width: 150px; overflow-y: auto; font-size: 0.8rem;">
                                                <?= nl2br(htmlspecialchars($ar['message'] ?? '')) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="badge rounded-pill bg-<?= $ar['status'] == 'approved' ? 'success' : ($ar['status'] == 'rejected' ? 'danger' : 'warning text-dark') ?>">
                                                <?= ucfirst($ar['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <?php if ($ar['id_proof_path']): ?>
                                                    <a href="<?= BASE_URL . $ar['id_proof_path'] ?>" target="_blank"
                                                        class="btn btn-xs btn-outline-info py-1 px-2 rounded-pill"
                                                        title="View ID Proof">
                                                        <i class="bi bi-file-earmark-person"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($ar['status'] == 'pending'): ?>
                                                    <a href="dashboard.php?action=approve&app_id=<?= $ar['id'] ?>"
                                                        class="btn btn-xs btn-success py-1 px-2 rounded-pill" title="Approve"
                                                        onclick="return confirm('Are you sure you want to approve this adoption?')">
                                                        <i class="bi bi-check"></i>
                                                    </a>
                                                    <a href="dashboard.php?action=reject&app_id=<?= $ar['id'] ?>"
                                                        class="btn btn-xs btn-danger py-1 px-2 rounded-pill" title="Reject">
                                                        <i class="bi bi-x"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No applications received yet.</p>
                <?php endif; ?>
            </div>

            <!-- Adoption Applications SENT -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-vibrant-violet text-white rounded-circle p-2 me-2">
                        <i class="bi bi-send-fill text-white"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">My Sent Applications</h5>
                </div>

                <?php if (count($apps_sent) > 0): ?>
                    <div class="row g-3">
                        <?php foreach ($apps_sent as $as): ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-white rounded shadow-sm d-flex align-items-center h-100">
                                    <img src="<?= BASE_URL . ($as['photo'] ?: 'assets/images/placeholder.jpg') ?>"
                                        class="rounded me-3 object-fit-cover" width="60" height="60">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($as['pet_name']) ?></h6>
                                            <span
                                                class="badge rounded-pill bg-<?= $as['status'] == 'approved' ? 'success' : ($as['status'] == 'rejected' ? 'danger' : 'warning text-dark') ?>"
                                                style="font-size: 0.6rem;">
                                                <?= ucfirst($as['status']) ?>
                                            </span>
                                        </div>
                                        <p class="small text-muted mb-0">Sent on
                                            <?= date('M d', strtotime($as['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted border rounded border-dashed opacity-75">
                        <i class="bi bi-send-x fs-3 mb-2 d-block"></i>
                        You haven't applied for any adoptions yet.
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Appointments Section -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-info text-white rounded-circle p-2 me-2">
                        <i class="bi bi-calendar-check-fill text-white"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">My Appointments</h5>
                </div>

                <?php if (count($appointments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Clinic / Salon</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $ap): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($ap['vet_name']) ?></div>
                                            <small class="text-muted"><?= ucfirst($ap['type']) ?></small>
                                        </td>
                                        <td>
                                            <div class="small fw-600"><?= date('D, M d, Y', strtotime($ap['date'])) ?></div>
                                            <div class="small text-muted"><?= date('h:i A', strtotime($ap['time'])) ?></div>
                                        </td>
                                        <td>
                                            <span
                                                class="badge rounded-pill bg-<?= $ap['status'] == 'confirmed' ? 'success' : ($ap['status'] == 'cancelled' ? 'danger' : 'warning text-dark') ?>"
                                                style="font-size: 0.7rem;">
                                                <?= ucfirst($ap['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted border rounded border-dashed opacity-75">
                        <i class="bi bi-calendar-x fs-3 mb-2 d-block"></i>
                        No upcoming appointments.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Donation History Section -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-vibrant-pink text-white rounded-circle p-2 me-2">
                        <i class="bi bi-piggy-bank-fill text-white"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Donation History</h5>
                </div>

                <?php if (count($donations) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <tbody>
                                <?php foreach ($donations as $d): ?>
                                    <tr class="border-bottom">
                                        <td class="py-3">
                                            <div class="fw-bold">Donated to <?= htmlspecialchars($d['ngo_name']) ?></div>
                                            <small class="text-muted"><?= date('M d, Y', strtotime($d['date'])) ?></small>
                                        </td>
                                        <td class="text-end py-3">
                                            <span class="fw-bold text-success">+₹<?= number_format($d['amount'], 2) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No donations made yet.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<style>
    .btn-xs {
        padding: 0.1rem 0.25rem;
        font-size: 0.75rem;
    }

    .bg-vibrant-pink {
        background-color: var(--vibrant-pink);
    }

    .bg-vibrant-violet {
        background-color: var(--vibrant-violet);
    }

    .hov-primary:hover {
        color: var(--primary-color) !important;
        background: rgba(16, 172, 132, 0.05) !important;
    }

    .fw-600 {
        font-weight: 600;
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
        <div class="modal fade" id="userUpdateModal<?= $fr['pet_id'] ?>" tabindex="-1">
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
                            <p class="text-muted">
                                <?= htmlspecialchars($latest_update['message']) ?>
                            </p>
                        </div>
                        <div class="mb-3">
                            <strong>Location:</strong>
                            <p class="text-muted">
                                <i class="bi bi-geo-alt-fill text-danger"></i>
                                Lat:
                                <?= $latest_update['latitude'] ?>,
                                Lng:
                                <?= $latest_update['longitude'] ?>
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