<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if all required parameters are provided
if (!isset($_GET['staff_id']) || !isset($_GET['date']) || !isset($_GET['service_id'])) {
    echo json_encode([
        'error' => 'Missing required parameters',
        'unavailable_times' => []
    ]);
    exit();
}

// Get and sanitize parameters
$staffId = (int)$_GET['staff_id'];
$date = $_GET['date'];
$serviceId = (int)$_GET['service_id'];

// Validate parameters
if ($staffId <= 0 || $serviceId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([
        'error' => 'Invalid parameters',
        'unavailable_times' => []
    ]);
    exit();
}

// Get service duration
$durationQuery = "SELECT duration FROM services WHERE service_id = $serviceId";
$durationResult = $conn->query($durationQuery);

if (!$durationResult || $durationResult->num_rows === 0) {
    echo json_encode([
        'error' => 'Service not found',
        'unavailable_times' => []
    ]);
    exit();
}

$serviceData = $durationResult->fetch_assoc();
$serviceDuration = (int)$serviceData['duration'];

// Get working hours for the staff on the selected date
// In a real implementation, you might have a staff_schedule table to check
// For now, we'll assume standard working hours (9:00 AM to 7:00 PM)
$workingHoursStart = '09:00:00';
$workingHoursEnd = '19:00:00';

// Check if the selected date is in the past
$today = date('Y-m-d');
if ($date < $today) {
    echo json_encode([
        'error' => 'Cannot book appointments in the past',
        'unavailable_times' => getAllTimeSlots($workingHoursStart, $workingHoursEnd, 30)
    ]);
    exit();
}

// Check if the selected date is a Sunday (assuming salon is closed on Sundays)
$dayOfWeek = date('w', strtotime($date));
if ($dayOfWeek == 0) { // 0 = Sunday
    echo json_encode([
        'error' => 'The salon is closed on Sundays',
        'unavailable_times' => getAllTimeSlots($workingHoursStart, $workingHoursEnd, 30)
    ]);
    exit();
}

// Get all existing bookings for the staff on the selected date
$bookingsQuery = "SELECT start_time, end_time 
                 FROM bookings 
                 WHERE staff_id = $staffId 
                 AND booking_date = '$date' 
                 AND status != 'cancelled'
                 ORDER BY start_time ASC";

$bookingsResult = $conn->query($bookingsQuery);

// Array to store unavailable time slots
$unavailableTimes = [];

if ($bookingsResult && $bookingsResult->num_rows > 0) {
    while ($booking = $bookingsResult->fetch_assoc()) {
        // Calculate all time slots that overlap with this booking
        $startTime = new DateTime($booking['start_time']);
        $endTime = new DateTime($booking['end_time']);
        
        // Get all 30-minute slots that overlap with this booking
        $currentSlot = clone $startTime;
        while ($currentSlot < $endTime) {
            $unavailableTimes[] = $currentSlot->format('H:i:00');
            $currentSlot->add(new DateInterval('PT30M')); // Add 30 minutes
        }
    }
}

// Add unavailable times for time slots that don't allow enough time for the service
// before the end of working hours
$allTimeSlots = getAllTimeSlots($workingHoursStart, $workingHoursEnd, 30);
foreach ($allTimeSlots as $timeSlot) {
    $slotStart = new DateTime($timeSlot);
    $slotEnd = clone $slotStart;
    $slotEnd->add(new DateInterval('PT' . $serviceDuration . 'M'));
    
    $workingEnd = new DateTime($workingHoursEnd);
    
    // If the service would end after working hours, mark the slot as unavailable
    if ($slotEnd > $workingEnd) {
        $unavailableTimes[] = $timeSlot;
    }
}

// If the date is today, disable past time slots
if ($date === $today) {
    $currentTime = new DateTime();
    $currentTime->add(new DateInterval('PT30M')); // Add buffer of 30 minutes
    
    foreach ($allTimeSlots as $timeSlot) {
        $slotTime = new DateTime($date . ' ' . $timeSlot);
        if ($slotTime < $currentTime) {
            $unavailableTimes[] = $timeSlot;
        }
    }
}

// Remove duplicates
$unavailableTimes = array_unique($unavailableTimes);

// Return the data as JSON
echo json_encode([
    'staff_id' => $staffId,
    'date' => $date,
    'service_id' => $serviceId,
    'service_duration' => $serviceDuration,
    'unavailable_times' => $unavailableTimes
]);

/**
 * Generate all possible time slots between start and end times with a given interval
 *
 * @param string $startTime Start time in format HH:MM:SS
 * @param string $endTime End time in format HH:MM:SS
 * @param int $intervalMinutes Interval in minutes
 * @return array Array of time slots in format HH:MM:SS
 */
function getAllTimeSlots($startTime, $endTime, $intervalMinutes) {
    $timeSlots = [];
    $current = new DateTime($startTime);
    $end = new DateTime($endTime);
    
    while ($current < $end) {
        $timeSlots[] = $current->format('H:i:00');
        $current->add(new DateInterval('PT' . $intervalMinutes . 'M'));
    }
    
    return $timeSlots;
}
?>