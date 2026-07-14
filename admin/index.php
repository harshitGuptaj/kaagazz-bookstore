<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Get stats
$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(total), 0) as sum FROM orders WHERE status != 'cancelled'")->fetch_assoc()['sum'];

// Recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.name as user_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
");

// Low stock books
$low_stock = $conn->query("SELECT * FROM books WHERE stock < 5 ORDER BY stock ASC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KAAGAZZ</title>
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
        .stat-card { border-radius: 15px; padding: 25px; color: #fff; position: relative; overflow: hidden; }
        .stat-card.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card i { font-size: 3rem; opacity: 0.3; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); }
        .stat-number { font-size: 2.5rem; font-weight: 700; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <a href="index.php" class="fs-4 fw-bold text-white text-decoration-none">
                <i class="fas fa-book"></i> KAAGAZZ Admin
            </a>
        </div>
        <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="books.php"><i class="fas fa-book"></i> Books</a>
        <a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a>
        <a href="users.php"><i class="fas fa-users"></i> Users</a>
        <a href="categories.php"><i class="fas fa-list"></i> Categories</a>
        <a href="../auth/logout.php" class="mt-5"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <h2 class="mb-4">Dashboard</h2>

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card blue">
                    <div class="stat-number"><?php echo number_format($total_books); ?></div>
                    <div class="stat-label">Total Books</div>
                    <i class="fas fa-book"></i>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card green">
                    <div class="stat-number"><?php echo number_format($total_users); ?></div>
                    <div class="stat-label">Total Users</div>
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card orange">
                    <div class="stat-number"><?php echo number_format($total_orders); ?></div>
                    <div class="stat-label">Total Orders</div>
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card purple">
                    <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i> Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = $recent_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                        <td>$<?php echo number_format($order['total'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch($order['status']) {
                                                    case 'pending': echo 'warning'; break;
                                                    case 'processing': echo 'info'; break;
                                                    case 'shipped': echo 'primary'; break;
                                                    case 'delivered': echo 'success'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"><?php echo ucfirst($order['status']); ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Low Stock Alert</h5>
                    </div>
                    <div class="card-body">
                        <?php if($low_stock->num_rows > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php while($book = $low_stock->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                            <br><small class="text-muted">by <?php echo htmlspecialchars($book['author']); ?></small>
                                        </div>
                                        <span class="badge bg-danger rounded-pill"><?php echo $book['stock']; ?> left</span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted text-center py-3 mb-0">All books are well stocked!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
