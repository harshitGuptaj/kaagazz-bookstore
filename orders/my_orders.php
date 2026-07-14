<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT o.*,
        (SELECT GROUP_CONCAT(CONCAT(b.title, ' x', oi.quantity) SEPARATOR ', ') FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = o.id) as books,
        (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = o.id) as total_items
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - KAAGAZZ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2E8BC0; --dark: #0C2D48; }
        body { font-family: 'Poppins', sans-serif; background: #f8f9fa; }
        .page-header { background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%); color: white; padding: 40px 0; }
        .order-card { background: white; border-radius: 15px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .order-id { font-weight: 700; color: var(--dark); font-size: 1.1rem; }
        .order-date { color: #999; font-size: 0.9rem; }
        .order-total { font-size: 1.3rem; font-weight: 700; color: var(--primary); }
        .order-items { color: #666; margin: 10px 0; }
        .badge-pending { background: #ffc107; color: #000; }
        .badge-processing { background: #17a2b8; }
        .badge-shipped { background: #0d6efd; }
        .badge-delivered { background: #198754; }
        .badge-cancelled { background: #dc3545; }
        .empty-orders { text-align: center; padding: 60px 20px; }
        .empty-orders i { font-size: 4rem; color: #ddd; margin-bottom: 20px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--dark);">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-book"></i> KAAGAZZ
            </a>
            <a href="../index.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-shopping-bag me-2"></i> My Orders</h2>
            <p class="mb-0 opacity-75">Track and manage your orders</p>
        </div>
    </div>

    <div class="container py-4">
        <?php if ($orders->num_rows === 0): ?>
            <div class="empty-orders">
                <i class="fas fa-shopping-bag"></i>
                <h3>No orders yet</h3>
                <p class="text-muted">Start shopping to see your orders here!</p>
                <a href="../index.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i> Shop Now
                </a>
            </div>
        <?php else: ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-id">#<?php echo $order['id']; ?></span>
                            <span class="order-date ms-3"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                        </div>
                        <span class="order-total">$<?php echo number_format($order['total'], 2); ?></span>
                    </div>
                    <div class="order-items">
                        <i class="fas fa-book me-1"></i>
                        <?php echo htmlspecialchars($order['books'] ?? 'N/A'); ?>
                        <span class="ms-2 text-muted">(<?php echo $order['total_items'] ?? 0; ?> items)</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <span class="text-muted me-2">Status:</span>
                            <span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                        </div>
                        <div>
                            <small class="text-muted me-3">
                                <i class="fas fa-credit-card me-1"></i>
                                <?php echo ucfirst($order['payment_method']); ?>
                            </small>
                            <?php if ($order['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this order?');">
                                    <input type="hidden" name="action" value="cancel_order">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($order['shipping_address'])): ?>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars($order['shipping_address']); ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <?php
    // Handle order cancellation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
        $order_id = intval($_POST['order_id']);
        $stmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['status'] === 'pending') {
                // Restore stock
                $stmt2 = $conn->prepare("UPDATE books b JOIN order_items oi ON b.id = oi.book_id SET b.stock = b.stock + oi.quantity WHERE oi.order_id = ?");
                $stmt2->bind_param("i", $order_id);
                $stmt2->execute();
                $stmt2->close();
                // Update status
                $stmt3 = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
                $stmt3->bind_param("i", $order_id);
                $stmt3->execute();
                $stmt3->close();
            }
        }
        $stmt->close();
        header("Location: my_orders.php");
        exit;
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>