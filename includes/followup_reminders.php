<?php
require_once dirname(__DIR__) . '/config.php';

/**
 * Simulates sending an email notification
 */
function sendFollowupEmail($to_email, $to_name, $pet_name, $due_date, $type = 'adopter')
{
    $subject = ($type == 'adopter')
        ? "Reminder: Your follow-up update for $pet_name is due!"
        : "Notification: A follow-up update for $pet_name is due soon.";

    $message = "Dear $to_name,\n\n";
    if ($type == 'adopter') {
        $message .= "This is a reminder that you need to upload a photo for $pet_name by $due_date.\n";
        $message .= "Please log in to your dashboard to submit the update.\n";
    } else {
        $message .= "This is to inform you that the adopter of $pet_name is scheduled to provide an update by $due_date.\n";
    }

    $message .= "\nBest regards,\nPet Rescue Team";

    // Since we don't have a real mail server, we log it to a file or just return true
    error_log("SIMULATED EMAIL SENT TO $to_email: $subject");
    return true;
}

/**
 * Checks for follow-ups due in the next 3 days and sends reminders
 */
function processFollowupReminders($conn)
{
    $today = date('Y-m-d');
    $reminder_limit = date('Y-m-d', strtotime('+3 days'));

    // Find pending follow-ups due soon
    $stmt = $conn->prepare("SELECT f.*, p.pet_name, p.user_id as giver_id, u_ad.email as adopter_email, u_ad.name as adopter_name, u_gv.email as giver_email, u_gv.name as giver_name
                           FROM ownership_followups f
                           JOIN adoption_posts p ON f.pet_id = p.id
                           JOIN users u_ad ON f.adopter_id = u_ad.id
                           JOIN users u_gv ON p.user_id = u_gv.id
                           WHERE f.status = 'pending' AND f.due_date <= ?");
    $stmt->execute([$reminder_limit]);
    $overdue = $stmt->fetchAll();

    foreach ($overdue as $row) {
        // Send to Adopter
        sendFollowupEmail($row['adopter_email'], $row['adopter_name'], $row['pet_name'], $row['due_date'], 'adopter');
        // Send to Giver (Previous owner/NGO)
        sendFollowupEmail($row['giver_email'], $row['giver_name'], $row['pet_name'], $row['due_date'], 'giver');
    }

    return count($overdue);
}

// If run directly (e.g. via cron)
if (php_sapi_name() == "cli") {
    echo "Processing follow-up reminders...\n";
    $count = processFollowupReminders($conn);
    echo "Processed $count reminders.\n";
}
?>