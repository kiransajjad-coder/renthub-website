<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->renthub_db;

    $userData = null;
    if (isset($_SESSION['user_id'])) {
        $userData = $db->users->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])]);
        
        // ✨ IMAGE PATH LOGIC
        if ($userData && !empty($userData['profile_img'])) {
            // Agar aapki images 'uploads' folder mein hain toh rasta set karein
            // Agar DB mein poora URL hai toh 'uploads/' hataden
            $userProfilePic = 'uploads/' . $userData['profile_img']; 
        } else {
            // Agar image nahi hai toh initials dikhayein
            $nameForAvatar = urlencode($userData['full_name'] ?? 'User');
            $userProfilePic = "https://ui-avatars.com/api/?name=$nameForAvatar&background=d4af37&color=000&bold=true";
        }
    }

    $collection = $db->products;
    $categories = $collection->distinct("category") ?: ["Cameras", "Dresses", "Drones"];
    $popularProducts = $collection->find(['status' => 'available'], ['limit' => 4]);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 1. Browser Tab ka Naam -->
    <title>RentHub Pro | Premium Executive Fleet</title>

    <!-- 2. Auto Refresh Code (Har 30 seconds baad page refresh hoga) -->
    <meta http-equiv="refresh" content="30">

    <!-- 3. Browser Tab ka Logo (Favicon) -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 4. Google Fonts (Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <!-- 5. External CSS & Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/style.css">

    <!-- 6. Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        /* Sidebar-Style Dropdown Menu */
        .nav-profile-img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #d4af37;
            cursor: pointer;
        }

        .dropdown-menu-sidebar {
            background-color: #111;
            border: 1px solid #d4af37;
            border-radius: 12px;
            padding: 10px;
            margin-top: 15px !important;
            min-width: 240px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
        }

       
        .user-info-header {
            padding: 10px 15px;
            border-bottom: 1px solid #333;
            margin-bottom: 10px;
        }

        .text-gold {
            color: #d4af37 !important;
        }
        .dropdown-item:hover {
    background-color: rgba(212, 175, 55, 0.15) !important; /* Halka Gold tint */
    color: #d4af37 !important; /* Text gold ho jayega */
    padding-left: 20px !important; /* Halka sa right shift effect */
     transform: translateX(5px)  !important;
}
.dropdown-item:hover i {
    transform: scale(1.2);
    color: #d4af37 !important;
         transform: translateX(5px)  !important;
}

/* Logout link ke liye special red hover (Optional) */
.dropdown-item.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.1) !important;
    color: #dc3545 !important;

}
        
    </style>
</head>


<body>

<nav class="navbar navbar-expand-lg fixed-top bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand text-white logo-text fw-bold" href="index.php">
            <span style="color: #d4af37;">Rent</span>HubPro
        </a>

        <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="fa fa-bars text-white"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link text-white px-3" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link text-white px-3" href="search-results.php">Browse Rentals</a></li>
                <li class="nav-item"><a class="nav-link text-gold px-3 fw-semibold" href="vendor-apply.php" style="color: #d4af37;">Become Vendor</a></li>

                <?php if (isset($userData) && !empty($userData)): ?>
                    <li class="nav-item dropdown ms-lg-3">
                        <a class="nav-link p-0 d-flex align-items-center" href="#" id="profileMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php
                                // User information handling
                                $name_to_show = htmlspecialchars($userData['full_name'] ?? $userData['name'] ?? 'User');
                                
                                // Image logic: check if path exists in DB, otherwise use UI Avatars
                                if (!empty($userData['profile_img'])) {
                                    $profile_pic = 'uploads/' . $userData['profile_img'];
                                } else {
                                    $profile_pic = 'https://ui-avatars.com/api/?name=' . urlencode($name_to_show) . '&background=d4af37&color=000&bold=true';
                                }
                            ?>
                            <span class="text-white me-2 d-none d-lg-inline small"><?= $name_to_show ?></span>
                            <img src="<?= $profile_pic ?>" class="nav-profile-img shadow-sm" 
                                 style="width:38px; height:38px; border-radius:50%; border:2px solid #d4af37; object-fit: cover;">
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2 mt-2" style="background: #1a1a1a; min-width: 200px;">
                            <li class="p-3 border-bottom border-secondary border-opacity-25">
                                <h6 class="m-0 text-warning"><?= $name_to_show ?></h6>
                                <small class="text-white">Verified User</small>
                            </li>
                            
                            <li><a class="dropdown-item text-white py-2" href="admin/view-payments.php">
                                <i class="fa fa-user-shield me-2" style="color: #d4af37; width: 20px;"></i> Admin Dashboard</a>
                            </li>
                            
                            <li><a class="dropdown-item text-white py-2" href="user-dashboard.php">
                                <i class="fa fa-gauge-high me-2" style="color: #d4af37; width: 20px;"></i> User Dashboard</a>
                            </li>
                            
                            <li><a class="dropdown-item text-white py-2" href="profile.php">
                                <i class="fa fa-user-gear me-2" style="color: #d4af37; width: 20px;"></i> Profile Update</a>
                            </li>

                            <li><hr class="dropdown-divider bg-secondary opacity-25"></li>
                            
                            <li>
                                <a class="dropdown-item text-danger py-2 fw-bold" href="logout.php">
                                    <i class="fa fa-power-off me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-3">
                        <a href="login.html" class="btn btn-outline-light rounded-pill px-4 me-2 btn-sm">Login</a>
                        <a href="register.html" class="btn rounded-pill px-4 btn-sm" style="background-color: #d4af37; color: #000; font-weight: bold;">Join Free</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

    <header class="hero-section text-white text-center">
        <div class="container">
            <h1 class="hero-title mb-3">Rent <span class="text-gold">Premium</span> Gear <br> In Seconds.</h1>
            <p class="lead mb-5 opacity-75">Join Pakistan's largest marketplace for high-end equipment rentals.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#popular" class="btn btn-gold px-5 py-3 shadow">Explore Marketplace</a>
                <a href="vendor-apply.php" class="btn btn-outline-light rounded-pill px-5 py-3">List Your Item</a>
            </div>
        </div>
    </header>


    <div class="container">
        <div class="search-container">
            <form action="search-results.php" method="GET" class="row g-0 align-items-center">
                <div class="col-md-3 search-box">
                    <label class="search-label">Category</label>
                    <select name="category" class="form-select border-0 fw-bold">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat)
                            echo "<option value='$cat'>$cat</option>"; ?>
                    </select>
                </div>
                <div class="col-md-3 search-box">
                    <label class="search-label">City</label>
                    <select name="city" class="form-select border-0 fw-bold">
                        <option value="">All Cities</option>
                        <?php
                        $cities = ['Rawalpindi', 'Islamabad', 'Multan', 'Lahore', 'Faisalabad', 'Karachi'];
                        foreach ($cities as $city) {
                            echo "<option value='$city'>$city</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 search-box">
                    <label class="search-label">Rental Period</label>
                    <div class="d-flex align-items-center">
                       <input type="text" id="datePicker" class="form-control border-0 fw-bold" placeholder="Select Dates">
                    </div>
                </div>
                <div class="col-md-2 p-2">
                    <a href="search-results.php" class="btn btn-gold w-100 py-3 rounded-4 shadow">SEARCH</a>
                </div>
            </form>
        </div>
    </div>

    <section class="py-5 mt-5 bg-light">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-md-3">
                    <i class="fa fa-boxes-stacked text-gold fa-2x mb-3"></i>
                    <h6 class="fw-bold">Wide Range</h6>
                    <p class="small text-muted">Cameras to Furniture</p>
                </div>
                <div class="col-md-3">
                    <i class="fa fa-shield-halved text-gold fa-2x mb-3"></i>
                    <h6 class="fw-bold">Secure Payments</h6>
                    <p class="small text-muted">Escrow Protected</p>
                </div>
                <div class="col-md-3">
                    <i class="fa fa-id-card text-gold fa-2x mb-3"></i>
                    <h6 class="fw-bold">Verified Vendors</h6>
                    <p class="small text-muted">Trusted Community</p>
                </div>
                <div class="col-md-3">
                    <i class="fa fa-headset text-gold fa-2x mb-3"></i>
                    <h6 class="fw-bold">24/7 Support</h6>
                    <p class="small text-muted">Always here to help</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h3 class="fw-800 display-6">Explore <span class="text-gold">Categories</span></h3>
                <p class="text-muted">Browse our wide range of premium rental assets</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <a href="search-results.php?category=Cameras" class="text-decoration-none">
                        <div class="cat-card">
                            <img src="https://cdn.thewirecutter.com/wp-content/media/2024/11/ADVICE-BUY-500-FILM-CAMERA-2048px-5330-2x1-1.jpg?width=2048&quality=75&crop=2:1&auto=webp"
                                alt="Cameras">
                            <div class="cat-overlay">
                                <div>
                                    <i class="fa fa-camera fa-2x mb-2 text-gold"></i>
                                    <h4 class="m-0">Cameras</h4>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="search-results.php?category=Dresses" class="text-decoration-none">
                        <div class="cat-card">
                            <img src="https://www.nameerabyfarooq.com/cdn/shop/files/ZincRedKameezLehengaforPakistaniBridalDress_1080x.jpg?v=1683300571"
                                alt="Bridal Dresses">
                            <div class="cat-overlay">
                                <div>
                                    <i class="fa fa-person-dress fa-2x mb-2 text-gold"></i>
                                    <h4 class="m-0">Bridal Dresses</h4>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="search-results.php?category=Drones" class="text-decoration-none">
                        <div class="cat-card">
                            <img src="https://i.pcmag.com/imagery/roundup-products/01U2LQkvkkktQRsYOfydaqL..v1745944008.jpg"
                                alt="Drones">
                            <div class="cat-overlay">
                                <div>
                                    <i class="fa-solid fa-plane-up fa-2x mb-2 text-gold"></i>
                                    <h4 class="m-0">Drones</h4>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="search-results.php?category=Decor" class="text-decoration-none">
                        <div class="cat-card">
                            <img src="https://tayibsweddings.co.uk/wp-content/uploads/2025/08/3684378004997288074_753739671_1.jpg"
                                alt="Event Decor">
                            <div class="cat-overlay">
                                <div>
                                    <i class="fa-solid fa-archway fa-2x mb-2 text-gold"></i>
                                    <h4 class="m-0">Event Decor</h4>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="search-results.php?category=Projectors" class="text-decoration-none">
                        <div class="cat-card">
                            <img src="https://media.wired.com/photos/629feede5da297afa9ff5e6f/master/pass/Home-Theater-Gear-GettyImages-95781853.jpg"
                                alt="Projectors">
                            <div class="cat-overlay">
                                <div>
                                    <i class="fa fa-video fa-2x mb-2 text-gold"></i>
                                    <h4 class="m-0">Projectors</h4>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="search-results.php?category=Sound" class="text-decoration-none">
                        <div class="cat-card">
                            <img src="https://abaudiovisual.pk/wp-content/uploads/2025/08/Professional-Sound-System-in-Pakistan.jpg"
                                alt="Sound Systems">
                            <div class="cat-overlay">
                                <div>
                                    <i class="fa fa-volume-high fa-2x mb-2 text-gold"></i>
                                    <h4 class="m-0">Sound Systems</h4>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="popular" class="py-5 bg-light-gray">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h3 class="fw-800 m-0 display-6">Hot <span class="text-gold">Rentals</span></h3>
                    <p class="text-muted small">Handpicked trending assets for you</p>
                </div>
                <a href="search-results.php" class="btn btn-view-all btn-warning">
                    View All <i class="fa fa-arrow-right ms-2"></i>
                </a>
            </div>

            <div class="row g-4">
                <?php foreach ($popularProducts as $p): ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="product-card">
                            <div class="product-img-wrapper">
                                <div class="card-price-tag">Rs. <?= number_format($p['price']) ?><span>/day</span></div>
                                <img src="<?= $p['image'] ?>" class="product-img" alt="<?= $p['name'] ?>">
                                <div class="img-overlay">
                                    <span class="badge-status">Trending</span>
                                </div>
                            </div>

                            <div class="product-info">
                                <h6 class="product-title"><?= $p['name'] ?></h6>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="location">
                                        <i class="fa-solid fa-location-dot me-1 text-gold"></i> <?= $p['city'] ?>
                                    </div>
                                    <div class="rating">
                                        <i class="fa fa-star text-warning"></i> <span class="fw-bold">4.8</span>
                                    </div>
                                </div>
                                <a href="product-details.php?id=<?= $p['_id'] ?>" class="btn-rent-now">
                                    Rent Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-5 text-center">
        <div class="container py-5">
            <h3 class="fw-800 mb-5">How It <span class="text-gold">Works</span></h3>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="step-icon"><i class="fa fa-search"></i></div>
                    <h5 class="fw-bold">Search Item</h5>
                    <p class="text-muted">Browse through thousands of premium items.</p>
                </div>
                <div class="col-md-4">
                    <div class="step-icon"><i class="fa fa-calendar-check"></i></div>
                    <h5 class="fw-bold">Book & Pay</h5>
                    <p class="text-muted">Select dates and pay securely via our platform.</p>
                </div>
                <div class="col-md-4">
                    <div class="step-icon"><i class="fa fa-handshake"></i></div>
                    <h5 class="fw-bold">Receive & Return</h5>
                    <p class="text-muted">Get your item delivered and return after use.</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h4 class="text-white fw-800 mb-4"><span class="text-gold">Rent</span>HubPro</h4>
                    <p>The ultimate rental marketplace for premium gear and luxury assets across Pakistan.</p>
                    <div class="d-flex gap-3 fs-5 mt-4">
                       <a href="https://www.facebook.com/your-profile-id" target="_blank" class="text-white me-3 social-icon">
    <i class="fab fa-facebook-f"></i>
</a>

<a href="https://www.instagram.com/kiran" target="_blank" class="text-white me-3 social-icon">
    <i class="fab fa-instagram"></i>
</a>

<a href="https://www.twitter.com/your-username" target="_blank" class="text-white social-icon">
    <i class="fab fa-twitter"></i>
</a>
                      <a href="https://wa.me/yournumber" class="whatsapp-float text-white" target="_blank">
    <i class="fab fa-whatsapp my-float"></i>
   
</a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h6 class="text-white fw-bold mb-4">QUICK LINKS</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-decoration-none text-white d-block mb-2">Browse Gear</a></li>
                        <li><a href="vendor-apply.php" class="text-decoration-none text-white  d-block mb-2">How it Works</a></li>
                        <li><a href="login.html" class="text-decoration-none text-white d-block mb-2">Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="text-white fw-bold mb-4">SUPPORT</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none text-white d-block mb-2">FAQs</a></li>
                        <li><a href="#" class="text-decoration-none text-white d-block mb-2">Privacy Policy</a></li>
                        <li><a href="#" class="text-decoration-none text-white d-block mb-2">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6 class="text-white fw-bold mb-4">NEWSLETTER</h6>
                    <div class="input-group">
                        <input type="text" class="form-control bg-dark border-0 text-white" placeholder="Email Address">
                        <button class="btn btn-gold">JOIN</button>
                    </div>
                </div>
            </div>
            <hr class="mt-5 border-secondary opacity-25">
            <p class="text-center small m-0">© <?= date('Y'); ?> RentHubPro. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onscroll = function () {
            var nav = document.querySelector('.navbar');
            if (window.pageYOffset > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        };
        flatpickr("#datePicker", {
    dateFormat: "Y-m-d", // Change format as needed
    allowInput: true,    // Allows user to type date manually
    // Add "mode: 'range'" here if you want to select start/end dates
  });

    </script>
</body>

</html>