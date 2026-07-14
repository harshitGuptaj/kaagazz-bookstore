<?php
require_once '../config/database.php';

// Check if user is seller or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['seller', 'admin'])) {
    header("Location: ../index.php");
    exit;
}

$seller_id = $_SESSION['user_id'];

// Get seller's books
$my_books = $conn->query("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.seller_id = $seller_id ORDER BY b.created_at DESC");

// Stats
$total_books = $conn->query("SELECT COUNT(*) as count FROM books WHERE seller_id = $seller_id")->fetch_assoc()['count'];
$total_sales = $conn->query("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE b.seller_id = $seller_id")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - KAAGAZZ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2E8BC0; --dark: #0C2D48; }
        body { font-family: 'Poppins', sans-serif; background: #f5f6fa; }
        .sidebar { background: var(--dark); min-height: 100vh; position: fixed; width: 250px; padding-top: 20px; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 20px; display: block; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: var(--primary); color: #fff; }
        .sidebar a i { margin-right: 10px; }
        .main-content { margin-left: 250px; padding: 30px; }
        .stat-card { border-radius: 15px; padding: 25px; color: #fff; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <a href="index.php" class="fs-4 fw-bold text-white text-decoration-none">
                <i class="fas fa-store"></i> Seller Panel
            </a>
        </div>
        <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="my-books.php"><i class="fas fa-book"></i> My Books</a>
        <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Store</a>
        <a href="../auth/logout.php" class="mt-5"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <h2 class="mb-4">Seller Dashboard</h2>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="fs-1 fw-bold"><?php echo $total_books; ?></div>
                    <div>Total Books Listed</div>
                    <i class="fas fa-book fa-2x opacity-25 position-absolute end-0 me-3" style="top:50%;transform:translateY(-50%);"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <div class="fs-1 fw-bold">$<?php echo number_format($total_sales, 2); ?></div>
                    <div>Total Sales</div>
                    <i class="fas fa-dollar-sign fa-2x opacity-25 position-absolute end-0 me-3" style="top:50%;transform:translateY(-50%);"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="fs-1 fw-bold"><?php echo $my_books->num_rows; ?></div>
                    <div>Active Listings</div>
                    <i class="fas fa-list fa-2x opacity-25 position-absolute end-0 me-3" style="top:50%;transform:translateY(-50%);"></i>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">My Books</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($book = $my_books->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['category_name'] ?? 'N/A'); ?></td>
                                <td>$<?php echo number_format($book['price'], 2); ?></td>
                                <td>
                                    <?php if($book['stock'] < 5): ?>
                                        <span class="badge bg-danger"><?php echo $book['stock']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?php echo $book['stock']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($book['stock'] > 0): ?>
                                        <span class="badge bg-primary">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
