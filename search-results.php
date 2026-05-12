<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->renthub_db;
    $collection = $db->products;

    // 1. Inputs from URL with Defaults
    $search_text = $_GET['search_text'] ?? '';
    $category = $_GET['category'] ?? '';
    $city = $_GET['city'] ?? '';
    $min_price = !empty($_GET['min_price']) ? (int) $_GET['min_price'] : 0;
    $max_price = !empty($_GET['max_price']) ? (int) $_GET['max_price'] : 999999;
    $sort = $_GET['sort'] ?? 'newest';

    // 2. Build MongoDB Query
    $filter = ['status' => 'available'];
    if (!empty($category)) {
        $filter['category'] = $category;
    }
    if (!empty($city)) {
        $filter['city'] = $city;
    }
    if (!empty($search_text)) {
        $filter['name'] = new MongoDB\BSON\Regex($search_text, 'i');
    }

    // Price Filter
    $filter['price'] = ['$gte' => $min_price, '$lte' => $max_price];

    // Sorting Logic
    $options = ['limit' => 100];
    if ($sort == 'price_low') {
        $options['sort'] = ['price' => 1];
    } elseif ($sort == 'price_high') {
        $options['sort'] = ['price' => -1];
    } elseif ($sort == 'rating') {
        $options['sort'] = ['rating' => -1];
    } else {
        $options['sort'] = ['_id' => -1];
    } // Default: Newest

    $cursor = $collection->find($filter, $options);
    $products = iterator_to_array($cursor);
    $foundCount = count($products);

    // 3. Dynamic Recommendations (Random items for "You May Also Like")
    $suggested_cursor = $collection->aggregate([
        ['$match' => ['status' => 'available']],
        ['$sample' => ['size' => 4]]
    ]);
    $suggestions = iterator_to_array($suggested_cursor);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- 1. Page Title -->
    <title>Elite Marketplace | RentHubPro</title>

    <!-- 2. Auto Refresh (Every 5 Seconds) -->
    <meta http-equiv="refresh" content="5">

    <!-- 3. Premium Favicon (Executive Gold Crown) -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios-filled/50/D4AF37/crown.png">

    <!-- 4. Google Fonts (Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap"
        rel="stylesheet">

    <!-- 5. Bootstrap & Icon Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- 6. Custom CSS -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        :root {
            --gold: #D4AF37;
            --navy: #0A192F;
            --slate: #64748b;
            --light: #f8fafc;
        }

        body {
            background-color: var(--light);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
        }

        /* Professional Header & Search Info */
        .navbar {
            background: var(--navy) !important;
            padding: 15px 0;
        }

        .search-info-bar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 25px 0;
            margin-bottom: 40px;
        }

        /* Sidebar Filter Styling */
        .filter-card {
            background: white;
            border-radius: 24px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            position: sticky;
            top: 100px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
            margin-bottom: 47px;
        }

        /* Premium Product Card */
        .product-card {
            border: none;
            border-radius: 22px;
            background: white;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            height: 100%;
            border: 1px solid #edf2f7;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 25px 50px rgba(10, 25, 47, 0.1);
            border-color: var(--gold);
        }

        .img-box {
            height: 220px;
            overflow: hidden;
            position: relative;
            background: #f1f5f9;
        }

        .img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.6s;
        }

        .product-card:hover .img-box img {
            transform: scale(1.1);
        }

        .price-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            padding: 6px 14px;
            border-radius: 12px;
            font-weight: 800;
            color: var(--navy);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-rent {
            background: var(--navy);
            color: white;
            border-radius: 14px;
            width: 100%;
            font-weight: 700;
            padding: 12px;
            border: 2px solid var(--navy);
            transition: 0.3s;
        }

        .btn-rent:hover {
            background: transparent;
            color: var(--navy);
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top custom-nav">
        <div class="container d-flex align-items-center justify-content-between">

            <a class="navbar-brand d-flex align-items-center text-decoration-none" href="index.php">
                <div class="logo-container">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSeIaL6-BRPUh2B5uruCISrVpH_nMd7gkxwLckL1ZDtiw&s"
                        alt="RentHub Logo" class="custom-logo">
                </div>
                <span class="ms-2 fw-bold fs-5 logo-text">
                    <span style="color: #d4af37;">Rent</span><span class="text-white">HubPro</span>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="mainNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link text-white px-3" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link text-white px-3" href="search-results.php">Browse
                            Rentals</a></li>
                    <li class="nav-item mt-2 mt-lg-0 ms-lg-3">
                        <a class="btn-vendor-nav" href="vendor-apply.php">Become Vendor</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="search-info-bar">
        <div class="container">
            <div class="row align-items-center g-3 mt-2">
                <div class="col-md-4">
                    <h4 class="m-0 fw-800">Explore <span
                            class="text-gold"><?= htmlspecialchars($category ?: 'Marketplace') ?></span></h4>
                    <p class="text-muted small m-0"><?= $foundCount ?> premium items matching your search</p>
                </div>
                <div class="col-md-5">
                    <form action="" method="GET" class="input-group shadow-sm rounded-pill overflow-hidden">
                        <input type="text" name="search_text" class="form-control border-0 ps-4"
                            value="<?= htmlspecialchars($search_text) ?>" placeholder="Search anything...">
                        <button class="btn btn-white bg-white border-0 pe-4" type="submit"><i
                                class="fa fa-search text-gold"></i></button>
                    </form>
                </div>
                <div class="col-md-3">
                    <form action="" method="GET" id="sortForm">
                        <input type="hidden" name="search_text" value="<?= htmlspecialchars($search_text) ?>">
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                        <select name="sort" class="form-select rounded-pill border-0 shadow-sm"
                            onchange="this.form.submit()">
                            <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                            <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Price: Low to High
                            </option>
                            <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Price: High to Low
                            </option>
                            <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>Top Rated</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="filter-card">
                    <form action="" method="GET">
                        <h6 class="fw-800 mb-4 d-flex align-items-center"><i class="fa fa-sliders me-2 text-gold"></i>
                            FILTERS</h6>

                        <div class="mb-4">
                            <label class="small fw-800 text-muted text-uppercase mb-2">Price Range</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="number" name="min_price" class="form-control form-control-sm"
                                    placeholder="Min" value="<?= $_GET['min_price'] ?? '' ?>">
                                <input type="number" name="max_price" class="form-control form-control-sm"
                                    placeholder="Max" value="<?= $_GET['max_price'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="small fw-800 text-muted text-uppercase mb-2">City</label>
                            <select name="city" class="form-select form-select-sm">
                                <option value="">All Cities</option>
                                <option value="Lahore" <?= $city == 'Lahore' ? 'selected' : '' ?>>Lahore</option>
                                <option value="Karachi" <?= $city == 'Karachi' ? 'selected' : '' ?>>Karachi</option>
                                <option value="Islamabad" <?= $city == 'Islamabad' ? 'selected' : '' ?>>Islamabad</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="small fw-800 text-muted text-uppercase mb-2">Category</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                <option value="Dresses" <?= $category == 'Dresses' ? 'selected' : '' ?>>Dresses</option>
                                <option value="Projectors" <?= $category == 'Projectors' ? 'selected' : '' ?>>Projectors
                                </option>
                                <option value="Sound" <?= $category == 'Sound' ? 'selected' : '' ?>>Sound Systems</option>
                                <option value="Decor" <?= $category == 'Decor' ? 'selected' : '' ?>>Decor</option>
                                <option value="Cameras" <?= $category == 'Cameras' ? 'selected' : '' ?>>Cameras</option>
                                <option value="Drones" <?= $category == 'Drones' ? 'selected' : '' ?>>Drones</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-bold">Apply Now</button>
                        <a href="search-results.php"
                            class="btn btn-link w-100 text-muted small mt-2 text-decoration-none">Clear Filters</a>
                    </form>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="row g-4">
                    <?php if ($foundCount > 0): ?>
                        <?php foreach ($products as $p): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="product-card">
                                    <div class="img-box">
                                        <img src="<?= $p['image'] ?? 'https://via.placeholder.com/300x200?text=No+Image' ?>"
                                            alt="Product">
                                        <div class="price-badge">Rs. <?= number_format($p['price'] ?? 0) ?></div>
                                    </div>
                                    <div class="p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span
                                                class="badge bg-light text-primary rounded-pill px-3"><?= $p['category'] ?? 'Rental' ?></span>
                                            <span class="small fw-bold text-warning"><i class="fa fa-star"></i>
                                                <?= $p['rating'] ?? '0.0' ?></span>
                                        </div>
                                        <h6 class="fw-bold text-truncate mb-2" style="font-size: 1.1rem;">
                                            <?= $p['name'] ?? 'Unnamed Asset' ?>
                                        </h6>
                                        <p class="text-muted small mb-4"><i class="fa fa-location-dot me-1 text-gold"></i>
                                            <?= $p['city'] ?? 'Pakistan' ?></p>
                                        <a href="product-details.php?id=<?= $p['_id'] ?>" class="btn btn-rent">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="fa fa-search-minus display-1 text-muted opacity-25 mb-3"></i>
                            <h3 class="fw-800">Oops! No items found.</h3>
                            <p class="text-muted">Try changing your filters or searching for something else like
                                "Projector".</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (count($suggestions) > 0): ?>
                    <div class="mt-5 pt-5">
                        <h5 class="fw-800 mb-4">You May Also <span class="text-gold">Like</span></h5>
                        <div class="row g-3">
                            <?php foreach ($suggestions as $s): ?>
                                <div class="col-md-3 mb-5">
                                    <a href="product-details.php?id=<?= $s['_id'] ?>" class="text-decoration-none text-dark">
                                        <div class="bg-white p-2 rounded-4 border shadow-sm h-100">
                                            <img src="<?= $s['image'] ?? 'https://via.placeholder.com/150' ?>"
                                                class="w-100 rounded-3 mb-2" style="height:120px; object-fit:cover;">
                                            <p class="m-0 small fw-bold text-truncate"><?= $s['name'] ?></p>
                                            <span class="text-gold fw-800 small ">Rs. <?= number_format($s['price']) ?></span>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h4 class="text-white fw-800 mb-4"><span class="text-gold">Rent</span>HubPro</h4>
                    <p>The ultimate rental marketplace for premium gear and luxury assets across Pakistan.</p>
                    <div class="d-flex gap-3 fs-5 mt-4">
                        <a href="https://www.facebook.com/your-profile-id" target="_blank"
                            class="text-white me-3 social-icon">
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
                        <li><a href="index.php" class="text-decoration-none text-white d-block mb-2">Browse Gear</a>
                        </li>
                        <li><a href="vendor-apply.php" class="text-decoration-none text-white  d-block mb-2">How it
                                Works</a></li>
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
</body>

</html>