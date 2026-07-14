<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? 'get';

switch ($action) {
    case 'get':
        getCart($conn, $user_id);
        break;
    case 'add':
        $book_id = intval($_POST['book_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        addToCart($conn, $user_id, $book_id, $quantity);
        break;
    case 'update':
        $book_id = intval($_POST['book_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        updateCart($conn, $user_id, $book_id, $quantity);
        break;
    case 'remove':
        $book_id = intval($_POST['book_id'] ?? 0);
        removeFromCart($conn, $user_id, $book_id);
        break;
    case 'clear':
        clearCart($conn, $user_id);
        break;
}

function getCart($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT c.book_id, c.quantity, b.title, b.price, b.image, b.stock
        FROM cart c
        JOIN books b ON c.book_id = b.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    $total = 0;

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $total += $row['price'] * $row['quantity'];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => $total,
        'count' => count($items)
    ]);
}

function addToCart($conn, $user_id, $book_id, $quantity) {
    // Check if item already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND book_id = ?");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Update quantity
        $new_qty = $row['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_qty, $row['id']);
        $stmt->execute();
    } else {
        // Insert new item
        $stmt = $conn->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $user_id, $book_id, $quantity);
        $stmt->execute();
    }
    $stmt->close();

    // Get updated count
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'count' => $result['count'] ?? 0
    ]);
}

function updateCart($conn, $user_id, $book_id, $quantity) {
    if ($quantity <= 0) {
        removeFromCart($conn, $user_id, $book_id);
        return;
    }

    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ?");
    $stmt->bind_param("iii", $quantity, $user_id, $book_id);
    $stmt->execute();
    $stmt->close();

    // Get updated count
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'count' => $result['count'] ?? 0
    ]);
}

function removeFromCart($conn, $user_id, $book_id) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ?");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
    $stmt->close();

    // Get updated count
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'count' => $result['count'] ?? 0
    ]);
}

function clearCart($conn, $user_id) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'count' => 0
    ]);
}
?>
