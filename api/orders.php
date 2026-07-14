<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? 'create';

switch ($action) {
    case 'create':
        createOrder($conn, $user_id);
        break;
    case 'my_orders':
        getMyOrders($conn, $user_id);
        break;
    case 'cancel':
        $order_id = intval($_POST['order_id'] ?? 0);
        cancelOrder($conn, $user_id, $order_id);
        break;
}

function createOrder($conn, $user_id) {
    // Get cart items
    $stmt = $conn->prepare("
        SELECT c.book_id, c.quantity, b.price, b.stock, b.title
        FROM cart c
        JOIN books b ON c.book_id = b.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        return;
    }

    // Calculate total and check stock
    $items = [];
    $total = 0;

    while ($row = $result->fetch_assoc()) {
        if ($row['quantity'] > $row['stock']) {
            echo json_encode(['success' => false, 'message' => "Not enough stock for {$row['title']}"]);
            return;
        }
        $items[] = $row;
        $total += $row['price'] * $row['quantity'];
    }

    // Create order
    $shipping_address = $_POST['shipping_address'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cod';

    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, shipping_address, payment_method) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $user_id, $total, $shipping_address, $payment_method);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert order items and update stock
    foreach ($items as $item) {
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $item['book_id'], $item['quantity'], $item['price']);
        $stmt->execute();

        // Reduce stock
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

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $order_id,
        'total' => $total
    ]);
}

function getMyOrders($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT o.*,
            (SELECT GROUP_CONCAT(b.title) FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = o.id) as books
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    $stmt->close();
    echo json_encode($orders);
}

function cancelOrder($conn, $user_id, $order_id) {
    $stmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['status'] === 'pending') {
            // Restore stock
            $stmt = $conn->prepare("
                UPDATE books b
                JOIN order_items oi ON b.id = oi.book_id
                SET b.stock = b.stock + oi.quantity
                WHERE oi.order_id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();

            // Update order status
            $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cannot cancel this order']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
}
?>
