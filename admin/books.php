<?php
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = trim($_POST['title']);
                $author = trim($_POST['author']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = intval($_POST['category_id']);
                $stock = intval($_POST['stock']);
                $image = 'default-book.png';

                $stmt = $conn->prepare("INSERT INTO books (title, author, description, price, category_id, seller_id, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdiiss", $title, $author, $description, $price, $category_id, $_SESSION['user_id'], $stock, $image);

                if ($stmt->execute()) {
                    $success = 'Book added successfully!';
                } else {
                    $error = 'Error adding book.';
                }
                $stmt->close();
                break;

            case 'delete':
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $success = 'Book deleted successfully!';
                }
                $stmt->close();
                break;

            case 'update':
                $id = intval($_POST['id']);
                $title = trim($_POST['title']);
                $author = trim($_POST['author']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = intval($_POST['category_id']);
                $stock = intval($_POST['stock']);

                $stmt = $conn->prepare("UPDATE books SET title=?, author=?, description=?, price=?, category_id=?, stock=? WHERE id=?");
                $stmt->bind_param("sssdiisi", $title, $author, $description, $price, $category_id, $stock, $id);
                if ($stmt->execute()) {
                    $success = 'Book updated successfully!';
                } else {
                    $error = 'Error updating book.';
                }
                $stmt->close();
                break;
        }
    }
}

// Get all books
$books = $conn->query("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.created_at DESC");

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - KAAGAZZ Admin</title>
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
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--dark); border-color: var(--dark); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <a href="index.php" class="fs-4 fw-bold text-white text-decoration-none">
                <i class="fas fa-book"></i> KAAGAZZ Admin
            </a>
        </div>
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="books.php" class="active"><i class="fas fa-book"></i> Books</a>
        <a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a>
        <a href="users.php"><i class="fas fa-users"></i> Users</a>
        <a href="categories.php"><i class="fas fa-list"></i> Categories</a>
        <a href="../auth/logout.php" class="mt-5"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Books</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="fas fa-plus me-2"></i> Add New Book
            </button>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($book = $books->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $book['id']; ?></td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
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
                                    <button class="btn btn-sm btn-info text-white" onclick="editBook(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Author</label>
                            <input type="text" name="author" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Price ($)</label>
                                <input type="number" name="price" step="0.01" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Stock</label>
                                <input type="number" name="stock" class="form-control" value="1" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Category</label>
                            <select name="category_id" class="form-select">
                                <?php while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
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

    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="mb-3">
                            <label>Title</label>
                            <input type="text" name="title" id="edit-title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Author</label>
                            <input type="text" name="author" id="edit-author" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" id="edit-description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Price ($)</label>
                                <input type="number" name="price" id="edit-price" step="0.01" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Stock</label>
                                <input type="number" name="stock" id="edit-stock" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Category</label>
                            <select name="category_id" id="edit-category" class="form-select">
                                <?php
                                $categories2 = $conn->query("SELECT * FROM categories ORDER BY name");
                                while($cat = $categories2->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBook(book) {
            document.getElementById('edit-id').value = book.id;
            document.getElementById('edit-title').value = book.title;
            document.getElementById('edit-author').value = book.author;
            document.getElementById('edit-description').value = book.description || '';
            document.getElementById('edit-price').value = book.price;
            document.getElementById('edit-stock').value = book.stock;
            document.getElementById('edit-category').value = book.category_id || '';

            var modal = new bootstrap.Modal(document.getElementById('editBookModal'));
            modal.show();
        }
    </script>
</body>
</html>
