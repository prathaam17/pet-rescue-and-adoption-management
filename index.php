<?php
require_once 'config.php';
include 'includes/header.php';
?>



<!-- Modern Hero Section -->
<header class="hero-section text-center position-relative overflow-hidden"
    style="background: url('<?= BASE_URL ?>assets/images/hero-pets.jpg') no-repeat center center; background-size: cover; min-height: 80vh; display: flex; align-items: center; padding: 120px 0;">
    <div class="hero-overlay"
        style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to bottom, rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.7)); z-index: 1;">
    </div>
    <div class="container hero-content position-relative" style="z-index: 2;">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h5 class="text-uppercase text-primary fw-bold mb-3 ls-2 animate-zoom-in" style="letter-spacing: 5px; opacity: 1;">
                    <i class="bi bi-patch-check-fill me-2"></i> Saving Lives Since 2024
                </h5>
                <h1 class="hero-title animate-on-scroll delay-100">Love Has<br> <span class="text-gradient">No Breed</span></h1>
                <p class="lead mb-5 fs-4 text-dark mx-auto animate-on-scroll delay-200"
                    style="max-width: 900px; font-weight: 500; text-shadow: 0 0 15px rgba(255,255,255,1), 0 0 5px rgba(255,255,255,1);">
                    <b>Embrace a heart full of compassion. We provide a bridge for every stray and injured soul to find
                        their forever home.</b>
                </p>
                <div class="d-flex justify-content-center gap-4 flex-wrap">
                    <a href="<?= BASE_URL ?>user/emergency.php" class="btn btn-emergency btn-3d-lg btn-pulse shadow-lg animate-slide-left delay-300">
                        <i class="bi bi-lightning-charge-fill me-2"></i> Report Rescue
                    </a>
                    <a href="<?= BASE_URL ?>adoption.php" class="btn btn-adopt-special shadow-sm px-5 animate-slide-right delay-300">
                        <i class="bi bi-heart-fill me-2"></i> Adopt a Friend
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container" style="margin-top: -60px; position: relative; z-index: 10;">
    <!-- Stats Counter -->
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="glass-card p-5 border-0 card-glow-primary animate-on-scroll">
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <div class="col text-center border-end-md">
                        <div class="mb-2">
                            <i class="bi bi-heart-pulse fs-1 text-primary"
                                style="filter: drop-shadow(0 0 8px rgba(16, 172, 132, 0.4));"></i>
                        </div>
                        <h2 class="display-5 fw-bold text-primary mb-1">150+</h2>
                        <p class="text-muted fw-500 mb-0">Lives Saved</p>
                    </div>
                    <div class="col text-center border-end-md">
                        <div class="mb-2">
                            <i class="bi bi-house-heart fs-1 text-secondary"
                                style="filter: drop-shadow(0 0 8px rgba(46, 134, 222, 0.4));"></i>
                        </div>
                        <h2 class="display-5 fw-bold text-secondary mb-1">85</h2>
                        <p class="text-muted fw-500 mb-0">Happy Adoptions</p>
                    </div>
                    <div class="col text-center">
                        <div class="mb-2">
                            <i class="bi bi-shield-heart fs-1"
                                style="color: #feca57; filter: drop-shadow(0 0 8px rgba(254, 202, 87, 0.6));"></i>
                        </div>
                        <h2 class="display-5 fw-bold" style="color: #feca57;">12</h2>
                        <p class="text-muted fw-500 mb-0">Active NGOs</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Adoptions -->
    <div class="text-center mb-5 mt-5 pt-5 animate-on-scroll">
        <h6 class="text-uppercase text-primary fw-bold mb-2" style="letter-spacing: 2px;">Meet Our Stars</h6>
        <h2 class="display-5 fw-bold section-title">Friends Waiting for You</h2>
    </div>

    <div class="row g-4 justify-content-center">
        <?php
        $stmt = $conn->query("SELECT p.*, u.role as user_role, n.ngo_name 
                             FROM adoption_posts p 
                             JOIN users u ON p.user_id = u.id 
                             LEFT JOIN ngos n ON u.id = n.user_id 
                             WHERE p.status='approved' AND p.adoption_status='available' 
                             ORDER BY RAND() LIMIT 3");
        $posts = $stmt->fetchAll();

        if (count($posts) > 0):
            foreach ($posts as $post):
                ?>
                <div class="col-lg-4 col-md-6 animate-on-scroll">
                    <div class="glass-card h-100 overflow-hidden border-0 shadow-sm card-glow-secondary">
                        <div class="card-img-wrapper position-relative overflow-hidden">
                            <img src="<?= htmlspecialchars($post['photo']) ?: 'assets/images/placeholder.jpg' ?>"
                                class="pet-card-img w-100 object-fit-cover transition-transform" style="height: 280px;"
                                alt="Pet">
                            <div class="position-absolute top-0 end-0 m-3 d-flex flex-column gap-2 align-items-end">
                                <span class="badge bg-white text-primary shadow-sm rounded-pill px-3 py-2 fw-bold"
                                    style="border: 1px solid rgba(16, 172, 132, 0.2);">
                                    <i class="bi bi-tag-fill me-1"></i> <?= htmlspecialchars($post['pet_type']) ?>
                                </span>
                                <?php if ($post['user_role'] == 'ngo'): ?>
                                    <span class="badge bg-success text-white shadow-sm rounded-pill px-2 py-1 fw-bold"
                                        style="font-size: 0.65rem;">
                                        <i class="bi bi-patch-check-fill me-1"></i> NGO VERIFIED
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h3 class="h4 fw-bold mb-0"><?= htmlspecialchars($post['pet_name']) ?></h3>
                                <span class="text-muted small"><i class="bi bi-clock me-1"></i> 2d ago</span>
                            </div>
                            <p class="text-primary fw-600 mb-2 small text-uppercase letter-spacing-1">
                                <?= htmlspecialchars($post['age']) ?> • Healthy
                            </p>
                            <p class="text-muted mb-3" style="font-size: 0.8rem;">
                                <i class="bi bi-person-circle"></i> Poster:
                                <?= htmlspecialchars($post['ngo_name'] ?: 'Individual') ?>
                            </p>
                            <p class="card-text text-muted mb-4 line-clamp-2">
                                <?= substr(htmlspecialchars($post['description']), 0, 100) . '...' ?>
                            </p>
                            <div class="d-grid">
                                <a href="adoption.php" class="btn btn-primary rounded-pill">
                                    Meet <?= htmlspecialchars($post['pet_name']) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; else: ?>
            <div class="col-12 text-center py-5">
                <div class="glass-card p-5 d-inline-block border-0">
                    <i class="bi bi-emoji-heart-eyes fs-1 text-primary opacity-50"></i>
                    <p class="text-muted mt-3 mb-4 fs-5">All our pets have found homes! Check back soon or</p>
                    <a href="adoption_form.php" class="btn btn-secondary rounded-pill px-5">Post a Pet for Adoption</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- What We Do -->
    <div class="row mt-5 pt-5 align-items-center">
        <div class="col-lg-6 mb-5 mb-lg-0 animate-on-scroll">
            <div class="position-relative">
                <div class="position-absolute top-0 start-0 translate-middle-x bg-primary opacity-10 rounded-circle"
                    style="width: 300px; height: 300px; z-index: -1;"></div>
                <img src="uploads/rescue1.png" class="img-fluid rounded-4 shadow-xl" alt="Rescue"
                    style="transform: perspective(1000px) rotateY(-5deg); border: 12px solid white;">
                <div
                    class="position-absolute bottom-0 end-0 bg-white p-4 rounded-4 shadow-lg mb-n4 me-n4 d-none d-md-block border-start border-5 border-primary">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-soft text-primary rounded-circle p-3">
                            <i class="bi bi-shield-check fs-3"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">100% Verified</h6>
                            <small class="text-muted">Trusted NGO Network</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 ps-lg-5 animate-on-scroll delay-200">
            <h6 class="text-uppercase text-secondary fw-bold mb-3 ls-2">Our impact</h6>
            <h2 class="fw-bold mb-4 display-6">More Than Just a <span class="text-gradient">Platform</span></h2>
            <p class="lead text-muted mb-4">We act as the bridge between animals in distress and the people who can help
                them. Our ecosystem ensures rapid response and quality care.</p>

            <div class="row g-4">
                <div class="col-12">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-white shadow-sm rounded-3 p-2 text-primary">
                            <i class="bi bi-lightning-charge-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Instant Emergency Reporting</h6>
                            <p class="text-muted small mb-0">Direct alerts to nearby NGOs and rescue teams.</p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-white shadow-sm rounded-3 p-2 text-primary">
                            <i class="bi bi-check2-all fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Verified Adoption Steps</h6>
                            <p class="text-muted small mb-0">A transparent and safe process for both pets and owners.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-white shadow-sm rounded-3 p-2 text-primary">
                            <i class="bi bi-hospital-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Professional Care Network</h6>
                            <p class="text-muted small mb-0">Curated list of highly rated vets and grooming salons.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <a href="ngos.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">
                    <i class="bi bi-search me-2"></i> Explore NGO Network
                </a>
            </div>
        </div>
    </div>

    <div class="section-divider-v"></div>

    <!-- Call to Action -->
    <div class="glass-card mt-5 mb-5 p-5 text-center position-relative overflow-hidden border-0 aurora-bg animate-on-scroll"
        style="border-radius: 60px 20px 80px 30px !important; background: linear-gradient(135deg, rgba(255,255,255,0.8), rgba(16, 172, 132, 0.05));">
        <div class="position-relative z-2 py-5">
            <h2 class="fw-bold mb-3 display-4">Ready to Make a <span class="text-gradient">Difference?</span></h2>
            <p class="text-muted mb-5 fs-5 mx-auto" style="max-width: 600px;">Your kindness can change a life today.
                Join our vibrant community of animal lovers and heroes.</p>
            <div class="d-flex flex-column align-items-center gap-4">
                <a href="register.php" class="btn btn-primary btn-3d-lg btn-lg px-5 shadow-lg">
                    <i class="bi bi-person-plus-fill me-2"></i> Get Started Now
                </a>
                <a href="donate.php" class="btn btn-donate-special btn-lg px-5">
                    <i class="bi bi-piggy-bank-fill me-2"></i> Send a Donation
                </a>
            </div>
        </div>
        <!-- Very Subtle Decorative Gradient -->
        <div class="position-absolute top-0 start-0 translate-middle bg-primary opacity-10 rounded-circle"
            style="width: 400px; height: 400px; filter: blur(100px); z-index: 1;"></div>
    </div>
</div>

<style>
    :root {
        --bg-vibrant-violet: rgba(142, 68, 173, 1);
    }

    .text-vibrant-pink {
        color: var(--vibrant-pink) !important;
    }

    .ls-2 {
        letter-spacing: 2px;
    }

    .fw-500 {
        font-weight: 500;
    }

    .fw-600 {
        font-weight: 600;
    }

    .bg-primary-soft {
        background-color: rgba(16, 172, 132, 0.1);
    }

    .shadow-xl {
        box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.15);
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .transition-transform {
        transition: transform 0.8s cubic-bezier(0.23, 1, 0.32, 1);
    }

    .glass-card:hover .transition-transform {
        transform: scale(1.08) rotate(1deg);
    }

    @media (min-width: 768px) {
        .border-end-md {
            border-right: 1px solid rgba(0, 0, 0, 0.05);
        }
    }
</style>

<?php include 'includes/footer.php'; ?>