<?php
require_once 'config.php';
include 'includes/header.php';

$type = isset($_GET['type']) ? $_GET['type'] : '';
$sql = "SELECT * FROM vet_salon";
if ($type) {
    $sql .= " WHERE type = '$type'";
}
$vets = $conn->query($sql)->fetchAll();
?>

<div class="container mt-5 pt-4">
    <div class="text-center mb-5 animate-on-scroll">
        <h6 class="text-uppercase text-primary fw-bold mb-2" style="letter-spacing: 2px;">Pet Care Services</h6>
        <h2 class="display-5 fw-bold section-title"><span class="text-gradient">Vets & Grooming</span></h2>
        <div class="mt-4">
            <a href="vets.php"
                class="btn btn-<?= $type == '' ? 'primary shadow-sm' : 'outline-primary bg-white' ?> rounded-pill px-4 mx-1">All
                List</a>
            <a href="vets.php?type=vet"
                class="btn btn-<?= $type == 'vet' ? 'primary shadow-sm' : 'outline-primary bg-white' ?> rounded-pill px-4 mx-1"><i
                    class="bi bi-hospital me-1"></i> Vets</a>
            <a href="vets.php?type=salon"
                class="btn btn-<?= $type == 'salon' ? 'primary shadow-sm' : 'outline-primary bg-white' ?> rounded-pill px-4 mx-1"><i
                    class="bi bi-scissors me-1"></i> Salons</a>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($vets as $v): ?>
            <div class="col-lg-4 col-md-6 mb-4 animate-on-scroll">
                <div class="glass-card h-100 border-0 shadow-sm overflow-hidden p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="bg-<?= $v['type'] == 'vet' ? 'primary' : 'vibrant-violet' ?> text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 54px; height: 54px;">
                            <i class="bi bi-<?= $v['type'] == 'vet' ? 'hospital' : 'scissors' ?> fs-4"></i>
                        </div>
                        <span
                            class="badge bg-light text-primary border-primary border shadow-sm rounded-pill px-3 py-2 fw-bold">
                            <?= ucfirst($v['type']) ?>
                        </span>
                    </div>
                    <h4 class="fw-bold mb-3"><?= htmlspecialchars($v['name']) ?></h4>
                    <div class="mb-4">
                        <p class="text-muted mb-1 d-flex align-items-center"><i
                                class="bi bi-geo-alt-fill text-primary me-2"></i> <?= htmlspecialchars($v['location']) ?>
                        </p>
                        <p class="text-muted mb-1 d-flex align-items-center"><i
                                class="bi bi-telephone-fill text-primary me-2"></i> <?= htmlspecialchars($v['phone']) ?></p>
                        <hr class="opacity-10 my-3">
                        <p class="small text-muted mb-0"><strong>Services:</strong> <?= htmlspecialchars($v['services']) ?>
                        </p>
                    </div>
                    <div class="mt-auto">
                        <button class="btn btn-primary w-100 rounded-pill shadow-sm"
                            onclick="openBookingModal(<?= $v['id'] ?>, '<?= htmlspecialchars($v['name']) ?>')">Book
                            Appointment</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (count($vets) == 0): ?>
            <div class="col-12 text-center py-5">
                <div class="glass-card p-5 d-inline-block border-0">
                    <i class="bi bi-emoji-heart-eyes fs-1 text-primary opacity-50"></i>
                    <p class="text-muted mt-3 mb-0 fs-5">No listings found in this category.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .bg-vibrant-violet {
        background-color: var(--vibrant-violet);
    }
</style>

<!-- Booking Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="book_appointment.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Book Appointment with <span id="vetNameDisplay"
                        class="text-primary fw-bold"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="vet_salon_id" id="vetIdInput">
                <input type="hidden" name="book_appointment" value="1">
                <div class="mb-3">
                    <label class="form-label">Preferred Date</label>
                    <input type="date" name="date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Preferred Time</label>
                    <input type="time" name="time" class="form-control" required>
                </div>
                <p class="text-muted small">By clicking book, you agree to our terms. This is a secure mock booking
                    process.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-toggle="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Confirm Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openBookingModal(id, name) {
        document.getElementById('vetIdInput').value = id;
        document.getElementById('vetNameDisplay').innerText = name;
        var myModal = new bootstrap.Modal(document.getElementById('appointmentModal'));
        myModal.show();
    }
</script>

<?php include 'includes/footer.php'; ?>