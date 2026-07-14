<?php
require_once 'config/database.php';

// Get categories for navbar and book sections
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$categories_nav = $conn->query("SELECT * FROM categories ORDER BY name");

// Get books grouped by category
$categories_with_books = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY name");
while ($cat = $cat_result->fetch_assoc()) {
    $books_stmt = $conn->prepare("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.category_id = ? ORDER BY b.created_at DESC");
    $books_stmt->bind_param("i", $cat['id']);
    $books_stmt->execute();
    $books_result = $books_stmt->get_result();
    $books_list = [];
    while ($book = $books_result->fetch_assoc()) {
        $books_list[] = $book;
    }
    $books_stmt->close();
    $categories_with_books[] = [
        'id' => $cat['id'],
        'name' => $cat['name'],
        'slug' => $cat['slug'],
        'description' => $cat['description'] ?? '',
        'books' => $books_list,
        'book_count' => count($books_list)
    ];
}

// Get all books for search/filter
$all_books = $conn->query("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.title");

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';

// Get cart count if logged in
$cart_count = 0;
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $cart_count = $result['count'] ?? 0;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KAAGAZZ - Online Book Store</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #2E8BC0;
            --dark: #0C2D48;
            --black: #444;
            --light: #666;
            --accent: #ff4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        /* Navbar Styles */
        .navbar {
            background: var(--dark) !important;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff !important;
        }

        .navbar-brand i {
            color: var(--primary);
        }

        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary) !important;
        }

        .navbar-icons .nav-link {
            font-size: 1.3rem;
            padding: 0 15px !important;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--primary);
            color: #fff;
            font-size: 0.7rem;
            padding: 3px 7px;
            border-radius: 50%;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            color: white;
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></svg>');
            background-size: 200px;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }

        /* Section Styles */
        .section-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }

        /* Book Card */
        .book-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }

        .book-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .book-card .card-img-top {
            height: 220px;
            object-fit: cover;
            background: linear-gradient(135deg, #eee 0%, #f5f5f5 100%);
        }

        .book-card .card-body {
            padding: 20px;
        }

        .book-card .book-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .book-card .book-author {
            font-size: 0.9rem;
            color: var(--light);
            margin-bottom: 10px;
        }

        .book-card .book-price {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
        }

        .book-card .book-price .old-price {
            font-size: 0.9rem;
            color: var(--light);
            text-decoration: line-through;
            margin-left: 8px;
        }

        .book-card .btn-add-cart {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .book-card .btn-add-cart:hover {
            background: var(--dark);
            color: #fff;
        }

        .book-card .btn-wishlist {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            border: none;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .book-card .btn-wishlist:hover,
        .book-card .btn-wishlist.active {
            background: var(--accent);
            color: #fff;
        }

        /* Category Badge */
        .category-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--primary);
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Icons Section */
        .icons-section {
            background: #fff;
            padding: 60px 0;
        }

        .icon-box {
            text-align: center;
            padding: 30px;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .icon-box:hover {
            background: #f8f9fa;
            transform: translateY(-5px);
        }

        .icon-box i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .icon-box h5 {
            font-weight: 600;
            color: var(--dark);
        }

        .icon-box p {
            color: var(--light);
            font-size: 0.9rem;
        }

        /* Newsletter Section */
        .newsletter-section {
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            padding: 80px 0;
            color: #fff;
        }

        .newsletter-section .form-control {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
        }

        .newsletter-section .btn-subscribe {
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: #fff;
            padding: 60px 0 30px;
        }

        .footer h5 {
            font-weight: 600;
            margin-bottom: 25px;
            position: relative;
        }

        .footer h5::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary);
        }

        .footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer a:hover {
            color: var(--primary);
            padding-left: 5px;
        }

        .footer-social a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .footer-social a:hover {
            background: var(--primary);
            color: #fff;
        }

        /* Cart Sidebar */
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: #fff;
            z-index: 9999;
            box-shadow: -5px 0 30px rgba(0,0,0,0.2);
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .cart-sidebar.active {
            right: 0;
        }

        .cart-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9998;
            display: none;
        }

        .cart-overlay.active {
            display: block;
        }

        .cart-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .cart-item img {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .cart-item-info {
            flex: 1;
            margin-left: 15px;
        }

        .cart-item-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .cart-item-price {
            color: var(--primary);
            font-weight: 500;
        }

        .cart-item-qty {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cart-item-qty button {
            width: 28px;
            height: 28px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-footer {
            padding: 20px;
            border-top: 1px solid #eee;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .cart-total-amount {
            color: var(--primary);
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 10000;
        }

        .custom-toast {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            padding: 15px 20px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
            min-width: 300px;
        }

        .custom-toast.success {
            border-left: 4px solid #28a745;
        }

        .custom-toast.error {
            border-left: 4px solid #dc3545;
        }

        .custom-toast.info {
            border-left: 4px solid #17a2b8;
        }

        .custom-toast i {
            font-size: 1.5rem;
        }

        .custom-toast.success i { color: #28a745; }
        .custom-toast.error i { color: #dc3545; }
        .custom-toast.info i { color: #17a2b8; }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Search Box */
        .search-box {
            position: relative;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .search-results.active {
            display: block;
        }

        .search-result-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .search-result-item:hover {
            background: #f8f9fa;
        }

        .search-result-item img {
            width: 50px;
            height: 65px;
            object-fit: cover;
            border-radius: 5px;
        }

        /* User Dropdown */
        .user-dropdown .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-radius: 10px;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            z-index: 10001;
            align-items: center;
            justify-content: center;
        }

        .loading-spinner.active {
            display: flex;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .hero-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 767px) {
            .cart-sidebar {
                width: 100%;
                right: -100%;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book"></i> KAAGAZZ
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-list me-1"></i> Categories
                        </a>
                        <ul class="dropdown-menu">
                            <?php while($cat = $categories_nav->fetch_assoc()): ?>
                                <li><a class="dropdown-item" href="#category-<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                            <?php endwhile; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#featured"><i class="fas fa-star me-1"></i> Featured</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#arrivals"><i class="fas fa-clock me-1"></i> New Arrivals</a>
                    </li>
                </ul>

                <div class="navbar-icons d-flex align-items-center">
                    <div class="search-box me-3">
                        <input type="text" class="form-control" id="search-input" placeholder="Search books..." autocomplete="off">
                        <div class="search-results" id="search-results"></div>
                    </div>

                    <a class="nav-link position-relative" href="#" id="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if($is_logged_in): ?>
                        <div class="user-dropdown dropdown ms-3">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="orders/my_orders.php"><i class="fas fa-shopping-bag me-2"></i> My Orders</a></li>
                                <li><a class="dropdown-item" href="#featured"><i class="fas fa-heart me-2"></i> Wishlist</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if($user_role === 'admin'): ?>
                                    <li><a class="dropdown-item" href="admin/index.php"><i class="fas fa-tachometer-alt me-2"></i> Admin Panel</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php elseif($user_role === 'seller'): ?>
                                    <li><a class="dropdown-item" href="seller/index.php"><i class="fas fa-store me-2"></i> Seller Dashboard</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger" href="auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link ms-3" href="auth/login.php">
                            <i class="fas fa-user"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Discover Your Next Great Read</h1>
                    <p class="hero-subtitle">Browse through thousands of books from trusted sellers. Up to 75% off on selected titles!</p>
                    <a href="#featured" class="btn btn-light btn-lg px-4 py-3">
                        <i class="fas fa-shopping-bag me-2"></i> Shop Now
                    </a>
                </div>
                <div class="col-lg-6 text-center mt-5 mt-lg-0">
                    <img src="image/book-1.png" alt="Featured Book" class="img-fluid" style="max-height: 400px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Icons Section -->
    <section class="icons-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="icon-box">
                        <i class="fas fa-shipping-fast"></i>
                        <h5>Free Shipping</h5>
                        <p>On orders over $100</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="icon-box">
                        <i class="fas fa-lock"></i>
                        <h5>Secure Payment</h5>
                        <p>100% secure checkout</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="icon-box">
                        <i class="fas fa-undo"></i>
                        <h5>Easy Returns</h5>
                        <p>10 day return policy</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="icon-box">
                        <i class="fas fa-headset"></i>
                        <h5>24/7 Support</h5>
                        <p>Call us anytime</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- All Books by Category -->
    <?php foreach ($categories_with_books as $catSection): ?>
        <?php if (!empty($catSection['books'])): ?>
            <section class="py-5" id="category-<?php echo $catSection['id']; ?>" style="<?php echo ($catSection['id'] % 2 === 0) ? 'background:#f8f9fa;' : ''; ?>">
                <div class="container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="section-title mb-1"><?php echo htmlspecialchars($catSection['name']); ?></h2>
                            <p class="text-muted mb-0"><?php echo $catSection['book_count']; ?> books available</p>
                        </div>
                    </div>
                    <div class="row g-4">
                        <?php foreach ($catSection['books'] as $book): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <div class="book-card" data-book-id="<?php echo $book['id']; ?>">
                                    <span class="category-badge"><?php echo htmlspecialchars($catSection['name']); ?></span>
                                    <button class="btn-wishlist" onclick="toggleWishlist(<?php echo $book['id']; ?>)">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    <img src="image/<?php echo htmlspecialchars($book['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                    <div class="card-body">
                                        <h5 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                        <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="book-price">$<?php echo number_format($book['price'], 2); ?></span>
                                            <button class="btn btn-add-cart btn-sm" onclick="addToCart(<?php echo $book['id']; ?>)">
                                                <i class="fas fa-cart-plus me-1"></i> Add
                                            </button>
                                        </div>
                                        <?php if ($book['stock'] < 5 && $book['stock'] > 0): ?>
                                            <small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Only <?php echo $book['stock']; ?> left</small>
                                        <?php elseif ($book['stock'] == 0): ?>
                                            <small class="text-danger"><i class="fas fa-times-circle"></i> Out of stock</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Newsletter -->
    <section class="newsletter-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <h2 class="mb-4"><i class="fas fa-envelope me-2"></i> Subscribe for Updates</h2>
                    <p class="mb-4 opacity-75">Get notified about new arrivals, special offers, and more!</p>
                    <form class="d-flex gap-2" id="newsletter-form">
                        <input type="email" class="form-control" placeholder="Enter your email" required>
                        <button type="submit" class="btn btn-subscribe">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5><i class="fas fa-book me-2"></i> KAAGAZZ</h5>
                    <p class="mt-3 opacity-75">Your trusted online bookstore. Buy and sell books across various categories with ease.</p>
                    <div class="footer-social mt-4">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled mt-3">
                        <li class="mb-2"><a href="#">Home</a></li>
                        <li class="mb-2"><a href="#featured">Featured</a></li>
                        <li class="mb-2"><a href="#arrivals">New Arrivals</a></li>
                        <li class="mb-2"><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h5>Categories</h5>
                    <ul class="list-unstyled mt-3">
                        <?php
                        $categories2 = $conn->query("SELECT * FROM categories LIMIT 5");
                        while($cat = $categories2->fetch_assoc()):
                        ?>
                            <li class="mb-2"><a href="#"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled mt-3 opacity-75">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> 123 Book Street, Library City</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i> +1 234 567 890</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i> info@kaagazz.com</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 opacity-25">
            <div class="text-center opacity-75">
                <p>&copy; 2024 KAAGAZZ. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Cart Sidebar -->
    <div class="cart-overlay" id="cart-overlay"></div>
    <div class="cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <h5><i class="fas fa-shopping-cart me-2"></i> Shopping Cart</h5>
            <button class="btn-close" id="close-cart"></button>
        </div>
        <div class="cart-body" id="cart-items">
            <!-- Cart items loaded via AJAX -->
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <span class="cart-total-amount" id="cart-total">$0.00</span>
            </div>
            <button class="btn btn-primary w-100 py-3" id="checkout-btn">
                <i class="fas fa-lock me-2"></i> Proceed to Checkout
            </button>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loading-spinner">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Bootstrap JS & jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Show loading spinner
        function showLoader() {
            $('#loading-spinner').addClass('active');
        }

        function hideLoader() {
            $('#loading-spinner').removeClass('active');
        }

        // Toast notification
        function showToast(message, type = 'info') {
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-times-circle',
                info: 'fa-info-circle'
            };

            const toast = $(`
                <div class="custom-toast ${type}">
                    <i class="fas ${icons[type]}"></i>
                    <span>${message}</span>
                </div>
            `);

            $('#toast-container').append(toast);

            setTimeout(() => {
                toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }

        // Cart functionality
        $('#cart-icon').on('click', function(e) {
            e.preventDefault();
            loadCart();
            $('#cart-sidebar').addClass('active');
            $('#cart-overlay').addClass('active');
        });

        $('#close-cart, #cart-overlay').on('click', function() {
            closeCartSidebar();
        });

        function closeCartSidebar() {
            $('#cart-sidebar').removeClass('active');
            $('#cart-overlay').removeClass('active');
        }

        // Smooth scroll for Shop Now buttons
        $('a[href="#featured"]').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('#featured').offset().top - 80
            }, 800);
        });

        function loadCart() {
            $.ajax({
                url: 'api/cart.php',
                type: 'GET',
                data: { action: 'get' },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        renderCart(data);
                    } else {
                        showToast(data.message || 'Error loading cart', 'error');
                        renderCart({ items: [], total: 0, count: 0 });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Cart load error:', error);
                    renderCart({ items: [], total: 0, count: 0 });
                }
            });
        }

        function renderCart(data) {
            if (!data || !data.items || data.items.length === 0) {
                $('#cart-items').html(`
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Your cart is empty</p>
                        <a href="#featured" class="btn btn-primary btn-sm" onclick="closeCartSidebar()">
                            <i class="fas fa-shopping-bag me-1"></i> Shop Now
                        </a>
                    </div>
                `);
                $('#cart-total').text('$0.00');
                return;
            }

            let html = '';
            data.items.forEach(item => {
                html += `
                    <div class="cart-item">
                        <img src="image/${item.image}" alt="${item.title}">
                        <div class="cart-item-info">
                            <div class="cart-item-title">${item.title}</div>
                            <div class="cart-item-price">$${parseFloat(item.price).toFixed(2)} x ${item.quantity}</div>
                        </div>
                        <div class="cart-item-qty">
                            <button onclick="updateCartQty(${item.book_id}, ${item.quantity - 1})"><i class="fas fa-minus"></i></button>
                            <span>${item.quantity}</span>
                            <button onclick="updateCartQty(${item.book_id}, ${item.quantity + 1})"><i class="fas fa-plus"></i></button>
                        </div>
                        <button class="btn text-danger ms-2" onclick="removeFromCart(${item.book_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            });

            $('#cart-items').html(html);
            $('#cart-total').text('$' + parseFloat(data.total).toFixed(2));
        }

        function addToCart(bookId) {
            <?php if(!$is_logged_in): ?>
                showToast('Please login to add items to cart', 'error');
                setTimeout(() => {
                    window.location.href = 'auth/login.php';
                }, 1500);
                return;
            <?php endif; ?>

            showLoader();
            $.ajax({
                url: 'api/cart.php',
                type: 'POST',
                data: { action: 'add', book_id: bookId, quantity: 1 },
                dataType: 'json',
                success: function(data) {
                    hideLoader();
                    if (data.success) {
                        showToast('Item added to cart!', 'success');
                        updateCartBadge(data.count);
                    } else {
                        showToast(data.message || 'Error adding to cart', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    hideLoader();
                    console.error('Add to cart error:', error);
                    showToast('Error adding to cart. Please try again.', 'error');
                }
            });
        }

        function updateCartQty(bookId, quantity) {
            if (quantity < 1) {
                removeFromCart(bookId);
                return;
            }

            $.ajax({
                url: 'api/cart.php',
                type: 'POST',
                data: { action: 'update', book_id: bookId, quantity: quantity },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        loadCart();
                        updateCartBadge(data.count);
                    } else {
                        showToast(data.message || 'Error updating cart', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Update cart error:', error);
                    showToast('Error updating cart', 'error');
                }
            });
        }

        function removeFromCart(bookId) {
            $.ajax({
                url: 'api/cart.php',
                type: 'POST',
                data: { action: 'remove', book_id: bookId },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        showToast('Item removed from cart', 'info');
                        loadCart();
                        updateCartBadge(data.count);
                    } else {
                        showToast(data.message || 'Error removing item', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Remove from cart error:', error);
                    showToast('Error removing item', 'error');
                }
            });
        }

        function updateCartBadge(count) {
            const badge = $('.cart-badge');
            if (count > 0) {
                badge.text(count).show();
            } else {
                badge.hide();
            }
        }

        // Wishlist
        function toggleWishlist(bookId) {
            <?php if(!$is_logged_in): ?>
                showToast('Please login to add items to wishlist', 'error');
                setTimeout(() => {
                    window.location.href = 'auth/login.php';
                }, 1500);
                return;
            <?php endif; ?>

            const btn = $(`.book-card[data-book-id="${bookId}"] .btn-wishlist`);

            $.ajax({
                url: 'api/wishlist.php',
                type: 'POST',
                data: { book_id: bookId },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        if (data.action === 'added') {
                            btn.addClass('active').find('i').removeClass('far').addClass('fas');
                            showToast('Added to wishlist!', 'success');
                        } else {
                            btn.removeClass('active').find('i').removeClass('fas').addClass('far');
                            showToast('Removed from wishlist', 'info');
                        }
                    } else {
                        showToast(data.message || 'Error updating wishlist', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Wishlist error:', error);
                    showToast('Error updating wishlist', 'error');
                }
            });
        }

        // Search
        let searchTimeout;
        $('#search-input').on('input', function() {
            clearTimeout(searchTimeout);
            const query = $(this).val().trim();

            if (query.length < 2) {
                $('#search-results').removeClass('active');
                return;
            }

            searchTimeout = setTimeout(() => {
                $.ajax({
                    url: 'api/books.php',
                    type: 'GET',
                    data: { search: query },
                    dataType: 'json',
                    success: function(data) {
                        renderSearchResults(data);
                    },
                    error: function(xhr, status, error) {
                        console.error('Search error:', error);
                        $('#search-results').removeClass('active');
                    }
                });
            }, 300);
        });

        function renderSearchResults(books) {
            if (books.length === 0) {
                $('#search-results').html('<div class="p-3 text-muted">No books found</div>').addClass('active');
                return;
            }

            let html = '';
            books.forEach(book => {
                html += `
                    <div class="search-result-item" onclick="window.location.href='#book-${book.id}'">
                        <img src="image/${book.image}" alt="${book.title}">
                        <div class="ms-3">
                            <div class="fw-semibold">${book.title}</div>
                            <div class="text-muted small">by ${book.author}</div>
                            <div class="text-primary fw-bold">$${parseFloat(book.price).toFixed(2)}</div>
                        </div>
                    </div>
                `;
            });

            $('#search-results').html(html).addClass('active');
        }

        $(document).click(function(e) {
            if (!$(e.target).closest('.search-box').length) {
                $('#search-results').removeClass('active');
            }
        });

        // Newsletter
        $('#newsletter-form').on('submit', function(e) {
            e.preventDefault();
            const email = $(this).find('input').val();
            showToast('Thank you for subscribing!', 'success');
            $(this)[0].reset();
        });

        // Checkout
        $('#checkout-btn').on('click', function() {
            <?php if(!$is_logged_in): ?>
                showToast('Please login to checkout', 'error');
                setTimeout(() => {
                    window.location.href = 'auth/login.php';
                }, 1500);
                return;
            <?php endif; ?>

            showToast('Redirecting to checkout...', 'info');
            setTimeout(() => {
                window.location.href = 'orders/checkout.php';
            }, 1000);
        });

        // Load cart count on page load
        $(document).ready(function() {
            <?php if($is_logged_in): ?>
            $.ajax({
                url: 'api/cart.php',
                type: 'GET',
                data: { action: 'get' },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        updateCartBadge(data.count);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Initial cart load error:', error);
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
