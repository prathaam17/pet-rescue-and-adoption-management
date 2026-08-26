<footer>
    <div class="container">
        <div class="row g-4 animate-on-scroll">
            <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                <h5 class="text-uppercase fw-bold">Pet Rescue</h5>
                <p class="mb-4">
                    Helping Animals. Saving Lives.<br>
                    Connecting compassionate souls with animals in need across the city.
                </p>
                <div class="social-links d-flex flex-column gap-2 mt-3">
                    <a href="https://facebook.com/pet_rescue" target="_blank" class="d-flex align-items-center gap-2">
                        <i class="bi bi-facebook fs-5"></i> <span>pet_rescue</span>
                    </a>
                    <a href="https://x.com/pet_rescue" target="_blank" class="d-flex align-items-center gap-2">
                        <i class="bi bi-twitter-x fs-5"></i> <span>pet_rescue</span>
                    </a>
                    <a href="https://instagram.com/pet_rescue" target="_blank" class="d-flex align-items-center gap-2">
                        <i class="bi bi-instagram fs-5"></i> <span>pet_rescue</span>
                    </a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                <h5 class="text-uppercase fw-bold">Quick Links</h5>
                <ul class="list-unstyled mb-0">
                    <li><a href="<?= BASE_URL ?>index.php">Home Page</a></li>
                    <li><a href="<?= BASE_URL ?>about.php">About Us</a></li>
                    <li><a href="<?= BASE_URL ?>adoption.php">Find a Pet</a></li>
                    <li><a href="<?= BASE_URL ?>vets.php">Vets & Salons</a></li>
                    <li><a href="<?= BASE_URL ?>ngos.php">NGO Partners</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h5 class="text-uppercase fw-bold">Get in Touch</h5>
                <p><i class="bi bi-geo-alt-fill text-primary me-2"></i>Mangalore</p>
                <p><i class="bi bi-envelope-fill text-primary me-2"></i> help@petrescue.com</p>
                <p><i class="bi bi-telephone-fill text-primary me-2"></i>7760206717</p>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="text-uppercase fw-bold">Join the Mission</h5>
                <p class="small mb-4">Your support helps us provide medical care, food, and shelter to animals in
                    distress. Join our network of heroes.</p>
                <div class="d-grid">
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-primary rounded-pill shadow-sm">
                        <i class="bi bi-person-plus-fill me-2"></i> Join as Volunteer
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>
<div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
    © 2026 Copyright: <a class="text-white" href="#">PetRescue.com</a>
</div>
</footer>

<!-- Paws AI Chatbot -->
<div class="chat-widget-container">
    <button class="chat-toggle-btn" id="chatToggle">
        <i class="bi bi-chat-dots-fill"></i>
    </button>

    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <img src="<?= BASE_URL ?>assets/images/paws_ai.png" alt="Paws AI">
            <div>
                <h6>Paws AI Assistant</h6>
                <p>Online | Ask me anything!</p>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="message bot">
                Hi! 🐾 I'm Paws AI. How can I help you today?
            </div>
            <div class="quick-replies">
                <button class="quick-reply" onclick="handleQuickReply('How to adopt?')">Adoption info</button>
                <button class="quick-reply" onclick="handleQuickReply('Report an emergency')">Emergency Rescue</button>
                <button class="quick-reply" onclick="handleQuickReply('Book a vet')">Vet Services</button>
            </div>
            <div id="typingIndicator" class="typing-indicator">
                <div class="dots"><span></span><span></span><span></span></div>
            </div>
        </div>

        <div class="chat-input-area">
            <input type="text" class="chat-input" id="chatInput" placeholder="Type a message...">
            <button class="send-btn" id="sendMessage">
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/chat.css">
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const APP_BASE_URL = "<?= BASE_URL ?>";
</script>
<!-- Main JS -->
<script src="<?= BASE_URL ?>assets/js/main.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>assets/js/chat.js"></script>
</body>

</html>