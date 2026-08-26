<?php
require_once '../config.php';
include '../includes/header.php';

// No login enforcement. Guests allowed.

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'] ?? null;
    $location = trim($_POST['location']);
    $animal_type = $_POST['animal_type'];
    if ($animal_type === 'Other' && !empty($_POST['other_animal_type'])) {
        $animal_type = trim($_POST['other_animal_type']);
    }
    $condition = $_POST['condition'];
    $desc = trim($_POST['description']);

    // Guest fields
    $guest_name = null;
    $guest_contact = null;

    if (!$user_id) {
        $guest_name = trim($_POST['guest_name']);
        $guest_contact = trim($_POST['guest_contact']);

        if (empty($guest_name) || empty($guest_contact)) {
            $message = '<div class="alert alert-danger">Please provide your name and contact number.</div>';
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $guest_name)) {
            $message = '<div class="alert alert-danger">Name should only contain letters and spaces.</div>';
        } elseif (!preg_match('/^[0-9]{10}$/', $guest_contact)) {
            $message = '<div class="alert alert-danger">Phone number must be exactly 10 digits.</div>';
        }
    }

    // Only proceed if message is empty (no validation errors)
    if (empty($message)) {
        if (!empty($location) && !empty($desc)) {
            $photo_path = '';
            // File Upload Main Logic
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                $max_size = 4 * 1024 * 1024; // 4MB
                if ($_FILES['photo']['size'] > $max_size) {
                    $message = '<div class="alert alert-danger">Image size exceeds the 4MB limit.</div>';
                } else {
                    $upload_dir = '../uploads/';
                    if (!is_dir($upload_dir))
                        mkdir($upload_dir, 0777, true);

                    $filename = time() . '_' . basename($_FILES['photo']['name']);
                    $target_file = $upload_dir . $filename;

                    // Simple check
                    $check = getimagesize($_FILES['photo']['tmp_name']);
                    if ($check !== false) {
                        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                            $photo_path = 'uploads/' . $filename; // Relative to BASE_URL
                        } else {
                            $message = '<div class="alert alert-danger">Failed to upload image. Please try again.</div>';
                        }
                    } else {
                        $message = '<div class="alert alert-danger">File is not a valid image.</div>';
                    }
                }
            } else {
                $message = '<div class="alert alert-danger">Please upload a photo of the emergency.</div>';
            }

            if (empty($message)) {
                try {
                    // Modified Insert to include guest fields and precise location
                    $stmt = $conn->prepare("INSERT INTO rescue_requests (user_id, guest_name, guest_contact, animal_type, condition_type, description, location, latitude, longitude, photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->execute([$user_id, $guest_name, $guest_contact, $animal_type, $condition, $desc, $location, $_POST['latitude'] ?: null, $_POST['longitude'] ?: null, $photo_path]);

                    $successText = 'Rescue request submitted! Searching for nearby NGOs...';
                    if ($user_id) {
                        $successText .= ' <a href="dashboard.php">View Status</a>';
                    } else {
                        $successText .= ' (Thank you for reporting!)';
                    }
                    $message = '<div class="alert alert-success">' . $successText . '</div>';
                } catch (PDOException $e) {
                    $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
                }
            }
        } else {
            $message = '<div class="alert alert-danger">Please fill all required fields.</div>';
        }
    }
}
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger shadow">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0"><i class="bi bi-megaphone-fill"></i> Report Emergency Rescue</h3>
                </div>
                <div class="card-body p-4">
                    <?= $message ?>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-info border-info">
                            <i class="bi bi-info-circle-fill"></i> You are reporting as a <strong>Guest</strong>.
                            <a href="../login.php">Login</a> to track your request status.
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Location</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="location" id="locationInput" class="form-control"
                                    placeholder="Enter exact location or address"
                                    value="<?= isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '' ?>"
                                    required>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="getLocation()">Auto-Detect</button>
                            </div>
                            <small class="text-muted" id="coordDisplay">
                                <?= (isset($_POST['latitude']) && $_POST['latitude']) ? '<i class="bi bi-crosshair"></i> GPS: ' . htmlspecialchars($_POST['latitude']) . ', ' . htmlspecialchars($_POST['longitude']) : '' ?>
                            </small>
                            <input type="hidden" name="latitude" id="latInput"
                                value="<?= isset($_POST['latitude']) ? htmlspecialchars($_POST['latitude']) : '' ?>">
                            <input type="hidden" name="longitude" id="lngInput"
                                value="<?= isset($_POST['longitude']) ? htmlspecialchars($_POST['longitude']) : '' ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Animal Type</label>
                                <select name="animal_type" id="animalTypeSelect" class="form-select"
                                    onchange="toggleOtherAnimal()" required>
                                    <option value="Dog" <?= (isset($_POST['animal_type']) && $_POST['animal_type'] == 'Dog') ? 'selected' : '' ?>>Dog</option>
                                    <option value="Cat" <?= (isset($_POST['animal_type']) && $_POST['animal_type'] == 'Cat') ? 'selected' : '' ?>>Cat</option>
                                    <option value="Bird" <?= (isset($_POST['animal_type']) && $_POST['animal_type'] == 'Bird') ? 'selected' : '' ?>>Bird</option>
                                    <option value="Cow" <?= (isset($_POST['animal_type']) && $_POST['animal_type'] == 'Cow') ? 'selected' : '' ?>>Cow</option>
                                    <option value="Other" <?= (isset($_POST['animal_type']) && $_POST['animal_type'] == 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                                <div id="otherAnimalField" class="mt-2"
                                    style="display: <?= (isset($_POST['animal_type']) && $_POST['animal_type'] == 'Other') ? 'block' : 'none' ?>;">
                                    <input type="text" name="other_animal_type" id="otherAnimalInput"
                                        class="form-control" placeholder="Please specify animal..."
                                        value="<?= isset($_POST['other_animal_type']) ? htmlspecialchars($_POST['other_animal_type']) : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Condition</label>
                                <select name="condition" class="form-select" required>
                                    <option value="Injured" <?= (isset($_POST['condition']) && $_POST['condition'] == 'Injured') ? 'selected' : '' ?>>Injured</option>
                                    <option value="Sick" <?= (isset($_POST['condition']) && $_POST['condition'] == 'Sick') ? 'selected' : '' ?>>Sick</option>
                                    <option value="Abandoned" <?= (isset($_POST['condition']) && $_POST['condition'] == 'Abandoned') ? 'selected' : '' ?>>Abandoned</option>
                                    <option value="Abused" <?= (isset($_POST['condition']) && $_POST['condition'] == 'Abused') ? 'selected' : '' ?>>Abused</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Description of Situation</label>
                            <textarea name="description" class="form-control" rows="3"
                                placeholder="Describe the injury or situation..."
                                required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Upload Photo <span class="text-danger">*</span></label>
                            <input type="file" name="photo" class="form-control" accept="image/*" required>
                        </div>

                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <hr>
                            <h5 class="text-secondary mb-3">Your Contact Details</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Your Name</label>
                                    <input type="text" name="guest_name" class="form-control" required
                                        placeholder="John Doe" pattern="[a-zA-Z\s]+" title="Only letters and spaces allowed"
                                        value="<?= isset($_POST['guest_name']) ? htmlspecialchars($_POST['guest_name']) : '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Phone Number</label>
                                    <input type="text" name="guest_contact" class="form-control" required
                                        placeholder="To contact you for rescue info" pattern="\d{10}" maxlength="10"
                                        title="Exactly 10 digits required"
                                        value="<?= isset($_POST['guest_contact']) ? htmlspecialchars($_POST['guest_contact']) : '' ?>">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-danger btn-lg rounded-pill fw-bold">SUBMIT RESCUE
                                REQUEST</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function getLocation() {
        const btn = document.querySelector('button[onclick="getLocation()"]');
        if (navigator.geolocation) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Detecting...';
            btn.disabled = true;
            navigator.geolocation.getCurrentPosition(showPosition, showError, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        } else {
            alert("Geolocation is not supported by this browser.");
        }
    }

    function showPosition(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const btn = document.querySelector('button[onclick="getLocation()"]');

        // Update hidden inputs
        document.getElementById("latInput").value = lat;
        document.getElementById("lngInput").value = lng;
        const coordDisplay = document.getElementById("coordDisplay");
        if (coordDisplay) {
            coordDisplay.innerHTML = `<i class="bi bi-crosshair"></i> GPS: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }

        // Reverse Geocoding using Nominatim (OpenStreetMap)
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`;

        fetch(url, {
            headers: {
                'User-Agent': 'PetRescueApp/1.0'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data && data.display_name) {
                    document.getElementById("locationInput").value = data.display_name;
                } else {
                    document.getElementById("locationInput").value = "Lat: " + lat + ", Lng: " + lng;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById("locationInput").value = "Lat: " + lat + ", Lng: " + lng;
                alert("Could not fetch address details. Coordinates filled instead.");
            })
            .finally(() => {
                btn.innerHTML = 'Auto-Detect';
                btn.disabled = false;
            });
    }

    function showError(error) {
        const btn = document.querySelector('button[onclick="getLocation()"]');
        btn.innerHTML = 'Auto-Detect';
        btn.disabled = false;

        switch (error.code) {
            case error.PERMISSION_DENIED:
                alert("User denied the request for Geolocation.");
                break;
            case error.POSITION_UNAVAILABLE:
                alert("Location information is unavailable.");
                break;
            case error.TIMEOUT:
                alert("The request to get user location timed out.");
                break;
            default:
                alert("An unknown error occurred.");
                break;
        }
    }
    function toggleOtherAnimal() {
        var animalSelect = document.getElementById("animalTypeSelect");
        var otherField = document.getElementById("otherAnimalField");
        var otherInput = document.getElementById("otherAnimalInput");

        if (animalSelect.value === "Other") {
            otherField.style.display = "block";
            otherInput.required = true;
        } else {
            otherField.style.display = "none";
            otherInput.required = false;
            otherInput.value = "";
        }
    }
</script>

<?php include '../includes/footer.php'; ?>