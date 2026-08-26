<?php
require_once 'config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login to donate.'); window.location.href='" . BASE_URL . "login.php';</script>";
    exit;
}

$ngo_id = $_GET['ngo_id'] ?? null;

if (!$ngo_id) {
    header("Location: ngos.php");
    exit;
}

// Fetch NGO details
$stmt = $conn->prepare("SELECT * FROM ngos WHERE id = ?");
$stmt->execute([$ngo_id]);
$ngo = $stmt->fetch();

if (!$ngo) {
    die("NGO not found.");
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Strip commas for numeric processing
    $amount = str_replace(',', '', $_POST['amount']);
    $card_number = $_POST['card_number'];
    $expiry = $_POST['expiry'];
    $cvv = $_POST['cvv'];
    $user_id = $_SESSION['user_id'];

    // Mock Payment Validation
    $current_year = (int) date('y');
    $current_month = (int) date('m');
    $is_expiry_valid = false;

    if (preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry, $matches)) {
        $exp_month = (int) $matches[1];
        $exp_year = (int) $matches[2];
        if ($exp_year > $current_year || ($exp_year == $current_year && $exp_month >= $current_month)) {
            $is_expiry_valid = true;
        }
    }

    if ($amount < 1) {
        $message = '<div class="alert alert-danger">Minimum donation is ₹1.</div>';
    } elseif ($amount > 50000) {
        $message = '<div class="alert alert-danger">Maximum donation per transaction is ₹50,000.</div>';
    } elseif (!preg_match('/^[0-9]{16}$/', $card_number)) {
        $message = '<div class="alert alert-danger">Invalid Card Number (Enter 16 digits).</div>';
    } elseif (!$is_expiry_valid) {
        $message = '<div class="alert alert-danger">Invalid Expiry Date (Use MM/YY and ensure it is not expired).</div>';
    } elseif (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
        $message = '<div class="alert alert-danger">Invalid CVV (Should be 3 or 4 digits).</div>';
    } else {
        // Success
        try {
            $stmt = $conn->prepare("INSERT INTO donations (user_id, ngo_id, amount, payment_status) VALUES (?, ?, ?, 'completed')");
            $stmt->execute([$user_id, $ngo_id, $amount]);
            $formatted_amount = number_format($amount);
            $message = '<div class="alert alert-success">Thank you! Donation of ₹' . $formatted_amount . ' to ' . htmlspecialchars($ngo['ngo_name']) . ' was successful.</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error processing donation: ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6 animate-on-scroll">
            <div class="card shadow">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0">Donate to
                        <?= htmlspecialchars($ngo['ngo_name']) ?>
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?= $message ?>

                    <form method="POST">
                        <div class="text-center mb-4">
                            <i class="bi bi-credit-card-2-front text-success" style="font-size: 3rem;"></i>
                            <p class="text-muted">Secure Payment Gateway</p>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Donation Amount (₹)</label>
                            <input type="text" id="formattedAmount" name="amount" class="form-control form-control-lg"
                                placeholder="10,000"
                                value="<?= isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : '' ?>"
                                required>
                            <small class="text-muted">Min: ₹1 | Max: ₹50,000</small>
                        </div>

                        <hr>
                        <h6 class="text-secondary mb-3">Card Details</h6>

                        <div class="mb-3">
                            <label>Card Number</label>
                            <input type="text" name="card_number" class="form-control" placeholder="1234567812345678"
                                maxlength="16" pattern="[0-9]{16}" title="Please enter a 16-digit card number"
                                value="<?= isset($_POST['card_number']) ? htmlspecialchars($_POST['card_number']) : '' ?>"
                                required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Expiry Date</label>
                                <input type="text" id="expiryDate" name="expiry" class="form-control"
                                    placeholder="MM/YY" pattern="(0[1-9]|1[0-2])\/[0-9]{2}"
                                    title="Please enter date in MM/YY format"
                                    value="<?= isset($_POST['expiry']) ? htmlspecialchars($_POST['expiry']) : '' ?>"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>CVV</label>
                                <input type="password" name="cvv" class="form-control" placeholder="123" maxlength="4"
                                    pattern="[0-9]{3,4}" title="Please enter 3 or 4 digit CVV" required>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg rounded-pill fw-bold">Pay Now</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const expiryInput = document.getElementById('expiryDate');

        expiryInput.addEventListener('input', function (e) {
            // Remove any non-digit characters
            let value = this.value.replace(/\D/g, '');

            // Limit to 4 digits (MMYY)
            if (value.length > 4) {
                value = value.slice(0, 4);
            }

            // Add slash after 2 digits
            if (value.length > 2) {
                this.value = value.slice(0, 2) + '/' + value.slice(2);
            } else {
                this.value = value;
            }
        });

        // Handle backspace specifically to allow easy deletion of slash
        expiryInput.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && this.value.length === 3 && this.value.endsWith('/')) {
                this.value = this.value.slice(0, 2);
                e.preventDefault();
            }
        });

        // Amount Formatting Logic (e.g., 10,000)
        const amountInput = document.getElementById('formattedAmount');

        amountInput.addEventListener('input', function (e) {
            // Remove everything except numbers
            let cursorPosition = this.selectionStart;
            let originalLength = this.value.length;

            let value = this.value.replace(/[^0-9]/g, '');

            if (value === "") {
                this.value = "";
                return;
            }

            // Format with commas
            let formattedValue = new Intl.NumberFormat('en-IN').format(value);
            this.value = formattedValue;

            // Adjust cursor position to handle comma insertion
            let newLength = this.value.length;
            this.setSelectionRange(cursorPosition + (newLength - originalLength), cursorPosition + (newLength - originalLength));
        });
    });
</script>

<?php include 'includes/footer.php'; ?>