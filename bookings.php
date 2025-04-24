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

$userName = $_SESSION['first_name'];
$userId = $_SESSION['user_id'];

// Initialize variables
$message = '';
$messageType = '';
$selectedServiceId = isset($_GET['service']) ? (int)$_GET['service'] : 0;
$selectedStaffId = isset($_GET['staff']) ? (int)$_GET['staff'] : 0;
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Process booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    // Sanitize and validate input
    $serviceId = (int)$_POST['service_id'];
    $staffId = isset($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;
    $bookingDate = $_POST['booking_date'];
    $startTime = $_POST['start_time'];
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    // Validate data
    $errors = [];
    
    if ($serviceId <= 0) {
        $errors[] = "Please select a valid service.";
    }
    
    if (empty($bookingDate)) {
        $errors[] = "Please select a booking date.";
    } else {
        // Check if date is in the future
        $today = date('Y-m-d');
        if ($bookingDate < $today) {
            $errors[] = "Booking date must be in the future.";
        }
    }
    
    if (empty($startTime)) {
        $errors[] = "Please select an appointment time.";
    }
    
    // If no errors, proceed with booking
    if (empty($errors)) {
        // Get service duration to calculate end time
        $durationQuery = "SELECT duration FROM services WHERE service_id = $serviceId";
        $durationResult = $conn->query($durationQuery);
        $serviceData = $durationResult->fetch_assoc();
        $duration = $serviceData['duration'];
        
        // Calculate end time by adding duration in minutes to start time
        $startDateTime = new DateTime($startTime);
        $endDateTime = clone $startDateTime;
        $endDateTime->add(new DateInterval('PT' . $duration . 'M'));
        $endTime = $endDateTime->format('H:i:s');
        
        // Check for conflicts with existing bookings for the selected staff
        $conflictQuery = "SELECT * FROM bookings 
                         WHERE staff_id = $staffId 
                         AND booking_date = '$bookingDate' 
                         AND status != 'cancelled'
                         AND ((start_time <= '$startTime' AND end_time > '$startTime') 
                             OR (start_time < '$endTime' AND end_time >= '$endTime')
                             OR (start_time >= '$startTime' AND end_time <= '$endTime'))";
        
        $conflictResult = $conn->query($conflictQuery);
        
        if ($conflictResult->num_rows > 0) {
            $message = "The selected time slot is already booked. Please choose another time.";
            $messageType = "danger";
        } else {
            // Insert booking into database
            $insertQuery = "INSERT INTO bookings (user_id, staff_id, service_id, booking_date, start_time, end_time, status, notes) 
                           VALUES ($userId, $staffId, $serviceId, '$bookingDate', '$startTime', '$endTime', 'pending', '$notes')";
            
            if ($conn->query($insertQuery)) {
                $message = "Your booking has been successfully submitted and is awaiting confirmation.";
                $messageType = "success";
                
                // Reset form values after successful submission
                $selectedServiceId = 0;
                $selectedStaffId = 0;
                $selectedDate = date('Y-m-d');
            } else {
                $message = "Error creating booking: " . $conn->error;
                $messageType = "danger";
            }
        }
    } else {
        $message = "Please correct the following errors: <ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
        $messageType = "danger";
    }
}

// Fetch all service categories
$categoryQuery = "SELECT * FROM service_categories ORDER BY name";
$categoryResult = cached_query($conn, $categoryQuery);

// Fetch all services grouped by category
$servicesQuery = "SELECT s.*, c.name as category_name 
                 FROM services s 
                 JOIN service_categories c ON s.category_id = c.category_id 
                 WHERE s.is_active = 1 
                 ORDER BY c.name, s.name";
$servicesResult = cached_query($conn, $servicesQuery);

// If a service is selected, get staff who can perform this service
$staffOptions = [];
if ($selectedServiceId > 0) {
    $staffQuery = "SELECT s.staff_id, u.first_name, u.last_name, s.specialization 
                  FROM staff s 
                  JOIN users u ON s.user_id = u.user_id 
                  JOIN staff_services ss ON s.staff_id = ss.staff_id 
                  WHERE ss.service_id = $selectedServiceId AND s.is_active = 1 
                  ORDER BY u.first_name, u.last_name";
    $staffResult = $conn->query($staffQuery);
    
    if ($staffResult && $staffResult->num_rows > 0) {
        while ($staff = $staffResult->fetch_assoc()) {
            $staffOptions[] = $staff;
        }
    }
}

// Get user's existing bookings
$bookingsQuery = "SELECT b.*, s.name as service_name, s.price, s.duration,
                 CONCAT(u.first_name, ' ', u.last_name) as staff_name 
                 FROM bookings b 
                 JOIN services s ON b.service_id = s.service_id 
                 LEFT JOIN staff st ON b.staff_id = st.staff_id 
                 LEFT JOIN users u ON st.user_id = u.user_id
                 WHERE b.user_id = $userId 
                 ORDER BY b.booking_date DESC, b.start_time DESC";
$bookingsResult = $conn->query($bookingsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Luminas Hair & Beauty Salon</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Flatpickr for Date/Time Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
    
    <style>
        /* Additional booking page specific styles */
        .booking-section {
            padding-top: 120px;
            padding-bottom: 80px;
        }
        
        .booking-card {
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .booking-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .booking-body {
            padding: 25px;
        }
        
        .time-slot {
            display: inline-block;
            padding: 10px 15px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .time-slot:hover {
            background-color: #f0f0f0;
        }
        
        .time-slot.active {
            background-color: #6c5ce7;
            color: white;
            border-color: #6c5ce7;
        }
        
        .time-slot.unavailable {
            background-color: #f8f9fa;
            color: #aaa;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        
        .booking-history-card {
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .booking-pending {
            border-left-color: #ffc107;
        }
        
        .booking-confirmed {
            border-left-color: #28a745;
        }
        
        .booking-completed {
            border-left-color: #17a2b8;
        }
        
        .booking-cancelled {
            border-left-color: #dc3545;
        }
        
        .staff-card {
            cursor: pointer;
            transition: all 0.3s;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid transparent;
        }
        
        .staff-card:hover {
            background-color: #f8f9fa;
        }
        
        .staff-card.selected {
            border-color: #6c5ce7;
            background-color: #f0efff;
        }
        
        .staff-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Step progress indicator */
        .booking-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 30px;
        }
        
        .booking-steps:before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            background: white;
            width: 20%;
            text-align: center;
        }
        
        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e0e0e0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }
        
        .step.active .step-icon {
            background: #6c5ce7;
        }
        
        .step.completed .step-icon {
            background: #28a745;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="brand-text">LUMINAS</span>
                <span class="brand-subtext">HAIR & BEAUTY</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                Welcome, <?php echo htmlspecialchars($userName); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item active" href="bookings.php">My Bookings</a></li>
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn-outline-primary rounded-pill px-3" href="auth/login.php">Login</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="nav-link btn-primary text-white rounded-pill px-3" href="auth/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Booking Section -->
    <section class="booking-section">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h1>Book Your Appointment</h1>
                <p class="text-muted">Schedule your next beauty treatment with ease</p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="booking-card">
                        <div class="booking-header">
                            <h3>New Appointment</h3>
                        </div>
                        <div class="booking-body">
                            <div class="booking-steps mb-4">
                                <div class="step active" id="step1">
                                    <div class="step-icon">1</div>
                                    <div class="step-title">Service</div>
                                </div>
                                <div class="step" id="step2">
                                    <div class="step-icon">2</div>
                                    <div class="step-title">Staff</div>
                                </div>
                                <div class="step" id="step3">
                                    <div class="step-icon">3</div>
                                    <div class="step-title">Date</div>
                                </div>
                                <div class="step" id="step4">
                                    <div class="step-icon">4</div>
                                    <div class="step-title">Time</div>
                                </div>
                                <div class="step" id="step5">
                                    <div class="step-icon">5</div>
                                    <div class="step-title">Confirm</div>
                                </div>
                            </div>
                            
                            <form action="bookings.php" method="post" id="bookingForm">
                                <!-- Step 1: Service Selection -->
                                <div class="booking-step-content" id="step1-content">
                                    <h4 class="mb-3">Select a Service</h4>
                                    
                                    <!-- Service Categories Tabs -->
                                    <ul class="nav nav-tabs mb-4" id="serviceTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                                                All Services
                                            </button>
                                        </li>
                                        <?php 
                                        if ($categoryResult && $categoryResult->num_rows > 0):
                                            mysqli_data_seek($categoryResult, 0); // Reset pointer to beginning
                                            while ($category = $categoryResult->fetch_assoc()): 
                                        ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="cat<?php echo $category['category_id']; ?>-tab" data-bs-toggle="tab" 
                                                    data-bs-target="#cat<?php echo $category['category_id']; ?>" type="button" role="tab">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </button>
                                        </li>
                                        <?php 
                                            endwhile;
                                        endif;
                                        ?>
                                    </ul>
                                    
                                    <!-- Service List -->
                                    <div class="tab-content" id="serviceTabContent">
                                        <div class="tab-pane fade show active" id="all" role="tabpanel">
                                            <div class="row">
                                                <?php 
                                                if ($servicesResult && $servicesResult->num_rows > 0):
                                                    mysqli_data_seek($servicesResult, 0); // Reset pointer
                                                    while ($service = $servicesResult->fetch_assoc()): 
                                                        $isSelected = ($selectedServiceId == $service['service_id']);
                                                ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="card service-select-card <?php echo $isSelected ? 'border-primary' : ''; ?>" 
                                                         onclick="selectService(<?php echo $service['service_id']; ?>)">
                                                        <div class="card-body">
                                                            <div class="form-check">
                                                                <input class="form-check-input service-radio" type="radio" name="service_radio" 
                                                                       id="service<?php echo $service['service_id']; ?>" 
                                                                       value="<?php echo $service['service_id']; ?>"
                                                                       <?php echo $isSelected ? 'checked' : ''; ?>>
                                                                <label class="form-check-label w-100" for="service<?php echo $service['service_id']; ?>">
                                                                    <h5 class="mb-1"><?php echo htmlspecialchars($service['name']); ?></h5>
                                                                    <p class="text-muted mb-1 small"><?php echo htmlspecialchars($service['category_name']); ?></p>
                                                                    <div class="d-flex justify-content-between">
                                                                        <span class="text-primary fw-bold">$<?php echo number_format($service['price'], 2); ?></span>
                                                                        <span class="text-muted"><i class="far fa-clock"></i> <?php echo $service['duration']; ?> min</span>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php 
                                                    endwhile;
                                                endif;
                                                ?>
                                            </div>
                                        </div>
                                        
                                        <?php 
                                        if ($categoryResult && $categoryResult->num_rows > 0):
                                            mysqli_data_seek($categoryResult, 0); // Reset pointer
                                            while ($category = $categoryResult->fetch_assoc()): 
                                        ?>
                                        <div class="tab-pane fade" id="cat<?php echo $category['category_id']; ?>" role="tabpanel">
                                            <div class="row">
                                                <?php 
                                                if ($servicesResult && $servicesResult->num_rows > 0):
                                                    mysqli_data_seek($servicesResult, 0); // Reset pointer
                                                    $foundService = false;
                                                    
                                                    while ($service = $servicesResult->fetch_assoc()): 
                                                        if ($service['category_id'] == $category['category_id']):
                                                            $foundService = true;
                                                            $isSelected = ($selectedServiceId == $service['service_id']);
                                                ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="card service-select-card <?php echo $isSelected ? 'border-primary' : ''; ?>" 
                                                         onclick="selectService(<?php echo $service['service_id']; ?>)">
                                                        <div class="card-body">
                                                            <div class="form-check">
                                                                <input class="form-check-input service-radio" type="radio" name="service_radio" 
                                                                       id="service<?php echo $service['service_id']; ?>" 
                                                                       value="<?php echo $service['service_id']; ?>"
                                                                       <?php echo $isSelected ? 'checked' : ''; ?>>
                                                                <label class="form-check-label w-100" for="service<?php echo $service['service_id']; ?>">
                                                                    <h5 class="mb-1"><?php echo htmlspecialchars($service['name']); ?></h5>
                                                                    <div class="d-flex justify-content-between">
                                                                        <span class="text-primary fw-bold">$<?php echo number_format($service['price'], 2); ?></span>
                                                                        <span class="text-muted"><i class="far fa-clock"></i> <?php echo $service['duration']; ?> min</span>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php 
                                                        endif;
                                                    endwhile;
                                                    
                                                    if (!$foundService): 
                                                ?>
                                                <div class="col-12">
                                                    <p>No services available in this category.</p>
                                                </div>
                                                <?php 
                                                    endif;
                                                endif;
                                                ?>
                                            </div>
                                        </div>
                                        <?php 
                                            endwhile;
                                        endif;
                                        ?>
                                    </div>

                                    <input type="hidden" name="service_id" id="selectedService" value="<?php echo $selectedServiceId; ?>">
                                    
                                    <div class="mt-4 d-flex justify-content-end">
                                        <button type="button" class="btn btn-primary" id="step1Next" disabled>Next: Select Staff</button>
                                    </div>
                                </div>
                                
                                <!-- Step 2: Staff Selection -->
                                <div class="booking-step-content" id="step2-content" style="display: none;">
                                    <h4 class="mb-3">Select a Staff Member</h4>
                                    
                                    <div class="row staff-selection">
                                        <?php if (empty($staffOptions)): ?>
                                            <div class="col-12">
                                                <p class="text-muted">Please select a service first to see available staff members.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($staffOptions as $staff): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="card h-100 staff-card <?php echo ($selectedStaffId == $staff['staff_id']) ? 'selected' : ''; ?>" 
                                                         onclick="selectStaff(<?php echo $staff['staff_id']; ?>)">
                                                        <div class="card-body">
                                                            <div class="d-flex align-items-center">
                                                                <img src="images/staff/default-avatar.jpg" alt="<?php echo htmlspecialchars($staff['first_name']); ?>" class="staff-image me-3">
                                                                <div>
                                                                    <h5 class="mb-1"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h5>
                                                                    <p class="text-muted mb-0 small"><?php echo htmlspecialchars($staff['specialization']); ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <input type="hidden" name="staff_id" id="selectedStaff" value="<?php echo $selectedStaffId; ?>">
                                    
                                    <div class="mt-4 d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" id="step2Back">Back</button>
                                        <button type="button" class="btn btn-primary" id="step2Next" <?php echo empty($staffOptions) ? 'disabled' : ''; ?>>Next: Select Date</button>
                                    </div>
                                </div>
                                
                                <!-- Step 3: Date Selection -->
                                <div class="booking-step-content" id="step3-content" style="display: none;">
                                    <h4 class="mb-3">Select a Date</h4>
                                    
                                    <div class="form-group mb-4">
                                        <label for="booking_date" class="form-label">Appointment Date</label>
                                        <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                               min="<?php echo date('Y-m-d'); ?>" value="<?php echo $selectedDate; ?>">
                                    </div>
                                    
                                    <div class="mt-4 d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" id="step3Back">Back</button>
                                        <button type="button" class="btn btn-primary" id="step3Next">Next: Select Time</button>
                                    </div>
                                </div>
                                
                                <!-- Step 4: Time Selection -->
                                <div class="booking-step-content" id="step4-content" style="display: none;">
                                    <h4 class="mb-3">Select an Appointment Time</h4>
                                    
                                    <div class="time-slots mb-4">
                                        <div class="mb-3">
                                            <label class="form-label">Morning</label>
                                            <div>
                                                <?php
                                                $timeSlots = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30'];
                                                foreach ($timeSlots as $time) {
                                                    echo '<div class="time-slot" data-time="' . $time . ':00">' . $time . '</div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Afternoon</label>
                                            <div>
                                                <?php
                                                $timeSlots = ['12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30'];
                                                foreach ($timeSlots as $time) {
                                                    echo '<div class="time-slot" data-time="' . $time . ':00">' . $time . '</div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="form-label">Evening</label>
                                            <div>
                                                <?php
                                                $timeSlots = ['17:00', '17:30', '18:00', '18:30'];
                                                foreach ($timeSlots as $time) {
                                                    echo '<div class="time-slot" data-time="' . $time . ':00">' . $time . '</div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="start_time" id="selectedTime" value="">
                                    
                                    <div class="mt-4 d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" id="step4Back">Back</button>
                                        <button type="button" class="btn btn-primary" id="step4Next" disabled>Next: Confirm Booking</button>
                                    </div>
                                </div>
                                
                                <!-- Step 5: Confirmation -->
                                <div class="booking-step-content" id="step5-content" style="display: none;">
                                    <h4 class="mb-3">Confirm Your Booking</h4>
                                    
                                    <div class="booking-summary card">
                                        <div class="card-body">
                                            <h5>Booking Summary</h5>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <p><strong>Service:</strong> <span id="summaryService"></span></p>
                                                    <p><strong>Staff Member:</strong> <span id="summaryStaff"></span></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Date:</strong> <span id="summaryDate"></span></p>
                                                    <p><strong>Time:</strong> <span id="summaryTime"></span></p>
                                                </div>
                                            </div><div class="form-group mt-4">
                                                <label for="notes" class="form-label">Additional Notes (Optional)</label>
                                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any special requests or information"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" id="step5Back">Back</button>
                                        <button type="submit" name="submit_booking" class="btn btn-success">Confirm Booking</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="booking-card">
                        <div class="booking-header">
                            <h3>Booking History</h3>
                        </div>
                        <div class="booking-body">
                            <?php if ($bookingsResult && $bookingsResult->num_rows > 0): ?>
                                <?php while ($booking = $bookingsResult->fetch_assoc()): ?>
                                    <?php
                                    $statusClass = '';
                                    switch ($booking['status']) {
                                        case 'pending':
                                            $statusClass = 'booking-pending';
                                            break;
                                        case 'confirmed':
                                            $statusClass = 'booking-confirmed';
                                            break;
                                        case 'completed':
                                            $statusClass = 'booking-completed';
                                            break;
                                        case 'cancelled':
                                            $statusClass = 'booking-cancelled';
                                            break;
                                    }
                                    
                                    $formattedDate = date('F j, Y', strtotime($booking['booking_date']));
                                    $formattedStartTime = date('g:i A', strtotime($booking['start_time']));
                                    $formattedEndTime = date('g:i A', strtotime($booking['end_time']));
                                    ?>
                                    
                                    <div class="card mb-3 <?php echo $statusClass; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <h5 class="card-title"><?php echo htmlspecialchars($booking['service_name']); ?></h5>
                                                <span class="badge <?php echo $booking['status'] == 'cancelled' ? 'bg-danger' : ($booking['status'] == 'confirmed' ? 'bg-success' : ($booking['status'] == 'completed' ? 'bg-info' : 'bg-warning')); ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </div>
                                            <p class="card-text mb-1">
                                                <i class="far fa-calendar-alt me-2"></i> <?php echo $formattedDate; ?>
                                            </p>
                                            <p class="card-text mb-1">
                                                <i class="far fa-clock me-2"></i> <?php echo $formattedStartTime . ' - ' . $formattedEndTime; ?>
                                            </p>
                                            <p class="card-text mb-1">
                                                <i class="far fa-user me-2"></i> <?php echo htmlspecialchars($booking['staff_name']); ?>
                                            </p>
                                            
                                            <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-sm btn-outline-danger cancel-booking" data-booking-id="<?php echo $booking['booking_id']; ?>">Cancel Booking</button>
                                                    <?php if ($booking['status'] == 'confirmed'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-primary ms-2 reschedule-booking" data-booking-id="<?php echo $booking['booking_id']; ?>">Reschedule</button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="far fa-calendar-plus fa-3x text-muted mb-3"></i>
                                    <p>You don't have any bookings yet.</p>
                                    <p class="text-muted">Your booking history will appear here once you make an appointment.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this booking? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <form id="cancelBookingForm" action="process_cancel_booking.php" method="post">
                        <input type="hidden" name="booking_id" id="cancel_booking_id" value="">
                        <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer bg-dark text-white">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h4>LUMINAS HAIR & BEAUTY</h4>
                    <p class="text-muted mt-3">Experience luxury hair and beauty services in a serene environment. Our team of professionals is dedicated to making you look and feel your best.</p>
                    <div class="social-icons mt-4">
                        <a href="#" class="me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled footer-links mt-3">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> 123 Beauty Lane, Colombo 04</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i> +94 11 234 5678</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i> info@luminasbeauty.lk</li>
                        <li><i class="fas fa-clock me-2"></i> Mon - Sat: 9:00 AM - 7:00 PM</li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled footer-links mt-3">
                        <li class="mb-2"><a href="index.php">Home</a></li>
                        <li class="mb-2"><a href="services.php">Services</a></li>
                        <li class="mb-2"><a href="about.php">About Us</a></li>
                        <li class="mb-2"><a href="gallery.php">Gallery</a></li>
                        <li class="mb-2"><a href="contact.php">Contact</a></li>
                        <li><a href="bookings.php">Book Now</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="bottom-footer py-3">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">© 2025 Luminas Hair & Beauty. All Rights Reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="privacy-policy.php" class="text-white me-3">Privacy Policy</a>
                        <a href="terms-conditions.php" class="text-white">Terms & Conditions</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize variables for booking data
            let currentStep = 1;
            let selectedServiceId = <?php echo $selectedServiceId ?: 0; ?>;
            let selectedStaffId = <?php echo $selectedStaffId ?: 0; ?>;
            let selectedDate = '<?php echo $selectedDate; ?>';
            let selectedTime = '';
            let serviceData = {};
            let staffData = {};
            
            // Get service data for the selected service
            if (selectedServiceId > 0) {
                const serviceElements = document.querySelectorAll('.service-select-card');
                serviceElements.forEach(element => {
                    const radioInput = element.querySelector('.service-radio');
                    if (radioInput && radioInput.value == selectedServiceId) {
                        const serviceName = element.querySelector('h5').innerText;
                        const servicePrice = element.querySelector('.text-primary').innerText;
                        const serviceDuration = element.querySelector('.text-muted:last-child').innerText;
                        
                        serviceData = {
                            id: selectedServiceId,
                            name: serviceName,
                            price: servicePrice,
                            duration: serviceDuration
                        };
                    }
                });
            }
            
            // Function to show the current step
            function showStep(stepNumber) {
                // Hide all steps
                document.querySelectorAll('.booking-step-content').forEach(step => {
                    step.style.display = 'none';
                });
                
                // Show the current step
                document.getElementById(`step${stepNumber}-content`).style.display = 'block';
                
                // Update step indicators
                document.querySelectorAll('.step').forEach((step, index) => {
                    const stepNum = index + 1;
                    if (stepNum < stepNumber) {
                        step.classList.add('completed');
                        step.classList.remove('active');
                    } else if (stepNum === stepNumber) {
                        step.classList.add('active');
                        step.classList.remove('completed');
                    } else {
                        step.classList.remove('active', 'completed');
                    }
                });
                
                currentStep = stepNumber;
            }
            
            // Function to select a service
            window.selectService = function(serviceId) {
                selectedServiceId = serviceId;
                document.getElementById('selectedService').value = serviceId;
                
                // Update UI to show selected service
                document.querySelectorAll('.service-select-card').forEach(card => {
                    card.classList.remove('border-primary');
                    const radio = card.querySelector('.service-radio');
                    if (radio && radio.value == serviceId) {
                        radio.checked = true;
                        card.classList.add('border-primary');
                        
                        // Store service data
                        const serviceName = card.querySelector('h5').innerText;
                        const servicePrice = card.querySelector('.text-primary').innerText;
                        const serviceDuration = card.querySelector('.text-muted:last-child').innerText;
                        
                        serviceData = {
                            id: serviceId,
                            name: serviceName,
                            price: servicePrice,
                            duration: serviceDuration
                        };
                    }
                });
                
                // Enable next button
                document.getElementById('step1Next').disabled = false;
                
                // Load staff for this service via AJAX
                loadStaffForService(serviceId);
            };
            
            // Function to load staff for a service
            function loadStaffForService(serviceId) {
                fetch(`get_staff.php?service_id=${serviceId}`)
                    .then(response => response.json())
                    .then(data => {
                        const staffContainer = document.querySelector('.staff-selection');
                        if (data.length > 0) {
                            let staffHtml = '';
                            data.forEach(staff => {
                                const isSelected = (selectedStaffId == staff.staff_id);
                                staffHtml += `
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 staff-card ${isSelected ? 'selected' : ''}" 
                                             onclick="selectStaff(${staff.staff_id})">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <img src="images/staff/default-avatar.jpg" alt="${staff.first_name}" class="staff-image me-3">
                                                    <div>
                                                        <h5 class="mb-1">${staff.first_name} ${staff.last_name}</h5>
                                                        <p class="text-muted mb-0 small">${staff.specialization}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                
                                // Store staff data if selected
                                if (isSelected) {
                                    staffData = {
                                        id: staff.staff_id,
                                        name: `${staff.first_name} ${staff.last_name}`,
                                        specialization: staff.specialization
                                    };
                                }
                            });
                            staffContainer.innerHTML = staffHtml;
                            document.getElementById('step2Next').disabled = false;
                        } else {
                            staffContainer.innerHTML = '<div class="col-12"><p>No staff available for this service.</p></div>';
                            document.getElementById('step2Next').disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading staff:', error);
                    });
            }
            
            // Function to select a staff member
            window.selectStaff = function(staffId) {
                selectedStaffId = staffId;
                document.getElementById('selectedStaff').value = staffId;
                
                // Update UI to show selected staff
                document.querySelectorAll('.staff-card').forEach(card => {
                    card.classList.remove('selected');
                    if (card.onclick.toString().includes(`selectStaff(${staffId})`)) {
                        card.classList.add('selected');
                        
                        // Store staff data
                        const staffName = card.querySelector('h5').innerText;
                        const staffSpecialization = card.querySelector('.text-muted').innerText;
                        
                        staffData = {
                            id: staffId,
                            name: staffName,
                            specialization: staffSpecialization
                        };
                    }
                });
                
                // Enable next button
                document.getElementById('step2Next').disabled = false;
            };
            
            // Function to check available time slots
            function checkAvailableTimeSlots() {
                const date = document.getElementById('booking_date').value;
                if (!date || !selectedStaffId) return;
                
                fetch(`get_available_times.php?staff_id=${selectedStaffId}&date=${date}&service_id=${selectedServiceId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.querySelectorAll('.time-slot').forEach(slot => {
                            const time = slot.getAttribute('data-time');
                            slot.classList.remove('active', 'unavailable');
                            
                            if (data.unavailable_times.includes(time)) {
                                slot.classList.add('unavailable');
                            } else if (time === selectedTime) {
                                slot.classList.add('active');
                            }
                            
                            // Add click event if not unavailable
                            if (!data.unavailable_times.includes(time)) {
                                slot.onclick = function() {
                                    selectTimeSlot(time);
                                };
                            } else {
                                slot.onclick = null;
                            }
                        });
                    })
                    .catch(error => {
                        console.error('Error checking available times:', error);
                    });
            }
            
            // Function to select a time slot
            function selectTimeSlot(time) {
                selectedTime = time;
                document.getElementById('selectedTime').value = time;
                
                // Update UI
                document.querySelectorAll('.time-slot').forEach(slot => {
                    slot.classList.remove('active');
                    if (slot.getAttribute('data-time') === time) {
                        slot.classList.add('active');
                    }
                });
                
                // Enable next button
                document.getElementById('step4Next').disabled = false;
            }
            
            // Initialize the date picker
            flatpickr('#booking_date', {
                minDate: 'today',
                dateFormat: 'Y-m-d',
                disable: [
                    function(date) {
                        // Disable Sundays (day 0)
                        return date.getDay() === 0;
                    }
                ],
                onChange: function(selectedDates, dateStr) {
                    selectedDate = dateStr;
                    // Reset time selection when date changes
                    selectedTime = '';
                    document.getElementById('selectedTime').value = '';
                    document.getElementById('step4Next').disabled = true;
                    document.querySelectorAll('.time-slot').forEach(slot => {
                        slot.classList.remove('active');
                    });
                    
                    // Check available time slots for the new date
                    if (selectedStaffId) {
                        checkAvailableTimeSlots();
                    }
                }
            });
            
            // Update booking summary
            function updateBookingSummary() {
                document.getElementById('summaryService').innerText = serviceData.name || '';
                document.getElementById('summaryStaff').innerText = staffData.name || '';
                document.getElementById('summaryDate').innerText = formatDate(selectedDate) || '';
                document.getElementById('summaryTime').innerText = formatTime(selectedTime) || '';
            }
            
            // Format date for display
            function formatDate(dateStr) {
                if (!dateStr) return '';
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                return new Date(dateStr).toLocaleDateString('en-US', options);
            }
            
            // Format time for display
            function formatTime(timeStr) {
                if (!timeStr) return '';
                const [hours, minutes] = timeStr.split(':');
                const period = hours >= 12 ? 'PM' : 'AM';
                const formattedHours = hours % 12 || 12;
                return `${formattedHours}:${minutes} ${period}`;
            }
            
            // Step navigation - Next buttons
            document.getElementById('step1Next').addEventListener('click', function() {
                if (selectedServiceId > 0) {
                    showStep(2);
                }
            });
            
            document.getElementById('step2Next').addEventListener('click', function() {
                if (selectedStaffId > 0) {
                    showStep(3);
                }
            });
            
            document.getElementById('step3Next').addEventListener('click', function() {
                if (selectedDate) {
                    showStep(4);
                    checkAvailableTimeSlots();
                }
            });
            
            document.getElementById('step4Next').addEventListener('click', function() {
                if (selectedTime) {
                    showStep(5);
                    updateBookingSummary();
                }
            });
            
            // Step navigation - Back buttons
            document.getElementById('step2Back').addEventListener('click', function() {
                showStep(1);
            });
            
            document.getElementById('step3Back').addEventListener('click', function() {
                showStep(2);
            });
            
            document.getElementById('step4Back').addEventListener('click', function() {
                showStep(3);
            });
            
            document.getElementById('step5Back').addEventListener('click', function() {
                showStep(4);
            });
            
            // Form validation
            document.getElementById('bookingForm').addEventListener('submit', function(event) {
                let isValid = true;
                let errorMessage = '';
                
                // Validate all required fields
                if (!selectedServiceId) {
                    isValid = false;
                    errorMessage += 'Please select a service.<br>';
                }
                
                if (!selectedStaffId) {
                    isValid = false;
                    errorMessage += 'Please select a staff member.<br>';
                }
                
                if (!selectedDate) {
                    isValid = false;
                    errorMessage += 'Please select a date.<br>';
                } else {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    const bookingDate = new Date(selectedDate);
                    if (bookingDate < today) {
                        isValid = false;
                        errorMessage += 'Booking date must be in the future.<br>';
                    }
                }
                
                if (!selectedTime) {
                    isValid = false;
                    errorMessage += 'Please select a time slot.<br>';
                }
                
                if (!isValid) {
                    event.preventDefault();
                    
                    // Create alert for error message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <strong>Please correct the following errors:</strong><br>
                        ${errorMessage}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    
                    // Insert alert before form
                    const form = document.getElementById('bookingForm');
                    form.parentNode.insertBefore(alertDiv, form);
                    
                    // Scroll to top of form
                    form.scrollIntoView({ behavior: 'smooth' });
                }
            });
            
            // Handle cancel booking
            const cancelButtons = document.querySelectorAll('.cancel-booking');
            cancelButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const bookingId = this.getAttribute('data-booking-id');
                    document.getElementById('cancel_booking_id').value = bookingId;
                    const cancelModal = new bootstrap.Modal(document.getElementById('cancelBookingModal'));
                    cancelModal.show();
                });
            });
            
            // Initialize with current status
            if (selectedServiceId > 0) {
                // If service is already selected (e.g., from URL parameter)
                document.getElementById('step1Next').disabled = false;
                loadStaffForService(selectedServiceId);
                
                if (selectedStaffId > 0 && selectedDate) {
                    checkAvailableTimeSlots();
                }
            }
            
            // Show correct initial step based on URL parameters
            if (selectedServiceId && selectedStaffId && selectedDate) {
                showStep(4); // Go directly to time selection
            } else if (selectedServiceId && selectedStaffId) {
                showStep(3); // Go to date selection
            } else if (selectedServiceId) {
                showStep(2); // Go to staff selection
            } else {
                showStep(1); // Start at service selection
            }
        });
    </script>
</body>
</html>