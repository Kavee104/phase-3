<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in
$isLoggedIn = is_logged_in();

// Redirect to login if not logged in
if (!$isLoggedIn) {
    $_SESSION['redirect_after_login'] = 'review.php';
    header('Location: auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'];
$userLastName = $_SESSION['last_name'];

// Check if booking ID is provided
if (!isset($_GET['booking']) || empty($_GET['booking'])) {
    $_SESSION['error_message'] = "No booking specified for review";
    header('Location: bookings.php');
    exit;
}

$bookingId = (int)$_GET['booking'];

// Get booking details to make sure it belongs to the current user and is completed
$bookingQuery = "SELECT b.*, s.name as service_name, 
                CONCAT(u.first_name, ' ', u.last_name) as staff_name
                FROM bookings b 
                JOIN services s ON b.service_id = s.service_id 
                LEFT JOIN staff st ON b.staff_id = st.staff_id 
                LEFT JOIN users u ON st.user_id = u.user_id 
                WHERE b.booking_id = ? AND b.user_id = ? AND b.status = 'completed'";

$stmt = $conn->prepare($bookingQuery);
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$bookingResult = $stmt->get_result();

if ($bookingResult->num_rows === 0) {
    $_SESSION['error_message'] = "Invalid booking or booking not eligible for review";
    header('Location: bookings.php');
    exit;
}

$booking = $bookingResult->fetch_assoc();

// Check if review already exists
$reviewExistsQuery = "SELECT review_id FROM reviews WHERE booking_id = ?";
$stmt = $conn->prepare($reviewExistsQuery);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$reviewResult = $stmt->get_result();
$reviewExists = ($reviewResult->num_rows > 0);

$reviewSuccess = false;
$reviewError = '';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    // Validate form data
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    // Basic validation
    if ($rating < 1 || $rating > 5) {
        $reviewError = "Please select a rating between 1 and 5";
    } elseif (empty($comment)) {
        $reviewError = "Please provide a comment for your review";
    } else {
        // If review already exists, update it
        if ($reviewExists) {
            $updateQuery = "UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE booking_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("isi", $rating, $comment, $bookingId);
            
            if ($stmt->execute()) {
                $reviewSuccess = true;
            } else {
                $reviewError = "Error updating review: " . $conn->error;
            }
        } else {
            // Insert new review
            $insertQuery = "INSERT INTO reviews (booking_id, user_id, service_id, staff_id, rating, comment, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("iiiiss", $bookingId, $userId, $booking['service_id'], $booking['staff_id'], $rating, $comment);
            
            if ($stmt->execute()) {
                $reviewSuccess = true;
            } else {
                $reviewError = "Error creating review: " . $conn->error;
            }
        }
    }
}

// If review exists, fetch it
$reviewData = null;
if ($reviewExists) {
    $getReviewQuery = "SELECT * FROM reviews WHERE booking_id = ?";
    $stmt = $conn->prepare($getReviewQuery);
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $reviewData = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review | Luminas Hair & Beauty Salon</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
    
    <style>
        .rating {
            direction: rtl;
            unicode-bidi: bidi-override;
            text-align: center;
        }
        .rating input {
            display: none;
        }
        .rating label {
            display: inline-block;
            padding: 0 5px;
            font-size: 30px;
            color: #ccc;
            cursor: pointer;
        }
        .rating label:hover,
        .rating label:hover ~ label,
        .rating input:checked ~ label {
            color: #FFD700;
        }
        .rating-display .fas {
            color: #FFD700;
        }
        .rating-display .far {
            color: #ccc;
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
                                <li><a class="dropdown-item" href="bookings.php">My Bookings</a></li>
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Leave a Review</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="bookings.php">Bookings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Review</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Review Section -->
    <section class="review-section py-5">
        <div class="container">
            <?php if ($reviewSuccess): ?>
            <div class="alert alert-success" role="alert">
                <h4 class="alert-heading">Thank You!</h4>
                <p>Your review has been submitted successfully. We appreciate your feedback!</p>
                <hr>
                <p class="mb-0">You can view your bookings or make a new appointment <a href="bookings.php">here</a>.</p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($reviewError)): ?>
            <div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">Error</h4>
                <p><?php echo $reviewError; ?></p>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h3 class="card-title mb-0">Your Experience at Luminas</h3>
                        </div>
                        <div class="card-body">
                            <div class="booking-details mb-4">
                                <h4>Booking Details</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Service:</strong> <?php echo htmlspecialchars($booking['service_name']); ?></p>
                                        <p><strong>Staff:</strong> <?php echo htmlspecialchars($booking['staff_name']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></p>
                                        <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($booking['start_time'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?booking=' . $bookingId); ?>">
                                <div class="mb-4">
                                    <h4>Rate Your Experience</h4>
                                    <div class="rating mb-3">
                                        <input type="radio" id="star5" name="rating" value="5" <?php echo ($reviewData && $reviewData['rating'] == 5) ? 'checked' : ''; ?>>
                                        <label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star4" name="rating" value="4" <?php echo ($reviewData && $reviewData['rating'] == 4) ? 'checked' : ''; ?>>
                                        <label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star3" name="rating" value="3" <?php echo ($reviewData && $reviewData['rating'] == 3) ? 'checked' : ''; ?>>
                                        <label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star2" name="rating" value="2" <?php echo ($reviewData && $reviewData['rating'] == 2) ? 'checked' : ''; ?>>
                                        <label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star1" name="rating" value="1" <?php echo ($reviewData && $reviewData['rating'] == 1) ? 'checked' : ''; ?>>
                                        <label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                                    </div>
                                    <small class="text-muted">Click on a star to rate your experience</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="comment" class="form-label">Your Review</label>
                                    <textarea class="form-control" id="comment" name="comment" rows="5" placeholder="Share your experience with us..."><?php echo ($reviewData) ? htmlspecialchars($reviewData['comment']) : ''; ?></textarea>
                                </div>
                                
                                <div class="text-end">
                                    <a href="bookings.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                    <button type="submit" name="submit_review" class="btn btn-primary">
                                        <?php echo ($reviewData) ? 'Update Review' : 'Submit Review'; ?>
                                    </button>
                                </div>
                            </form>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle navbar scrolling behavior
            const navbar = document.querySelector('.navbar');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.classList.add('navbar-scrolled');
                } else {
                    navbar.classList.remove('navbar-scrolled');
                }
            });
        });
    </script>
</body>
</html>