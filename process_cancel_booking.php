<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in
$isLoggedIn = is_logged_in();

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header("Location: auth/login.php?redirect=bookings.php");
    exit();
}

$userId = $_SESSION['user_id'];
$redirectUrl = 'bookings.php';
$message = '';
$messageType = '';

// Process booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    // Sanitize input
    $bookingId = (int)$_POST['booking_id'];
    
    // Verify booking belongs to current user
    $checkQuery = "SELECT b.*, s.price FROM bookings b 
                  JOIN services s ON b.service_id = s.service_id 
                  WHERE b.booking_id = $bookingId AND b.user_id = $userId";
    $checkResult = $conn->query($checkQuery);
    
    if ($checkResult && $checkResult->num_rows > 0) {
        $booking = $checkResult->fetch_assoc();
        
        // Check if booking can be cancelled (only pending or confirmed bookings)
        if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed') {
            // Calculate time until appointment
            $bookingDateTime = strtotime($booking['booking_date'] . ' ' . $booking['start_time']);
            $currentTime = time();
            $hoursRemaining = ($bookingDateTime - $currentTime) / 3600;
            
            // If less than 24 hours before appointment, apply cancellation fee logic
            $cancellationFee = 0;
            $cancellationNote = '';
            
            if ($hoursRemaining < 24) {
                // Apply 50% cancellation fee
                $cancellationFee = $booking['price'] * 0.5;
                $cancellationNote = "Late cancellation fee applied: $" . number_format($cancellationFee, 2);
            }
            
            // Update booking status to cancelled
            $notes = $booking['notes'] ?? '';
            if (!empty($notes)) {
                $notes .= "\n";
            }
            $notes .= $cancellationNote . "\nCancelled on: " . date('Y-m-d H:i:s');
            
            // Since there's no cancellation_fee column, we'll store this information in the notes
            $notesEscaped = $conn->real_escape_string($notes);
            $updateQuery = "UPDATE bookings SET 
                           status = 'cancelled', 
                           notes = '$notesEscaped'
                           WHERE booking_id = $bookingId";
            
            if ($conn->query($updateQuery)) {
                $message = "Your booking has been successfully cancelled.";
                if ($cancellationFee > 0) {
                    $message .= " Please note a cancellation fee of $" . number_format($cancellationFee, 2) . " has been applied.";
                }
                $messageType = "success";
                
                // Optionally: You could track the cancellation fee in a separate table
                // or update your system to handle late cancellation fees
            } else {
                $message = "Error cancelling booking: " . $conn->error;
                $messageType = "danger";
            }
        } else {
            $message = "This booking cannot be cancelled because it is already " . $booking['status'] . ".";
            $messageType = "warning";
        }
    } else {
        $message = "Invalid booking or you do not have permission to cancel this booking.";
        $messageType = "danger";
    }
} else {
    $message = "Invalid request.";
    $messageType = "danger";
}

// Store message in session to display after redirect
$_SESSION['message'] = $message;
$_SESSION['message_type'] = $messageType;

// Redirect back to bookings page
header("Location: $redirectUrl");
exit();
?>