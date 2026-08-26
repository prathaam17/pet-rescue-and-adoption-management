// Main JS
document.addEventListener('DOMContentLoaded', function () {
    console.log("Pet Rescue App Loaded");

    // Auto-hide alerts
    setTimeout(function () {
        let alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function (alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Global File Size Validation (4MB)
    const MAX_FILE_SIZE = 4 * 1024 * 1024; // 4MB in bytes

    document.addEventListener('change', function (e) {
        if (e.target && e.target.type === 'file') {
            const file = e.target.files[0];
            if (file && file.size > MAX_FILE_SIZE) {
                alert("File is too large! Maximum allowed size is 4MB.");
                e.target.value = ''; // Clear the input
            }
        }
    });

    // Scroll Animation Observer
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15
    };

    const scrollObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            } else {
                // Remove the class to allow re-animation on scroll up/down
                entry.target.classList.remove('is-visible');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.animate-on-scroll, .animate-slide-left, .animate-slide-right, .animate-zoom-in').forEach(element => {
        scrollObserver.observe(element);
    });
});
