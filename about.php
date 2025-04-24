<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in using the function from db_connect.php
$isLoggedIn = is_logged_in();
$userName = $isLoggedIn ? $_SESSION['first_name'] : '';

// Fetch staff members
$staffQuery = "SELECT s.*, u.first_name, u.last_name, u.profile_image 
              FROM staff s 
              JOIN users u ON s.user_id = u.user_id 
              WHERE s.is_active = 1 
              ORDER BY u.first_name";
$staffResult = cached_query($conn, $staffQuery);

// Fetch testimonials/reviews for about page (limit to 2)
$reviewsQuery = "SELECT r.*, u.first_name, u.last_name, s.name as service_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.user_id 
                LEFT JOIN services s ON r.service_id = s.service_id 
                WHERE r.is_approved = 1 
                ORDER BY r.rating DESC, r.created_at DESC LIMIT 2";
$reviewsResult = cached_query($conn, $reviewsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Luminas Hair & Beauty Salon</title>
    
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
                        <a class="nav-link active" href="about.php">About</a>
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
            <div class="row">
                <div class="col-12">
                    <h1>About Us</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">About</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="our-story py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="story-image">
                        <img src="images/bg2.webp" alt="Luminas Salon Exterior" class="img-fluid rounded">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="story-content">
                        <h2>Our Story</h2>
                        <p class="lead">Bringing beauty and confidence to our community since 2010.</p>
                        <p>Luminas Hair & Beauty was founded with a simple yet powerful vision: to create a sanctuary where clients could experience exceptional beauty services in a luxurious yet welcoming environment.</p>
                        <p>What began as a small salon with just three styling chairs has grown into a comprehensive beauty destination offering a wide range of hair, facial, nail, makeup, and massage services. Through the years, our commitment to quality and customer satisfaction has remained unwavering.</p>
                        <p>Today, Luminas stands as a testament to our dedication to the art and science of beauty, continually evolving to incorporate the latest techniques and products while maintaining the personalized service that has been our hallmark since day one.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="our-values py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>Our Values</h2>
                <p class="text-muted">The principles that guide everything we do</p>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="value-card text-center">
                        <div class="value-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <h3>Excellence</h3>
                        <p>We strive for excellence in every service we provide, from the products we use to the techniques we employ.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="value-card text-center">
                        <div class="value-icon">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <h3>Client Care</h3>
                        <p>Our clients are at the heart of everything we do. Your satisfaction and comfort are our top priorities.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="value-card text-center">
                        <div class="value-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3>Sustainability</h3>
                        <p>We're committed to using eco-friendly products and implementing sustainable practices throughout our salon.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="value-card text-center">
                        <div class="value-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>Continuous Learning</h3>
                        <p>Our team regularly trains in the latest techniques and trends to bring you the most current beauty innovations.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="value-card text-center">
                        <div class="value-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Community</h3>
                        <p>We believe in building strong relationships with our clients and giving back to the community that supports us.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="value-card text-center">
                        <div class="value-icon">
                            <i class="fas fa-puzzle-piece"></i>
                        </div>
                        <h3>Individuality</h3>
                        <p>We celebrate your unique beauty and create personalized treatments that enhance your natural features.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Meet Our Team -->
    <section class="our-team py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>Meet Our Team</h2>
                <p class="text-muted">The talented professionals ready to transform your look</p>
            </div>
            <div class="row">
                <?php 
                if ($staffResult && $staffResult->num_rows > 0):
                    while ($staff = $staffResult->fetch_assoc()): 
                        $staffImage = !empty($staff['profile_image']) ? $staff['profile_image'] : 'default.jpg';
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="team-card">
                        <div class="team-image">
                            <img src="images/staff/<?php echo htmlspecialchars($staffImage); ?>" alt="<?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>" class="img-fluid">
                        </div>
                        <div class="team-content">
                            <h3><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h3>
                            <p class="team-specialization"><?php echo htmlspecialchars($staff['specialization']); ?></p>
                            <p class="team-bio"><?php echo htmlspecialchars(substr($staff['bio'], 0, 150) . '...'); ?></p>
                            <div class="team-social">
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    endwhile;
                else:
                    // Display default staff if none in database
                    $defaultStaff = [
                        [
                            'name' => 'Kaveesha Geeganarachchi',
                            'specialization' => 'Hair Stylist & Colorist',
                            'bio' => 'With over 10 years of experience, Emma specializes in transformative haircuts and vibrant coloring techniques that bring out each client\'s unique beauty.'
                        ],
                        [
                            'name' => 'Iuri Dinushani',
                            'specialization' => 'Senior Aesthetician',
                            'bio' => 'Michael\'s expertise in skincare and facial treatments has made him a favorite among clients seeking rejuvenation and relaxation.'
                        ],
                        [
                            'name' => 'Sawbhagya Keller',
                            'specialization' => 'Nail Artist & Technician',
                            'bio' => 'Sophia combines creativity with precision to deliver stunning nail designs and immaculate manicures that leave lasting impressions.'
                        ]
                    ];
                    
                    foreach ($defaultStaff as $member):
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="team-card">
                        <div class="team-image">
                            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTUd-5GAQtSXMxDZxQD5jyqaLoEGly64-ESHg&s" alt="<?php echo $member['name']; ?>" class="img-fluid">
                        </div>
                        <div class="team-content">
                            <h3><?php echo $member['name']; ?></h3>
                            <p class="team-specialization"><?php echo $member['specialization']; ?></p>
                            <p class="team-bio"><?php echo $member['bio']; ?></p>
                            <div class="team-social">
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
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

    <!-- Our Salon -->
    <section class="our-salon py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>Our Salon</h2>
                <p class="text-muted">Step into a world of beauty and tranquility</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="salon-feature">
                        <img src="images/salon-interior-1.jpg" alt="Salon Interior" class="img-fluid rounded">
                        <div class="salon-feature-content">
                            <h3>Luxurious Environment</h3>
                            <p>Our salon is designed to provide the perfect blend of luxury and comfort, creating an atmosphere where you can truly relax and enjoy your beauty experience.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="salon-feature">
                        <img src="images/salon-interior-2.jpg" alt="Salon Equipment" class="img-fluid rounded">
                        <div class="salon-feature-content">
                            <h3>State-of-the-Art Equipment</h3>
                            <p>We invest in the latest technology and equipment to ensure that you receive the highest quality services with optimal results.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="salon-feature">
                        <img src="images/salon-products.avif" alt="Premium Products" class="img-fluid rounded">
                        <div class="salon-feature-content">
                            <h3>Premium Products</h3>
                            <p>We use only the finest beauty products, carefully selected for their quality, effectiveness, and commitment to ethical and sustainable practices.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="salon-feature">
                        <img src="images/salon-lounge.jpg" alt="Client Lounge" class="img-fluid rounded">
                        <div class="salon-feature-content">
                            <h3>Client Lounge</h3>
                            <p>Our comfortable waiting area features complimentary refreshments and a relaxing atmosphere to enhance your visit from the moment you arrive.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Client Testimonials -->
    <section class="testimonials-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>What Our Clients Say</h2>
                <p class="text-muted">The experiences that keep our clients coming back</p>
            </div>
            <div class="row justify-content-center">
                <?php 
                if ($reviewsResult && $reviewsResult->num_rows > 0):
                    while ($review = $reviewsResult->fetch_assoc()): 
                ?>
                <div class="col-lg-6 col-md-8 mb-4">
                    <div class="testimonial-card-large">
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
                            'comment' => 'I\'ve been coming to Luminas for over a year now, and I\'ve never had anything less than an exceptional experience. The staff is professional, friendly, and incredibly talented. My hair has never looked better!',
                            'rating' => 5,
                            'service' => 'Hair Styling & Color'
                        ],
                        [
                            'name' => 'Dilmi Kodithuwakku',
                            'comment' => 'As someone who was initially hesitant about spa treatments, I can\'t believe what I was missing. The massage therapists at Luminas are miracle workers! Truly a transformative experience that I now look forward to monthly.',
                            'rating' => 5,
                            'service' => 'Deep Tissue Massage'
                        ]
                    ];
                    
                    foreach ($defaultTestimonials as $testimonial):
                ?>
                <div class="col-lg-6 col-md-8 mb-4">
                    <div class="testimonial-card-large">
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
            <div class="text-center mt-4">
                <a href="services.php" class="btn btn-primary">Experience Our Services</a>
            </div>
        </div>
    </section>

    <!-- FAQs -->
    <section class="faq-section py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2>Frequently Asked Questions</h2>
                <p class="text-muted">Answers to common questions about our salon and services</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Do I need to book an appointment in advance?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>While we do accept walk-ins when possible, we strongly recommend booking appointments in advance to ensure availability with your preferred stylist or technician. You can book online through our website, by phone, or in person at the salon.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    What happens if I need to cancel my appointment?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>We understand that plans can change. We appreciate at least 24 hours' notice for cancellations, which allows us to offer the appointment to other clients. Cancellations with less than 24 hours' notice may incur a cancellation fee.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Do you offer consultations before services?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Yes! We offer complimentary consultations for most of our services. This gives you the opportunity to discuss your goals with your stylist or technician and allows them to create a personalized plan for your service.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    What products do you use and sell?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>We use and sell professional-grade products from leading brands known for their quality and results. Our selection includes options for all hair and skin types, with a focus on products that are cruelty-free and environmentally conscious.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    Do you offer gift cards?
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Yes, we offer both physical and digital gift cards in any denomination. They make perfect gifts for birthdays, holidays, or any special occasion. Gift cards can be purchased in-salon or online through our website.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <h2>Ready to Experience the Luminas Difference?</h2>
                    <p class="lead mb-0">Book your appointment today and let our team of experts take care of you.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="bookings.php" class="btn btn-primary btn-lg">Book Now</a>
                    <a href="contact.php" class="btn btn-outline-primary btn-lg ms-2">Contact Us</a>
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