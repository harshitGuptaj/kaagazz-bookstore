<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['seller', 'admin'])) {
    header("Location: ../index.php");
    exit;
}

$seller_id = $_SESSION['user_id'];

// Handle add book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $isbn = trim($_POST['isbn'] ?? '');
        $published_year = intval($_POST['published_year'] ?? 0);
        $image = 'book-1.png';

        if (!empty($title) && $price > 0) {
            $stmt = $conn->prepare("INSERT INTO books (title, author, description, price, stock, category_id, seller_id, isbn, published_year, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdiiiiss", $title, $author, $description, $price, $stock, $category_id, $seller_id, $isbn, $published_year, $image);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: my-books.php");
        exit;
    } elseif ($_POST['action'] === 'delete') {
        $book_id = intval($_POST['book_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM books WHERE id = ? AND seller_id = ?");
        $stmt->bind_param("ii", $book_id, $seller_id);
        $stmt->execute();
        $stmt->close();
        header("Location: my-books.php");
        exit;
    } elseif ($_POST['action'] === 'update_stock') {
        $book_id = intval($_POST['book_id'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $stmt = $conn->prepare("UPDATE books SET stock = ? WHERE id = ? AND seller_id = ?");
        $stmt->bind_param("iii", $stock, $book_id, $seller_id);
        $stmt->execute();
        $stmt->close();
        header("Location: my-books.php");
        exit;
    }
}

// Get seller's books
$stmt = $conn->prepare("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.seller_id = ? ORDER BY b.created_at DESC");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$my_books = $stmt->get_result();
$stmt->close();

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Books - Seller Dashboard</title>
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
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="my-books.php" class="active"><i class="fas fa-book"></i> My Books</a>
        <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Store</a>
        <a href="../auth/logout.php" class="mt-5"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">My Books</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="fas fa-plus me-2"></i> Add Book
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = $my_books->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['category_name'] ?? 'N/A'); ?></td>
                                <td>$<?php echo number_format($book['price'], 2); ?></td>
                                <td>
                                    <form method="POST" class="d-inline" style="width: 80px;">
                                        <input type="hidden" name="action" value="update_stock">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        <input type="number" name="stock" value="<?php echo $book['stock']; ?>" min="0" class="form-control form-control-sm" onchange="this.form.submit()">
                                    </form>
                                </td>
                                <td>
                                    <?php if ($book['stock'] > 0): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this book?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Book</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Author *</label>
                                <input type="text" name="author" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Price ($) *</label>
                                <input type="number" name="price" class="form-control" step="0.01" min="0.01" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stock *</label>
                                <input type="number" name="stock" class="form-control" min="0" value="10" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select">
                                    <option value="0">-- Select --</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ISBN</label>
                                <input type="text" name="isbn" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Published Year</label>
                                <input type="number" name="published_year" class="form-control" min="1900" max="2099">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>