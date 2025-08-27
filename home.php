<?php
session_start();

// Include database connection
require_once 'config/database.php';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Fetch school settings
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE is_public = 1");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Fetch featured gallery images
$stmt = $conn->prepare("SELECT gi.*, gc.name as category_name 
                       FROM gallery_images gi 
                       JOIN gallery_categories gc ON gi.category_id = gc.id 
                       WHERE gi.is_featured = 1 AND gi.status = 'approved' 
                       ORDER BY gi.created_at DESC LIMIT 6");
$stmt->execute();
$featuredImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent announcements
$stmt = $conn->prepare("SELECT * FROM communication_logs 
                       WHERE type = 'email' AND recipient_type = 'other' 
                       AND subject LIKE '%Announcement%' 
                       ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($settings['school_name']) ? htmlspecialchars($settings['school_name']) : 'School Management System'; ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --accent-color: #f6c23e;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            background-color: #f8f9fc;
        }
        
        .navbar {
            background-color: #fff;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .navbar-brand img {
            max-height: 50px;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-image: url('assets/images/pattern.png');
            opacity: 0.1;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-image img {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .feature-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .gallery-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover img {
            transform: scale(1.05);
        }
        
        .gallery-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .announcement-card {
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }
        
        .announcement-card:hover {
            transform: translateX(5px);
        }
        
        .cta-section {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #13855c 100%);
            color: white;
            padding: 80px 0;
            position: relative;
        }
        
        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-image: url('assets/images/pattern.png');
            opacity: 0.1;
        }
        
        .cta-content {
            position: relative;
            z-index: 1;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
        
        .btn-light {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        footer {
            background-color: #fff;
            box-shadow: 0 -0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .social-icons a {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .social-icons a:hover {
            background-color: var(--dark-color);
            transform: translateY(-3px);
        }
        
        .contact-info i {
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0;
            }
            
            .hero-image {
                margin-top: 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="home.php">
                <?php if(isset($settings['school_logo']) && !empty($settings['school_logo'])): ?>
                    <img src="<?php echo htmlspecialchars($settings['school_logo']); ?>" alt="School Logo">
                <?php else: ?>
                    <span class="h4 text-primary"><?php echo isset($settings['school_name']) ? htmlspecialchars($settings['school_name']) : 'School Management System'; ?></span>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#gallery">Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#announcements">Announcements</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-primary" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="display-4 fw-bold mb-4"><?php echo isset($settings['school_name']) ? htmlspecialchars($settings['school_name']) : 'School Management System'; ?></h1>
                    <p class="lead mb-4">Empowering education through innovative management solutions. Our comprehensive system streamlines administrative tasks, enhances communication, and improves the learning experience.</p>
                    <div class="d-flex gap-3">
                        <a href="login.php" class="btn btn-light btn-lg">Login</a>
                        <a href="#contact" class="btn btn-outline-light btn-lg">Contact Us</a>
                    </div>
                </div>
                <div class="col-lg-6 hero-image mt-5 mt-lg-0">
                    <img src="assets/images/hero-image.jpg" alt="School Management" class="img-fluid" onerror="this.src='https://via.placeholder.com/600x400?text=School+Management+System'">
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-5" id="about">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="assets/images/about-image.jpg" alt="About Our School" class="img-fluid rounded shadow" onerror="this.src='https://via.placeholder.com/600x400?text=About+Our+School'">
                </div>
                <div class="col-lg-6">
                    <h2 class="mb-4">About Our School</h2>
                    <p class="lead"><?php echo isset($settings['school_name']) ? htmlspecialchars($settings['school_name']) : 'Our School'; ?> is committed to providing quality education and fostering a nurturing environment for all students.</p>
                    <p>Founded with a vision to transform education through technology, our school management system offers a comprehensive solution for educational institutions. We believe in creating an ecosystem where administrators, teachers, students, and parents can collaborate effectively.</p>
                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-primary me-2"></i>
                                <span>Experienced Faculty</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-primary me-2"></i>
                                <span>Modern Facilities</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-primary me-2"></i>
                                <span>Comprehensive Curriculum</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-primary me-2"></i>
                                <span>Extracurricular Activities</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="mb-3">Our Key Features</h2>
                <p class="lead text-muted">Discover the powerful tools that make our school management system exceptional</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h5>Student Management</h5>
                        <p class="text-muted">Comprehensive student profiles, attendance tracking, and performance analytics.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5>Exam Management</h5>
                        <p class="text-muted">Create exams, record results, and generate detailed performance reports.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h5>Fee Management</h5>
                        <p class="text-muted">Streamlined fee collection, invoicing, and payment tracking system.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h5>Attendance System</h5>
                        <p class="text-muted">Digital attendance tracking with automated reports and notifications.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h5>Communication Tools</h5>
                        <p class="text-muted">Integrated messaging system for seamless communication between all stakeholders.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        <h5>Photo Gallery</h5>
                        <p class="text-muted">Showcase school events, achievements, and activities through a digital gallery.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="py-5" id="gallery">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="mb-3">Photo Gallery</h2>
                <p class="lead text-muted">Explore our school's vibrant community and activities</p>
            </div>
            <div class="row">
                <?php if (count($featuredImages) > 0): ?>
                    <?php foreach ($featuredImages as $image): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="gallery-item">
                                <img src="<?php echo htmlspecialchars($image['file_path']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>" onerror="this.src='https://via.placeholder.com/400x300?text=School+Event'">
                                <div class="gallery-caption">
                                    <h5><?php echo htmlspecialchars($image['title']); ?></h5>
                                    <p class="small"><?php echo htmlspecialchars($image['category_name']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p>No gallery images available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Announcements Section -->
    <section class="py-5 bg-light" id="announcements">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="mb-3">Latest Announcements</h2>
                <p class="lead text-muted">Stay updated with the latest news and events</p>
            </div>
            <div class="row">
                <?php if (count($announcements) > 0): ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="col-lg-4 mb-4">
                            <div class="card announcement-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($announcement['subject']); ?></h5>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($announcement['message'], 0, 150))); ?>...</p>
                                    <p class="card-text"><small class="text-muted">Posted on <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></small></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p>No announcements available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section" id="contact">
        <div class="container">
            <div class="row align-items-center cta-content">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <h2 class="mb-3">Get in Touch With Us</h2>
                    <p class="lead mb-0">Have questions about our school or the management system? Contact us today!</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="#contact-form" class="btn btn-light btn-lg">Contact Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5" id="contact-form">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h3 class="mb-4">Contact Information</h3>
                    <div class="contact-info mb-4">
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo isset($settings['school_address']) ? htmlspecialchars($settings['school_address']) : '123 Education Street, Knowledge City'; ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo isset($settings['school_phone']) ? htmlspecialchars($settings['school_phone']) : '+1234567890'; ?></p>
                        <p><i class="fas fa-envelope"></i> <?php echo isset($settings['school_email']) ? htmlspecialchars($settings['school_email']) : 'info@schoolms.com'; ?></p>
                        <p><i class="fas fa-globe"></i> <?php echo isset($settings['school_website']) ? htmlspecialchars($settings['school_website']) : 'https://www.schoolms.com'; ?></p>
                    </div>
                    <div class="social-icons">
                        <a href="#" class="me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="me-2"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow">
                        <div class="card-body p-4">
                            <h3 class="mb-4">Send us a Message</h3>
                            <form id="contactForm">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" required>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo isset($settings['school_name']) ? htmlspecialchars($settings['school_name']) : 'School Management System'; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="login.php" class="text-decoration-none text-primary">Login</a> | 
                    <a href="#" class="text-decoration-none text-primary">Privacy Policy</a> | 
                    <a href="#" class="text-decoration-none text-primary">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 70,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Active nav link highlighting
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        
        window.addEventListener('scroll', () => {
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                const sectionHeight = section.clientHeight;
                
                if (pageYOffset >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });
        
        // Contact form submission
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your message! We will get back to you soon.');
            this.reset();
        });
    </script>
</body>
</html>