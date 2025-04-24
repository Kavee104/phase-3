<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!is_logged_in()) {
    // Redirect to login page if not logged in
    header('Location: auth/login.php');
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$userQuery = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$userData = $userResult->fetch_assoc();
$stmt->close();

// Check if form is submitted for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Sanitize and validate input
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Basic validation
    $errors = [];
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email already exists (but not for current user)
    if ($email !== $userData['email']) {
        $checkEmailQuery = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt = $conn->prepare($checkEmailQuery);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $emailCheckResult = $stmt->get_result();
        if ($emailCheckResult->num_rows > 0) {
            $errors[] = "Email is already in use";
        }
        $stmt->close();
    }
    
    // Process profile image upload if provided
    $profile_image = $userData['profile_image']; // Default to current image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = 'images/profiles/';
            $file_name = time() . '_' . $_FILES['profile_image']['name'];
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Delete old profile image if it's not the default
                if ($userData['profile_image'] !== 'default.jpg' && file_exists('images/profiles/' . $userData['profile_image'])) {
                    unlink('images/profiles/' . $userData['profile_image']);
                }
                $profile_image = $file_name;
            } else {
                $errors[] = "Failed to upload profile image";
            }
        } else {
            $errors[] = "Invalid file type. Only JPEG, PNG, and GIF are allowed";
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        $updateQuery = "UPDATE users SET 
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        phone = ?, 
                        profile_image = ? 
                        WHERE user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $profile_image, $user_id);
        
        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $userQuery = "SELECT * FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($userQuery);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $userResult = $stmt->get_result();
            $userData = $userResult->fetch_assoc();
        } else {
            $errors[] = "Failed to update profile: " . $conn->error;
        }
        $stmt->close();
    }
}

// Check if form is submitted for password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $password_errors = [];
    
    // Verify current password
    if (!password_verify($current_password, $userData['password'])) {
        $password_errors[] = "Current password is incorrect";
    }
    
    // Validate new password
    if (strlen($new_password) < 8) {
        $password_errors[] = "New password must be at least 8 characters long";
    }
    
    // Check if passwords match
    if ($new_password !== $confirm_password) {
        $password_errors[] = "New passwords do not match";
    }
    
    // Update password if no errors
    if (empty($password_errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $updatePasswordQuery = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updatePasswordQuery);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $password_success = "Password changed successfully!";
        } else {
            $password_errors[] = "Failed to update password: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch user's bookings
$bookingsQuery = "SELECT b.*, s.name as service_name, 
                 CONCAT(u.first_name, ' ', u.last_name) as staff_name 
                 FROM bookings b
                 JOIN services s ON b.service_id = s.service_id
                 LEFT JOIN staff st ON b.staff_id = st.staff_id
                 LEFT JOIN users u ON st.user_id = u.user_id
                 WHERE b.user_id = ?
                 ORDER BY b.booking_date DESC, b.start_time DESC
                 LIMIT 5";
$stmt = $conn->prepare($bookingsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookingsResult = $stmt->get_result();
$stmt->close();

// Fetch user's reviews
$reviewsQuery = "SELECT r.*, s.name as service_name 
                FROM reviews r
                LEFT JOIN services s ON r.service_id = s.service_id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC";
$stmt = $conn->prepare($reviewsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviewsResult = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Luminas Hair & Beauty Salon</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="bookings.php">My Bookings</a></li>
                            <li><a class="dropdown-item active" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <header class="page-header">
        <div class="container">
            <h1>My Profile</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Profile</li>
                </ol>
            </nav>
        </div>
    </header>

    <!-- Profile Section -->
    <section class="profile-section py-5">
        <div class="container">
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Left Sidebar -->
                <div class="col-lg-3 mb-4">
                    <div class="profile-sidebar bg-white p-4 rounded shadow-sm">
                        <div class="profile-image text-center mb-4">
                            <?php 
                            $profileImg = !empty($userData['profile_image']) ? $userData['profile_image'] : 'default.jpg';
                            ?>
                            <img src="images/profiles/<?php echo htmlspecialchars($profileImg); ?>" alt="Profile Picture" class="img-fluid rounded-circle profile-pic">
                        </div>
                        <div class="profile-info text-center">
                            <h4><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h4>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($userData['email']); ?></p>
                            <p><i class="fas fa-phone me-2"></i><?php echo !empty($userData['phone']) ? htmlspecialchars($userData['phone']) : 'Not specified'; ?></p>
                            <p><i class="fas fa-calendar-alt me-2"></i>Member since: <?php echo date('M Y', strtotime($userData['created_at'])); ?></p>
                        </div>
                        <div class="profile-nav mt-4">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#profile" data-bs-toggle="tab">
                                        <i class="fas fa-user me-2"></i>Personal Information
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#security" data-bs-toggle="tab">
                                        <i class="fas fa-lock me-2"></i>Security
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#bookings" data-bs-toggle="tab">
                                        <i class="fas fa-calendar-check me-2"></i>Recent Bookings
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#reviews" data-bs-toggle="tab">
                                        <i class="fas fa-star me-2"></i>My Reviews
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="bookings.php">
                                        <i class="fas fa-plus-circle me-2"></i>New Booking
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Right Content -->
                <div class="col-lg-9">
                    <div class="tab-content">
                        <!-- Personal Information Tab -->
                        <div class="tab-pane fade show active" id="profile">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Personal Information</h5>
                                </div>
                                <div class="card-body">
                                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="profile_image" class="form-label">Profile Picture</label>
                                            <input type="file" class="form-control" id="profile_image" name="profile_image">
                                            <div class="form-text">Upload a new profile picture (JPG, PNG, or GIF only)</div>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($password_errors) && !empty($password_errors)): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <ul class="mb-0">
                                                <?php foreach ($password_errors as $error): ?>
                                                    <li><?php echo htmlspecialchars($error); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($password_success)): ?>
                                        <div class="alert alert-success" role="alert">
                                            <?php echo htmlspecialchars($password_success); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form action="profile.php" method="POST">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <div class="form-text">Password must be at least 8 characters long</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Bookings Tab -->
                        <div class="tab-pane fade" id="bookings">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Bookings</h5>
                                    <a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if ($bookingsResult->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Service</th>
                                                        <th>Date</th>
                                                        <th>Time</th>
                                                        <th>Staff</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($booking = $bookingsResult->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                                            <td><?php echo date('h:i A', strtotime($booking['start_time'])); ?></td>
                                                            <td><?php echo !empty($booking['staff_name']) ? htmlspecialchars($booking['staff_name']) : 'Any Available'; ?></td>
                                                            <td>
                                                                <span class="badge <?php 
                                                                    $statusClass = 'bg-secondary';
                                                                    if ($booking['status'] === 'confirmed') {
                                                                        $statusClass = 'bg-success';
                                                                    } elseif ($booking['status'] === 'pending') {
                                                                        $statusClass = 'bg-warning';
                                                                    } elseif ($booking['status'] === 'cancelled') {
                                                                        $statusClass = 'bg-danger';
                                                                    } elseif ($booking['status'] === 'completed') {
                                                                        $statusClass = 'bg-info';
                                                                    }
                                                                    echo $statusClass;
                                                                ?>">
                                                                    <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                                                </span>
                                                            </td>
                                                                <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            You don't have any bookings yet. <a href="bookings.php" class="alert-link">Book now</a>!
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reviews Tab -->
                        <div class="tab-pane fade" id="reviews">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">My Reviews</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($reviewsResult->num_rows > 0): ?>
                                        <?php while ($review = $reviewsResult->fetch_assoc()): ?>
                                            <div class="review-item mb-4 pb-4 border-bottom">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h5 class="mb-0"><?php echo htmlspecialchars($review['service_name'] ?? 'Service'); ?></h5>
                                                    <span class="text-muted small"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                                </div>
                                                <div class="rating mb-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                
                                                <?php if (!$review['is_approved']): ?>
                                                    <div class="badge bg-warning text-dark">Pending approval</div>
                                                <?php endif; ?>
                                                
                                                <div class="mt-2">
                                                    <a href="edit-review.php?id=<?php echo $review['review_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <a href="delete-review.php?id=<?php echo $review['review_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this review?')">Delete</a>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            You haven't written any reviews yet. After your appointment, we'd love to hear your feedback!
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer pt-5 pb-3">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h4>LUMINAS</h4>
                    <p>Your premier destination for all beauty and hair services. We're dedicated to making you look and feel your best.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-pinterest-p"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <h4>Contact Us</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Galle Road, Colombo 3, Sri Lanka</li>
                        <li><i class="fas fa-phone"></i> +94 11 234 5678</li>
                        <li><i class="fas fa-envelope"></i> info@luminasbeauty.lk</li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <h4>Opening Hours</h4>
                    <ul class="opening-hours">
                        <li>Monday - Thursday: <span>9:00 AM - 7:00 PM</span></li>
                        <li>Friday: <span>9:00 AM - 8:00 PM</span></li>
                        <li>Saturday: <span>10:00 AM - 5:00 PM</span></li>
                        <li>Sunday: <span>Closed</span></li>
                    </ul>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="copyright text-center">
                        <p>&copy; <?php echo date('Y'); ?> Luminas Hair & Beauty. All Rights Reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="js/main.js"></script>
</body>
</html>