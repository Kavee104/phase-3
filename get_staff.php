<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if service_id is provided
if (!isset($_GET['service_id']) || !is_numeric($_GET['service_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid service ID']);
    exit();
}

$serviceId = (int)$_GET['service_id'];

// Get staff who can perform this service
$staffQuery = "SELECT s.staff_id, u.first_name, u.last_name, s.specialization 
              FROM staff s 
              JOIN users u ON s.user_id = u.user_id 
              JOIN staff_services ss ON s.staff_id = ss.staff_id 
              WHERE ss.service_id = $serviceId AND s.is_active = 1 
              ORDER BY u.first_name, u.last_name";
$staffResult = $conn->query($staffQuery);

$staffList = [];
if ($staffResult && $staffResult->num_rows > 0) {
    while ($staff = $staffResult->fetch_assoc()) {
        $staffList[] = [
            'staff_id' => $staff['staff_id'],
            'first_name' => $staff['first_name'],
            'last_name' => $staff['last_name'],
            'specialization' => $staff['specialization']
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($staffList);
?>