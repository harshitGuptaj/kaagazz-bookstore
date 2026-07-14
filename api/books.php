<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'search';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$limit = intval($_GET['limit'] ?? 50);

switch ($action) {
    case 'search':
        searchBooks($conn, $search, $category, $limit);
        break;
    case 'get':
        getBook($conn, intval($_GET['id'] ?? 0));
        break;
    case 'all':
        getAllBooks($conn, $limit);
        break;
    default:
        searchBooks($conn, $search, $category, $limit);
}

function searchBooks($conn, $search, $category, $limit) {
    $sql = "SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($search)) {
        $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }

    if (!empty($category)) {
        $sql .= " AND c.slug = ?";
        $params[] = $category;
        $types .= 's';
    }

    $sql .= " ORDER BY b.created_at DESC LIMIT ?";
    $params[] = $limit;
    $types .= 'i';

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $books = [];
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }

    $stmt->close();
    echo json_encode($books);
}

function getBook($conn, $id) {
    $stmt = $conn->prepare("
        SELECT b.*, c.name as category_name, u.name as seller_name
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN users u ON b.seller_id = u.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Book not found']);
    }

    $stmt->close();
}

function getAllBooks($conn, $limit) {
    $stmt = $conn->prepare("
        SELECT b.*, c.name as category_name
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        ORDER BY b.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $books = [];
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }

    $stmt->close();
    echo json_encode($books);
}
?>
