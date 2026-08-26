<?php
require_once 'config.php';
include 'includes/header.php';

// Load all approved NGOs for live filtering
$query = "SELECT n.*, u.phone as contact_phone FROM ngos n JOIN users u ON n.user_id = u.id WHERE n.status='approved'";
$ngos = $conn->query($query)->fetchAll();
?>

<div class="container mt-5 pt-4">
    <div class="text-center mb-5 animate-on-scroll">
        <h6 class="text-uppercase text-primary fw-bold mb-2" style="letter-spacing: 2px;">Support Networks</h6>
        <h2 class="display-5 fw-bold section-title"><span class="text-gradient">Our Partner NGOs</span></h2>
        <p class="lead text-muted mx-auto" style="max-width: 600px;">Every organization here is a verified hero,
            dedicated to the welfare of animals in our city.</p>
    </div>

    <!-- Formal Unified Single Search Section -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-6">
            <div class="formal-search-bar">
                <div class="search-segment">
                    <i class="bi bi-search"></i>
                    <input type="text" id="ngoGlobalSearch" placeholder="Search NGO names or locations...">
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
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
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

    <div class="row g-4" id="ngoContainer">
        <?php foreach ($ngos as $n):
            // Normalize for frontend searching: lowercase and remove symbols
            $search_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $n['ngo_name']));
            $search_city = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $n['city']));
            $search_address = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $n['address']));
            ?>
            <div class="col-md-6 mb-4 ngo-card-col animate-on-scroll" data-name="<?= htmlspecialchars($search_name) ?>"
                data-place="<?= htmlspecialchars($search_city . $search_address) ?>">
                <div class="glass-card h-100 border-0 shadow-sm p-4 overflow-hidden position-relative">
                    <!-- Subtle background glow -->
                    <div class="position-absolute top-0 end-0 bg-primary opacity-10 rounded-circle"
                        style="width: 150px; height: 150px; margin-right: -75px; margin-top: -75px; filter: blur(30px);">
                    </div>

                    <div class="d-flex align-items-center mb-4 position-relative">
                        <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center me-4"
                            style="width: 64px; height: 64px; font-size: 1.5rem;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1">
                                <?= htmlspecialchars($n['ngo_name']) ?>
                            </h4>
                            <span class="badge bg-light text-primary border-primary border rounded-pill px-3">
                                <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($n['city']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="mb-4">
                        <p class="text-muted mb-4"><?= htmlspecialchars($n['description']) ?></p>
                        <div class="d-flex flex-column gap-2">
                            <p class="small text-muted mb-0"><i class="bi bi-geo-alt-fill text-primary me-2"></i>
                                <?= htmlspecialchars($n['address']) ?></p>
                            <p class="small text-muted mb-0"><i class="bi bi-telephone-fill text-primary me-2"></i>
                                <?= htmlspecialchars($n['contact_phone'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="donate.php?ngo_id=<?= $n['id'] ?>"
                            class="btn btn-primary rounded-pill px-4 shadow-sm flex-grow-1"><i
                                class="bi bi-heart-fill me-2"></i> Support NGO</a>
                        <a href="tel:<?= $n['contact_phone'] ?? '' ?>"
                            class="btn btn-outline-primary rounded-pill px-4 bg-white"><i class="bi bi-telephone"></i></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- No Results Message -->
        <div id="noResults" class="col-12 text-center py-5 d-none">
            <i class="bi bi-search display-1 text-muted opacity-25"></i>
            <h3 class="mt-4 text-muted">No NGOs found matching your search.</h3>
            <p class="text-muted">Try removing symbols or using different keywords.</p>
            <button onclick="clearSearch()" class="btn btn-outline-primary rounded-pill mt-2">View All NGOs</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const globalInput = document.getElementById('ngoGlobalSearch');
        const cards = document.querySelectorAll('.ngo-card-col');
        const noResults = document.getElementById('noResults');

        function filterNGOs() {
            const searchTerm = globalInput.value.toLowerCase().replace(/[^a-z0-9]/g, '');

            let visibleCount = 0;

            cards.forEach(card => {
                const cardName = card.getAttribute('data-name');
                const cardPlace = card.getAttribute('data-place');

                // Match if search term is found in either name or place
                const match = cardName.includes(searchTerm) || cardPlace.includes(searchTerm);

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
            filterNGOs();
        };

        globalInput.addEventListener('input', filterNGOs);
    });
</script>

<?php include 'includes/footer.php'; ?>