<?php
require_once 'config.php';
include 'includes/header.php';
?>

<!-- Custom CSS for Horizontal Scroll & Snap -->
<style>
    /* smooth scrolling for the whole page */
    html {
        scroll-behavior: smooth;
    }

    body {
        overflow-x: hidden;
    }

    /* SECTION CONTAINERS */
    .snap-container {
        /* Optional: Toggle this if you want strictly one-page-at-a-time scrolling. 
           For now, we just ensure they are min-h-screen for the "One Page" feel. */
        width: 100%;
        position: relative;
    }

    /* HERO SECTION */
    .hero-wrapper {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
        padding: 2rem 0;
        z-index: 1;
        position: relative;
    }

    /* STORIES SECTION (The "Page 2") */
    .stories-wrapper {
        min-height: 100vh;
        background: #2d3436;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 40px 0;
        position: relative;
        z-index: 10;
        border-radius: 40px 40px 0 0;
        margin-top: -40px;
        box-shadow: 0 -20px 50px rgba(0, 0, 0, 0.3);
    }

    /* HORIZONTAL SCROLL CONTAINER */
    .stories-scroller {
        display: flex;
        gap: 30px;
        overflow-x: auto;
        padding: 20px 0; /* Remove side padding for seamless loop */
        /* scroll-snap-type: x mandatory; */ // Snapping disabled for smooth auto-scroll
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* IE 10+ */
        align-items: center;
    }

    .stories-scroller::-webkit-scrollbar {
        display: none;
        /* Chrome/Safari */
    }

    /* STORY CARD */
    .story-card-h {
        min-width: 320px;
        /* Reduced from 400px */
        max-width: 320px;
        height: 450px;
        /* Reduced from 500px */
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 25px;
        scroll-snap-align: center;
        position: relative;
        overflow: hidden;
        transition: transform 0.4s ease, background 0.4s ease;
        display: flex;
        flex-direction: column;
    }

    .story-card-h:hover {
        transform: scale(1.05) translateY(-10px);
        background: rgba(255, 255, 255, 0.15);
        z-index: 5;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    }

    /* IMAGE HALF */
    .card-img-top {
        height: 50%;
        /* Slightly smaller image area */
        width: 100%;
        object-fit: cover;
        border-bottom: 5px solid rgba(0, 0, 0, 0.1);
    }

    /* TEXT HALF */
    .card-body-custom {
        padding: 20px;
        /* Slightly tighter padding */
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* BADGE */
    .status-badge {
        position: absolute;
        top: 20px;
        left: 20px;
        background: transparent;
        padding: 0;
        font-weight: 800;
        font-size: 1.2rem;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
        z-index: 5;
    }

    /* Ensure icon color matches or contrasts well if needed, but text-* classes in HTML usually handle color */
    .status-badge i {
        margin-right: 5px;
    }

    /* NAVIGATION ARROWS */
    .scroll-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        color: var(--primary-color);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 20;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        font-size: 1.5rem;
        transition: 0.3s;
        border: none;
    }

    .scroll-btn:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-50%) scale(1.1);
    }

    .scroll-left {
        left: 20px;
    }

    .scroll-right {
        right: 20px;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .story-card-h {
            min-width: 85vw;
            /* Mobile: Show 1 peek 2nd */
            height: 450px;
        }
    }

    .animate-float {
        animation: floating 3s ease-in-out infinite;
    }

    @keyframes floating {
        0% {
            transform: translateY(0px) rotate(-5deg);
        }

        50% {
            transform: translateY(-15px) rotate(-5deg);
        }

        100% {
            transform: translateY(0px) rotate(-5deg);
        }
    }
</style>

<!-- SECTION 1: HERO (Vertical Page 1) -->
<div class="hero-wrapper">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-10 animate-on-scroll">
                <span
                    class="badge bg-white text-primary shadow-sm rounded-pill px-4 py-2 mb-4 text-uppercase fw-bold ls-2">
                    <i class="bi bi-heart-fill me-2"></i> Our Mission
                </span>
                <h1 class="display-3 fw-bold mb-4">
                    Saving one animal may not change the world, but it changes the world for <span
                        class="text-gradient">that animal.</span>
                </h1>

                <!-- Full Width Breakout Section -->
                <div class="position-relative mt-5 py-5 overflow-hidden"
                    style="width: 100vw; margin-left: calc(-50vw + 50%); background: linear-gradient(to right, rgba(255,255,255,0), rgba(255,255,255,0.8) 20%, rgba(255,255,255,0.8) 80%, rgba(255,255,255,0));">



                    <div class="container position-relative animate-on-scroll delay-200" style="z-index: 2;">
                        <div class="row justify-content-center">
                            <div class="col-lg-10">
                                <h6 class="text-primary fw-bold ls-2 mb-4 d-inline-block px-3 py-1 rounded-pill"
                                    style="background: rgba(var(--bs-primary-rgb), 0.1);">WHY WE EXIST</h6>
                                <p class="display-6 fw-normal text-dark mb-0"
                                    style="font-weight: 300; line-height: 1.6;">
                                    Not just a platform, but a <span class="fw-bold"
                                        style="color: #2d3436; background: linear-gradient(120deg, #81ecec 0%, #00b894 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">movement</span>.
                                    We unite NGOs, volunteers, and adopters to ensure every rescue ends in a <span
                                        class="position-relative d-inline-block fw-bold text-dark">safe home
                                        <svg class="position-absolute w-100" style="bottom: -5px; left: 0; height: 8px;"
                                            viewBox="0 0 100 10" preserveAspectRatio="none">
                                            <path d="M0 5 Q 50 10 100 5" stroke="#00b894" stroke-width="3"
                                                fill="none" />
                                        </svg>
                                    </span>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 animate-bounce">
                    <p class="text-muted small text-uppercase ls-2 mb-2">See Transformations</p>
                    <a href="#transformations"
                        class="bi bi-arrow-down-circle-fill fs-2 text-primary text-decoration-none"></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Background Decor -->
    <i class="bi bi-paw-fill display-1 position-absolute top-0 start-0 m-5 text-muted opacity-10 animate-float"
        style="transform: rotate(-15deg);"></i>
</div>

<!-- SECTION 2: HORIZONTAL STORIES (Vertical Page 2) -->
<div id="transformations" class="stories-wrapper">

    <div class="container-fluid position-relative">
        <div class="text-center mb-5 animate-on-scroll">
            <span class="badge bg-warning text-dark rounded-pill px-3 py-1 mb-2">Real Stories</span>
            <h2 class="display-4 fw-bold mb-2">Rescue Transformations</h2>
            <p class="text-white-50">Swipe to see how lives are changed forever.</p>
        </div>

        <!-- Arrow Buttons -->
        <button class="scroll-btn scroll-left d-none d-md-flex" onclick="scrollStories('left')"><i
                class="bi bi-chevron-left"></i></button>
        <button class="scroll-btn scroll-right d-none d-md-flex" onclick="scrollStories('right')"><i
                class="bi bi-chevron-right"></i></button>

        <!-- Horizontal Scroller -->
        <div class="stories-scroller" id="storiesContainer">

            <!-- Story 1 -->
            <div class="story-card-h">

                <img src="assets/images/about/tiger2.jpeg" class="card-img-top" alt="Tiger">
                <div class="card-body-custom">
                    <div>
                        <h3 class="h4 fw-bold text-white mb-2">Tiger’s Rescue</h3>
                        <p class="text-white-50 small">Reported injured near a construction site. Rushed for emergency
                            care, he survived and is recovering.</p>
                    </div>
                    <div class="d-flex align-items-center small" style="color: #31eeb8ff;">
                        <i class="bi bi-shield-check me-2 fs-5"></i> Rescued
                    </div>
                </div>
            </div>

            <!-- Story 2 -->
            <div class="story-card-h">

                <img src="assets/images/about/maxx.jpeg" class="card-img-top" alt="Daisy">
                <div class="card-body-custom">
                    <div>
                        <h3 class="h4 fw-bold text-white mb-2">Daisy’s Recovery</h3>
                        <p class="text-white-50 small">Found abandoned and dehydrated. Our NGO team reached her in time
                            to provide food and shelter.</p>
                    </div>
                    <div class="d-flex align-items-center small" style="color: #80b9dfff;">
                        <i class="bi bi-shield-check me-2 fs-5"></i> Rescued
                    </div>
                </div>
            </div>

            <!-- Story 3 -->
            <div class="story-card-h">

                <img src="assets\images\about\daisyy.jpeg" class="card-img-top" alt="Max">
                <div class="card-body-custom">
                    <div>
                        <h3 class="h4 fw-bold text-white mb-2">Max’s Safety</h3>
                        <p class="text-white-50 small">Trapped in a drain during rain. Volunteers arrived instantly to
                            bring him to safety.</p>
                    </div>
                    <div class="d-flex align-items-center small" style="color: #ffff12ff;">
                        <i class="bi bi-shield-check me-2 fs-5"></i> Rescued
                    </div>
                </div>
            </div>

            <!-- Story 4 (Adoptions Start) -->
            <div class="story-card-h" style="border-color: rgba(var(--bs-success-rgb), 0.5);">

                <img src="assets\images\about\rokii.jpeg" class="card-img-top" alt="Bruno">
                <div class="card-body-custom">
                    <div>
                        <h3 class="h4 fw-bold text-white mb-2">Bruno’s New Home</h3>
                        <p class="text-white-50 small">From street fear to a loving family. Bruno found his forever
                            home.</p>
                    </div>
                    <div class="bg-success bg-opacity-25 p-2 rounded text-center">
                        <small class="text-white fw-bold">❤️ Thanks to Geeta & Family</small>
                    </div>
                </div>
            </div>

            <!-- Story 5 -->
            <div class="story-card-h">

                <img src="assets/images/about/lunass.jpeg" class="card-img-top" alt="Luna">
                <div class="card-body-custom">
                    <div>
                        <h3 class="h4 fw-bold text-white mb-2">Luna’s Joy</h3>
                        <p class="text-white-50 small">Found weak, now thriving. Adoption gave her the family she
                            deserved.</p>
                    </div>
                    <div class="bg-info bg-opacity-25 p-2 rounded text-center">
                        <small class="text-white fw-bold">❤️ Thanks to Rahul & Family</small>
                    </div>
                </div>
            </div>

            <!-- Story 6 -->
            <div class="story-card-h">

                <img src="assets/images/about/brunoos.jpeg" class="card-img-top" alt="Rocky">
                <div class="card-body-custom">
                    <div>
                        <h3 class="h4 fw-bold text-white mb-2">Rocky’s Playtime</h3>
                        <p class="text-white-50 small">Proper care restored his confidence. Rocky is now a playful
                            family member.</p>
                    </div>
                    <div class="bg-warning bg-opacity-25 p-2 rounded text-center">
                        <small class="text-white fw-bold">❤️ Thanks to Anita & Family</small>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const container = document.getElementById('storiesContainer');
    let isPaused = false;
    let autoScrollSpeed = 1; // Pixels per interval

    // Manual navigation
    function scrollStories(direction) {
        const scrollAmount = 350; // Card width (320) + gap (30)
        if (direction === 'left') {
            container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        } else {
            container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    }

    // Clone content for seamless looping
    const originalContent = container.innerHTML;
    container.innerHTML += originalContent; // Duplicate cards

    // Automatic smooth scroll function
    function startAutoScroll() {
        setInterval(() => {
            if (!isPaused) {
                container.scrollLeft += autoScrollSpeed;

                // Seamless reset logic
                // If we reach the midpoint (end of first set), jump to start
                if (container.scrollLeft >= container.scrollWidth / 2) {
                    container.scrollLeft = 0;
                }
            }
        }, 30);
    }

    // Also handle manual scrolls to keep the infinite loop working
    container.addEventListener('scroll', () => {
        if (container.scrollLeft >= container.scrollWidth / 2) {
            container.scrollLeft = 0;
        }
    });

    // Pause on interactions
    container.addEventListener('mouseenter', () => isPaused = true);
    container.addEventListener('mouseleave', () => isPaused = false);
    container.addEventListener('touchstart', () => isPaused = true);
    container.addEventListener('touchend', () => isPaused = false);

    // Initial Start
    startAutoScroll();
</script>

<?php include 'includes/footer.php'; ?>