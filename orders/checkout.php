<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get cart items
$stmt = $conn->prepare("
    SELECT c.book_id, c.quantity, b.title, b.price, b.image, b.stock
    FROM cart c
    JOIN books b ON c.book_id = b.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total += $row['price'] * $row['quantity'];
}
$stmt->close();

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = $conn->real_escape_string($_POST['shipping_address'] ?? '');
    $payment_method = $conn->real_escape_string($_POST['payment_method'] ?? 'cod');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');

    if (empty($shipping_address)) {
        $error = "Please enter a shipping address";
    } elseif (count($cart_items) === 0) {
        $error = "Your cart is empty";
    } else {
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total, shipping_address, payment_method, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("idss", $user_id, $total, $shipping_address, $payment_method);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        // Add order items
        foreach ($cart_items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $item['book_id'], $item['quantity'], $item['price']);
            $stmt->execute();
            $stmt->close();

            // Update stock
            $stmt = $conn->prepare("UPDATE books SET stock = stock - ? WHERE id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['book_id']);
            $stmt->execute();
            $stmt->close();
        }

        // Clear cart
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Update user phone if provided
        if (!empty($phone)) {
            $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
            $stmt->bind_param("si", $phone, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        $success = "Order placed successfully! Order ID: #" . $order_id;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - KAAGAZZ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2E8BC0;
            --dark: #0C2D48;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
        }
        .checkout-container {
            max-width: 1000px;
            margin: 50px auto;
        }
        .checkout-header {
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .cart-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .cart-item img {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }
        .cart-item-info {
            flex: 1;
        }
        .cart-item-title {
            font-weight: 600;
            color: var(--dark);
        }
        .cart-item-price {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.1rem;
        }
        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-row.total {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
            border-top: 2px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
        }
        .btn-place-order {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            margin-top: 20px;
        }
        .btn-place-order:hover {
            background: var(--dark);
        }
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
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

    <div class="container checkout-container">
        <div class="checkout-header">
            <h2><i class="fas fa-credit-card"></i> Checkout</h2>
            <p class="mb-0">Complete your order by providing your shipping details</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <div class="mt-3">
                    <a href="../index.php" class="btn btn-primary">Continue Shopping</a>
                    <a href="my_orders.php" class="btn btn-outline-primary ms-2">View My Orders</a>
                </div>
            </div>
        <?php elseif (count($cart_items) === 0 && !isset($success)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p class="text-muted">Add some books to your cart and come back!</p>
                <a href="../index.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Shop Now
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-7">
                    <h4 class="mb-4">Cart Items (<?php echo count($cart_items); ?>)</h4>

                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="../image/<?php echo htmlspecialchars($item['image']); ?>" alt="">
                            <div class="cart-item-info">
                                <div class="cart-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                <div class="text-muted">Qty: <?php echo $item['quantity']; ?></div>
                            </div>
                            <div class="cart-item-price">
                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" class="mt-4">
                        <h5 class="mb-3"><i class="fas fa-truck"></i> Shipping Details</h5>

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required placeholder="Enter your phone number">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Shipping Address *</label>
                            <textarea name="shipping_address" class="form-control" rows="3" required placeholder="Enter your full address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="cod">Cash on Delivery</option>
                                <option value="card">Credit/Debit Card</option>
                                <option value="upi">UPI</option>
                            </select>
                        </div>
                </div>

                <div class="col-lg-5">
                    <div class="summary-card">
                        <h4 class="mb-4">Order Summary</h4>

                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Shipping</span>
                            <span>FREE</span>
                        </div>

                        <div class="summary-row">
                            <span>Tax</span>
                            <span>Included</span>
                        </div>

                        <div class="summary-row total">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>

                        <button type="submit" class="btn btn-place-order">
                            <i class="fas fa-lock"></i> Place Order
                        </button>

                        <div class="text-center mt-3">
                            <small class="text-muted"><i class="fas fa-shield-alt"></i> Secure checkout</small>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
