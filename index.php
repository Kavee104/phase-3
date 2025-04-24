<?php
// Include database connection
require_once 'includes/db_connect.php';

// No need to start session again - it's already started in db_connect.php
// session_start(); - remove this line

// Check if user is logged in using the function from db_connect.php
$isLoggedIn = is_logged_in();
$userName = $isLoggedIn ? $_SESSION['first_name'] : '';

// Using the optimized cached_query function for better performance
// Fetch service categories
$categoryQuery = "SELECT * FROM service_categories ORDER BY name";
$categoryResult = cached_query($conn, $categoryQuery);

// Fetch featured services (limit to 6)
$servicesQuery = "SELECT s.*, c.name as category_name 
                 FROM services s 
                 JOIN service_categories c ON s.category_id = c.category_id 
                 WHERE s.is_active = 1 
                 ORDER BY s.service_id DESC LIMIT 6";
$servicesResult = cached_query($conn, $servicesQuery);

// Fetch gallery images (limit to 8)
$galleryQuery = "SELECT * FROM gallery WHERE is_active = 1 ORDER BY display_order, image_id LIMIT 8";
$galleryResult = cached_query($conn, $galleryQuery);

// Fetch testimonials/reviews
$reviewsQuery = "SELECT r.*, u.first_name, u.last_name, s.name as service_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.user_id 
                LEFT JOIN services s ON r.service_id = s.service_id 
                WHERE r.is_approved = 1 
                ORDER BY r.created_at DESC LIMIT 3";
$reviewsResult = cached_query($conn, $reviewsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luminas Hair & Beauty Salon</title>
    
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
                        <a class="nav-link active" href="index.php">Home</a>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="display-4">Your Beauty Journey Starts Here</h1>
            <p class="lead">Experience luxury beauty treatments tailored just for you</p>
            <div class="mt-4">
                <a href="services.php" class="btn btn-primary btn-lg me-2">Our Services</a>
                <a href="bookings.php" class="btn btn-outline-light btn-lg">Book Now</a>
            </div>
        </div>
    </section>

    <!-- Service Categories -->
    <section class="service-categories py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>Our Services</h2>
                <p class="text-muted">Discover a wide range of premium beauty services</p>
            </div>
            <div class="row justify-content-center">
                <?php 
                if ($categoryResult && $categoryResult->num_rows > 0):
                    while ($category = $categoryResult->fetch_assoc()): 
                        $categoryImage = !empty($category['image']) ? $category['image'] : 'default-category.jpg';
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="category-card">
                        <div class="category-image">
                            <img src="images/categories/<?php echo htmlspecialchars($categoryImage); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                        </div>
                        <div class="category-content">
                            <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($category['description'], 0, 80) . '...'); ?></p>
                            <a href="services.php?category=<?php echo $category['category_id']; ?>" class="btn btn-outline-primary">View Services</a>
                        </div>
                    </div>
                </div>
                <?php 
                    endwhile;
                endif;
                ?>
            </div>
        </div>
    </section>

    <!-- Featured Services -->
    <section class="featured-services py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>Featured Services</h2>
                <p class="text-muted">Our most popular treatments and packages</p>
            </div>
            <div class="row">
                <?php 
                if ($servicesResult && $servicesResult->num_rows > 0):
                    while ($service = $servicesResult->fetch_assoc()): 
                        $serviceImage = !empty($service['image']) ? $service['image'] : 'default-service.jpg';
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="images/services/<?php echo htmlspecialchars($serviceImage); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>">
                            <div class="service-category"><?php echo htmlspecialchars($service['category_name']); ?></div>
                        </div>
                        <div class="service-content">
                            <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($service['description'], 0, 100) . '...'); ?></p>
                            <div class="service-details">
                                <span class="service-price">$<?php echo number_format($service['price'], 2); ?></span>
                                <span class="service-duration"><i class="far fa-clock"></i> <?php echo $service['duration']; ?> min</span>
                            </div>
                            <a href="bookings.php?service=<?php echo $service['service_id']; ?>" class="btn btn-primary mt-3">Book Now</a>
                        </div>
                    </div>
                </div>
                <?php 
                    endwhile;
                endif;
                ?>
            </div>
            <div class="text-center mt-4">
                <a href="services.php" class="btn btn-outline-primary">View All Services</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="about-image">
                        <img src="images/bg2.webp" alt="Luminas Salon Interior" class="img-fluid rounded">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-content">
                        <h2>Welcome to Luminas</h2>
                        <p class="lead">Where beauty meets luxury and self-care is transformed into an art.</p>
                        <p>At Luminas Hair & Beauty, we believe everyone deserves to feel beautiful and confident. Our team of skilled professionals is dedicated to providing exceptional service in a relaxing environment.</p>
                        <p>With state-of-the-art facilities and premium products, we ensure every visit leaves you feeling refreshed and rejuvenated.</p>
                        <a href="about.php" class="btn btn-outline-primary mt-3">Learn More About Us</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="gallery-section py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>Our Gallery</h2>
                <p class="text-muted">See the results of our expert services</p>
            </div>
            <div class="row g-3">
                <?php 
                if ($galleryResult && $galleryResult->num_rows > 0):
                    while ($image = $galleryResult->fetch_assoc()): 
                ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="gallery-item">
                        <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>" class="img-fluid">
                        <div class="gallery-overlay">
                            <div class="gallery-info">
                                <h5><?php echo htmlspecialchars($image['title']); ?></h5>
                                <p><?php echo htmlspecialchars($image['category']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    endwhile;
                else:
                ?>
                <!-- Default gallery images if none in database -->
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="gallery-item">
                        <img src="images/gallery/HAIR-STYLING-CLASS-FOR-BRIDAL-AND-RED-CARPET.jpg" alt="Hair Styling" class="img-fluid">
                        <div class="gallery-overlay">
                            <div class="gallery-info">
                                <h5>Elegant Styling</h5>
                                <p>Hair</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="gallery-item">
                        <img src="images/gallery/hb.png" alt="Facial Treatment" class="img-fluid">
                        <div class="gallery-overlay">
                            <div class="gallery-info">
                                <h5>Rejuvenating Facial</h5>
                                <p>Facial</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="gallery-item">
                        <img src="images/gallery/Aesthetic_Summer_Nail_Designs_4afeb3da-c54d-4230-abde-68ef46859c78.jpg" alt="Nail Art" class="img-fluid">
                        <div class="gallery-overlay">
                            <div class="gallery-info">
                                <h5>Creative Nail Art</h5>
                                <p>Nails</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="gallery-item">
                        <img src="images/gallery/eye-makeup-1.avif" alt="Makeup" class="img-fluid">
                        <div class="gallery-overlay">
                            <div class="gallery-info">
                                <h5>Glamour Makeup</h5>
                                <p>Makeup</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>Client Testimonials</h2>
                <p class="text-muted">What our clients say about us</p>
            </div>
            <div class="row">
                <?php 
                if ($reviewsResult && $reviewsResult->num_rows > 0):
                    while ($review = $reviewsResult->fetch_assoc()): 
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'filled' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="testimonial-text">
                            <p>"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                        </div>
                        <div class="testimonial-author">
                            <h5><?php echo htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.'); ?></h5>
                            <?php if (!empty($review['service_name'])): ?>
                                <p><small>Service: <?php echo htmlspecialchars($review['service_name']); ?></small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php 
                    endwhile;
                else:
                    // Display default testimonials if none in database
                    $defaultTestimonials = [
                        [
                            'name' => 'Vidumini Kodikara',
                            'comment' => 'My hair has never looked better! The stylist really understood what I wanted and exceeded my expectations.',
                            'rating' => 5,
                            'service' => 'Hair Coloring'
                        ],
                        [
                            'name' => 'Dilmi Kodithuwakku',
                            'comment' => 'The massage was incredibly relaxing and exactly what I needed. Will definitely be back soon!',
                            'rating' => 5,
                            'service' => 'Swedish Massage'
                        ],
                        [
                            'name' => 'Dinuri Gayara',
                            'comment' => 'The facial left my skin glowing. The aesthetician was knowledgeable and friendly.',
                            'rating' => 4,
                            'service' => 'Classic Facial'
                        ]
                    ];
                    
                    foreach ($defaultTestimonials as $testimonial):
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo ($i <= $testimonial['rating']) ? 'filled' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="testimonial-text">
                            <p>"<?php echo $testimonial['comment']; ?>"</p>
                        </div>
                        <div class="testimonial-author">
                            <h5><?php echo $testimonial['name']; ?></h5>
                            <p><small>Service: <?php echo $testimonial['service']; ?></small></p>
                        </div>
                    </div>
                </div>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>
        </div>
    </section>

    <!-- Booking CTA -->
    <section class="booking-cta py-5">
        <div class="container">
            <div class="cta-content text-center">
                <h2>Ready to Transform Your Look?</h2>
                <p class="lead">Book your appointment today and experience the Luminas difference</p>
                <a href="bookings.php" class="btn btn-primary btn-lg mt-3">Book Now</a>
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