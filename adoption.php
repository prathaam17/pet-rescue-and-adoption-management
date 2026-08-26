<?php
require_once 'config.php';
include 'includes/header.php';

$posts = $conn->query("SELECT p.*, u.role as user_role, n.ngo_name 
                      FROM adoption_posts p 
                      JOIN users u ON p.user_id = u.id 
                      LEFT JOIN ngos n ON u.id = n.user_id 
                      WHERE p.status='approved' AND p.adoption_status='available' 
                      ORDER BY p.created_at DESC")->fetchAll();
?>

<div class="container mt-5 pt-4">
    <div class="text-center mb-5 animate-on-scroll">
        <h6 class="text-uppercase text-primary fw-bold mb-2" style="letter-spacing: 2px;">Find a Friend</h6>
        <h1 class="display-5 fw-bold section-title"><span class="text-gradient">Pets for Adoption</span></h1>
        <p class="lead text-muted mx-auto mb-4" style="max-width: 600px;">Every pet here is looking for a forever home.
            Your new best friend might be just one click away.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="adoption_form.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-circle me-2"></i> Post a Pet for Adoption
            </a>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'ngo'): ?>
                <a href="ngo/dashboard.php" class="btn btn-outline-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-speedometer2 me-2"></i> NGO Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formal Unified Single Search Section -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-6">
            <div class="formal-search-bar">
                <div class="search-segment">
                    <i class="bi bi-search"></i>
                    <input type="text" id="petGlobalSearch"
                        placeholder="Search pet types or breeds (Dog, Cat, Persian...)">
                </div>
            </div>
        </div>
    </div>

    <style>
        .formal-search-bar {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 3px solid transparent;
            background: linear-gradient(#fff, #fff) padding-box,
                linear-gradient(270deg, #54a0ff, #00d2ff, #a29bfe, #ff7675, #54a0ff) border-box;
            background-size: 400% 400%;
            border-radius: 12px;
            padding: 5px;
            box-shadow: 0 4px 15px rgba(84, 160, 255, 0.1);
            transition: all 0.3s ease;
            animation: rgbFlow 6s ease infinite;
        }

        @keyframes rgbFlow {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .formal-search-bar:focus-within {
            box-shadow: 0 8px 25px rgba(84, 160, 255, 0.3);
            transform: translateY(-2px);
            animation-duration: 3s;
        }

        .search-segment {
            display: flex;
            align-items: center;
            flex: 1;
            padding: 8px 15px;
        }

        .search-segment i {
            color: #95a5a6;
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .search-segment input {
            border: none;
            width: 100%;
            outline: none;
            font-size: 0.95rem;
            color: #2c3e50;
            font-weight: 500;
        }
    </style>

    <div class="row g-4" id="petContainer">
        <?php foreach ($posts as $p):
            // Normalize for frontend searching: lowercase and remove symbols
            $search_type = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['pet_type']));
            $search_breed = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p['breed']));
            ?>
            <div class="col-md-6 col-lg-3 pet-card-col animate-on-scroll" data-type="<?= htmlspecialchars($search_type) ?>"
                data-breed="<?= htmlspecialchars($search_breed) ?>">
                <div class="glass-card h-100 border-0 shadow-sm overflow-hidden">
                    <div class="card-img-wrapper position-relative overflow-hidden">
                        <img src="<?= BASE_URL . ($p['photo'] ?: 'assets/images/placeholder.jpg') ?>"
                            class="pet-card-img w-100 object-fit-cover transition-transform" style="height: 220px;"
                            alt="<?= htmlspecialchars($p['pet_name']) ?>">
                        <div class="position-absolute top-0 end-0 m-2 d-flex flex-column gap-1 align-items-end">
                            <span class="badge bg-white text-primary shadow-sm rounded-pill px-2 py-1 small fw-bold">
                                <?= htmlspecialchars($p['pet_type']) ?>
                            </span>
                            <?php if ($p['user_role'] == 'ngo'): ?>
                                <span class="badge bg-success text-white shadow-sm rounded-pill px-2 py-1 small fw-bold"
                                    style="font-size: 0.6rem;">
                                    <i class="bi bi-patch-check-fill me-1"></i> NGO VERIFIED
                                </span>
                            <?php else: ?>
                                <span class="badge bg-info text-white shadow-sm rounded-pill px-2 py-1 small fw-bold"
                                    style="font-size: 0.6rem;">
                                    <i class="bi bi-person-fill me-1"></i> INDIVIDUAL
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h4 class="card-title fw-bold mb-0 text-primary cursor-pointer"
                                onclick='showPetDetails(<?= json_encode($p) ?>)' title="Click to view bio">
                                <?= htmlspecialchars($p['pet_name']) ?>
                            </h4>
                            <button class="btn btn-sm btn-outline-primary border-0 p-1 shadow-none"
                                onclick="showFullImage('<?= BASE_URL . ($p['photo'] ?: 'assets/images/placeholder.jpg') ?>', '<?= htmlspecialchars($p['pet_name']) ?>')"
                                title="View Full Photo">
                                <i class="bi bi-zoom-in fs-5"></i>
                            </button>
                        </div>
                        <p class="text-primary fw-600 mb-2 small text-uppercase">
                            <?= htmlspecialchars($p['pet_type']) ?> • <?= htmlspecialchars($p['breed']) ?> •
                            <?= htmlspecialchars($p['gender']) ?>
                        </p>
                        <p class="text-muted mb-2 small">
                            <i class="bi bi-clock me-1"></i> <?= htmlspecialchars($p['age']) ?> • Healthy
                        </p>
                        <p class="text-muted mb-3" style="font-size: 0.75rem;">
                            <i class="bi bi-person-circle"></i> Poster:
                            <?= htmlspecialchars($p['ngo_name'] ?: 'Individual') ?>
                        </p>
                        <p class="card-text text-muted small mb-4">
                            <?= substr(htmlspecialchars($p['description']), 0, 80) . (strlen($p['description']) > 80 ? '...' : '') ?>
                            <?php if (strlen($p['description']) > 80): ?>
                                <button class="btn btn-link p-0 text-primary small text-decoration-none fw-bold"
                                    onclick='showPetDetails(<?= json_encode($p) ?>)'>Read More</button>
                            <?php endif; ?>
                        </p>
                        <div class="d-flex gap-2">
                            <a href="adopt_apply.php?id=<?= $p['id'] ?>"
                                class="btn btn-primary rounded-pill btn-sm flex-grow-1">Adopt Now</a>
                            <button class="btn btn-outline-primary rounded-pill btn-sm"
                                onclick='showPetDetails(<?= json_encode($p) ?>)' title="View Full Bio">
                                <i class="bi bi-info-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- No Results Message -->
        <div id="noResults" class="col-12 text-center py-5 <?= count($posts) == 0 ? '' : 'd-none' ?>">
            <div class="glass-card p-5 d-inline-block border-0 shadow-sm">
                <i class="bi bi-search fs-1 text-primary opacity-50"></i>
                <h3 class="mt-3 text-muted">No pets found matching your search.</h3>
                <p class="text-muted mb-4">Try removing symbols or using different keywords.</p>
                <button onclick="clearSearch()" class="btn btn-primary rounded-pill px-5">View All Pets</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const globalInput = document.getElementById('petGlobalSearch');
        const cards = document.querySelectorAll('.pet-card-col');
        const noResults = document.getElementById('noResults');

        function filterPets() {
            const searchTerm = globalInput.value.toLowerCase().replace(/[^a-z0-9]/g, '');

            let visibleCount = 0;

            cards.forEach(card => {
                const cardType = card.getAttribute('data-type');
                const cardBreed = card.getAttribute('data-breed');

                // Match if search term is found in either type or breed
                const match = cardType.includes(searchTerm) || cardBreed.includes(searchTerm);

                if (match) {
                    card.classList.remove('d-none');
                    visibleCount++;
                } else {
                    card.classList.add('d-none');
                }
            });

            if (visibleCount === 0) {
                noResults.classList.remove('d-none');
            } else {
                noResults.classList.add('d-none');
            }
        }

        window.clearSearch = function () {
            globalInput.value = '';
            filterPets();
        };

        globalInput.addEventListener('input', filterPets);
    });
</script>


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

<!-- Pet Details Modal -->
<div class="modal fade" id="petDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill text-primary me-2"></i>Pet Biography
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <img id="detailPetPhoto" src="" class="rounded-4 shadow-sm mb-3 object-fit-cover"
                        style="width: 120px; height: 120px;" alt="">
                    <h3 id="detailPetName" class="fw-bold mb-1"></h3>
                    <div class="d-flex justify-content-center flex-wrap gap-2 mb-2">
                        <span id="detailPetType" class="badge bg-primary rounded-pill px-3"></span>
                        <span id="detailPetBreed" class="badge bg-secondary rounded-pill px-3"></span>
                        <span id="detailPetGender" class="badge bg-info text-dark rounded-pill px-3"></span>
                        <span id="detailPetAge"
                            class="badge bg-outline-primary border border-primary text-primary rounded-pill px-3"></span>
                    </div>
                </div>
                <div class="p-3 bg-light rounded-4">
                    <h6 class="fw-bold text-uppercase small text-muted mb-2">Description</h6>
                    <p id="detailPetDescription" class="mb-0 text-dark"
                        style="white-space: pre-line; line-height: 1.6;"></p>
                </div>
                <div class="mt-4 d-flex align-items-center justify-content-between">
                    <div class="text-muted small">
                        <i class="bi bi-person-circle me-1"></i> Posted by: <span id="detailPetPoster"
                            class="fw-bold text-primary"></span>
                    </div>
                    <div id="detailPetDate" class="text-muted small"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <a id="detailAdoptBtn" href="#" class="btn btn-primary rounded-pill px-4 flex-grow-1">Adopt Me Now</a>
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

    function showPetDetails(p) {
        document.getElementById('detailPetName').innerText = p.pet_name;
        document.getElementById('detailPetType').innerText = p.pet_type;
        document.getElementById('detailPetBreed').innerText = p.breed || 'Unknown Breed';
        document.getElementById('detailPetGender').innerText = p.gender;
        document.getElementById('detailPetAge').innerText = p.age + " Old";
        document.getElementById('detailPetDescription').innerText = p.description;
        document.getElementById('detailPetPhoto').src = '<?= BASE_URL ?>' + (p.photo || 'assets/images/placeholder.jpg');
        document.getElementById('detailPetPoster').innerText = p.ngo_name || 'Individual';
        document.getElementById('detailPetDate').innerText = 'Posted: ' + new Date(p.created_at).toLocaleDateString();
        document.getElementById('detailAdoptBtn').href = 'adopt_apply.php?id=' + p.id;

        var myModal = new bootstrap.Modal(document.getElementById('petDetailsModal'));
        myModal.show();
    }
</script>

<?php include 'includes/footer.php'; ?>