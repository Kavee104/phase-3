<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in using the function from db_connect.php
$isLoggedIn = is_logged_in();
$userName = $isLoggedIn ? $_SESSION['first_name'] : '';

// Get category filter from URL if exists
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Query to get all service categories
$categoryQuery = "SELECT * FROM service_categories ORDER BY name";
$categoryResult = cached_query($conn, $categoryQuery);

// Prepare services query based on filter
$servicesQuery = "SELECT s.*, c.name as category_name 
                FROM services s 
                JOIN service_categories c ON s.category_id = c.category_id 
                WHERE s.is_active = 1";

// Add category filter if specified
if ($categoryFilter > 0) {
    $servicesQuery .= " AND s.category_id = $categoryFilter";
}

$servicesQuery .= " ORDER BY c.name, s.name";
$servicesResult = cached_query($conn, $servicesQuery);

// Get selected category name for display
$selectedCategoryName = "All Services";
if ($categoryFilter > 0 && $categoryResult && $categoryResult->num_rows > 0) {
    // Reset result pointer and loop through categories
    $categoryResult->data_seek(0);
    while ($category = $categoryResult->fetch_assoc()) {
        if ($category['category_id'] == $categoryFilter) {
            $selectedCategoryName = $category['name'];
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - Luminas Hair & Beauty Salon</title>
    
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
                        <a class="nav-link active" href="services.php">Services</a>
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
            <div class="row">
                <div class="col-12">
                    <h1>Our Services</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Services</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Service Categories Filters -->
    <section class="service-filters py-4 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-md-0"><?php echo htmlspecialchars($selectedCategoryName); ?></h2>
                </div>
                <div class="col-md-6">
                    <div class="category-filter d-flex justify-content-md-end">
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                Filter by Category
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="categoryDropdown">
                                <li><a class="dropdown-item <?php echo ($categoryFilter == 0) ? 'active' : ''; ?>" href="services.php">All Services</a></li>
                                <?php
                                if ($categoryResult && $categoryResult->num_rows > 0) {
                                    // Reset result pointer
                                    $categoryResult->data_seek(0);
                                    while ($category = $categoryResult->fetch_assoc()) {
                                        $activeClass = ($categoryFilter == $category['category_id']) ? 'active' : '';
                                        echo '<li><a class="dropdown-item '.$activeClass.'" href="services.php?category='.$category['category_id'].'">'.htmlspecialchars($category['name']).'</a></li>';
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Listing -->
    <section class="services-listing py-5">
        <div class="container">
            <?php
            // Check if we have services to display
            if ($servicesResult && $servicesResult->num_rows > 0) {
                // Group services by category
                $services = [];
                $categories = [];
                
                while ($service = $servicesResult->fetch_assoc()) {
                    $categoryId = $service['category_id'];
                    $categoryName = $service['category_name'];
                    
                    if (!isset($services[$categoryId])) {
                        $services[$categoryId] = [];
                        $categories[$categoryId] = $categoryName;
                    }
                    
                    $services[$categoryId][] = $service;
                }
                
                // Loop through categories and display services
                foreach ($categories as $catId => $catName) {
                    ?>
                    <div class="service-category-section mb-5">
                        <h3 class="category-title mb-4"><?php echo htmlspecialchars($catName); ?></h3>
                        <div class="row">
                            <?php foreach ($services[$catId] as $service) { 
                                $serviceImage = !empty($service['image']) ? $service['image'] : 'default-service.jpg';
                            ?>
                            <div class="col-lg-6 mb-4">
                                <div class="service-item">
                                    <div class="row g-0">
                                        <div class="col-md-4">
                                            <div class="service-image h-100">
                                                <img src="images/services/<?php echo htmlspecialchars($serviceImage); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="img-fluid h-100 w-100 object-fit-cover">
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="service-content p-4">
                                                <h4><?php echo htmlspecialchars($service['name']); ?></h4>
                                                <p><?php echo htmlspecialchars($service['description']); ?></p>
                                                <div class="service-details mb-3">
                                                    <span class="service-price">$<?php echo number_format($service['price'], 2); ?></span>
                                                    <span class="service-duration ms-3"><i class="far fa-clock"></i> <?php echo $service['duration']; ?> min</span>
                                                </div>
                                                <a href="bookings.php?service=<?php echo $service['service_id']; ?>" class="btn btn-primary">Book Now</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                // No services found
                echo '<div class="alert alert-info">No services available in this category. Please check back later.</div>';
            }
            ?>
        </div>
    </section>

    <!-- Service Process -->
    <section class="service-process py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>Our Service Process</h2>
                <p class="text-muted">What to expect when you book with us</p>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="process-step text-center">
                        <div class="process-icon">
                            <i class="fas fa-calendar-check"></i>
                            <span class="process-number">1</span>
                        </div>
                        <h4>Book Your Appointment</h4>
                        <p>Choose your service and select a convenient time with your preferred stylist.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <div class="process-step text-center">
                        <div class="process-icon">
                            <i class="fas fa-comments"></i>
                            <span class="process-number">2</span>
                        </div>
                        <h4>Consultation</h4>
                        <p>Discuss your specific needs and goals with our expert professionals.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <div class="process-step text-center">
                        <div class="process-icon">
                            <i class="fas fa-spa"></i>
                            <span class="process-number">3</span>
                        </div>
                        <h4>Enjoy Your Service</h4>
                        <p>Relax and enjoy your personalized treatment in our luxurious space.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="process-step text-center">
                        <div class="process-icon">
                            <i class="fas fa-heart"></i>
                            <span class="process-number">4</span>
                        </div>
                        <h4>Aftercare & Follow-up</h4>
                        <p>Receive professional advice for maintaining your results at home.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Special Promotions -->
    <section class="promotions py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>Special Promotions</h2>
                <p class="text-muted">Limited time offers to enhance your beauty experience</p>
            </div>
            
            <div class="row">
                <!-- Fetching promotions would normally be done here -->
                <div class="col-lg-6 mb-4">
                    <div class="promotion-card">
                        <div class="promotion-content">
                            <span class="promotion-badge">Limited Time</span>
                            <h3>New Client Special</h3>
                            <p>First-time clients receive 15% off any service. Experience the Luminas difference today!</p>
                            <div class="promotion-code">Use code: <span>WELCOME15</span></div>
                            <a href="bookings.php" class="btn btn-primary mt-3">Book Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="promotion-card">
                        <div class="promotion-content">
                            <span class="promotion-badge">Package Deal</span>
                            <h3>Complete Renewal Package</h3>
                            <p>Enjoy a haircut, facial, and manicure at a special bundle price. Perfect for a complete refresh!</p>
                            <div class="promotion-price"><span class="old-price">$155</span> <span class="new-price">$125</span></div>
                            <a href="bookings.php" class="btn btn-primary mt-3">Book Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Frequently Asked Questions -->
    <section class="services-faq py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>Frequently Asked Questions</h2>
                <p class="text-muted">Common questions about our services</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="accordion" id="servicesAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    How far in advance should I book my appointment?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#servicesAccordion">
                                <div class="accordion-body">
                                    <p>We recommend booking at least 1-2 weeks in advance for regular services and 3-4 weeks for specialized services or popular time slots. For bridal or special event services, we suggest booking 2-3 months ahead.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    What should I do to prepare for my service?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#servicesAccordion">
                                <div class="accordion-body">
                                    <p>For hair services, we generally recommend coming with clean, dry hair unless instructed otherwise. For facials, arrive with a clean face free of makeup. For massage services, we suggest arriving a few minutes early to fill out any necessary forms and begin relaxing before your treatment.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Are consultations free?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#servicesAccordion">
                                <div class="accordion-body">
                                    <p>Yes, we offer complimentary consultations for most services. This helps us understand your needs and expectations before beginning the service. For complex color treatments or specialized services, a formal consultation appointment may be recommended.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    What if I'm not satisfied with my service?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#servicesAccordion">
                                <div class="accordion-body">
                                    <p>Your satisfaction is our priority. If you're not completely happy with your service, please let us know within 7 days, and we'll make it right. We offer complimentary touch-ups and adjustments to ensure you love your results.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    Can I request a specific staff member?
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#servicesAccordion">
                                <div class="accordion-body">
                                    <p>Absolutely! You can request your preferred stylist or technician when booking your appointment. We encourage building relationships with our staff members for consistent service and results. If your preferred staff member is unavailable, we'll be happy to recommend another skilled professional.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Book Now CTA -->
    <section class="book-cta py-5">
        <div class="container">
            <div class="cta-content text-center">
                <h2>Transform Your Look Today</h2>
                <p class="lead">Experience exceptional beauty services tailored to your unique needs</p>
                <div class="mt-4">
                    <a href="bookings.php" class="btn btn-primary btn-lg">Book Your Appointment</a>
                    <a href="contact.php" class="btn btn-outline-primary btn-lg ms-3">Contact Us</a>
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