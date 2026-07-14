<?php
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'This email is already registered.';
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $success = 'Account created successfully! You can now login.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - KAAGAZZ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2E8BC0;
            --dark: #0C2D48;
            --black: #444;
            --light: #666;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .register-header {
            background: var(--primary);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .register-header h2 {
            margin: 0;
            font-weight: 600;
        }
        .register-body {
            padding: 40px;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 192, 0.25);
        }
        .btn-register {
            background: var(--primary);
            color: white;
            font-weight: 500;
            padding: 12px;
            border-radius: 10px;
            border: none;
            width: 100%;
        }
        .btn-register:hover {
            background: var(--dark);
            color: white;
        }
        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .success-message {
            background: #efe;
            color: #3c3;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 192, 0.25);
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <i class="fas fa-user-plus fa-3x mb-3"></i>
            <h2>Create Account</h2>
            <p class="mb-0 opacity-75">Join KAAGAZZ today</p>
        </div>
        <div class="register-body">
            <?php if ($error): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <br><a href="login.php" class="text-decoration-none">Click here to login</a></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label text-muted">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-user text-secondary"></i></span>
                        <input type="text" name="name" class="form-control" placeholder="Enter your name" value="<?php echo $_POST['name'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-envelope text-secondary"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Enter your email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Account Type</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-user-tag text-secondary"></i></span>
                        <select name="role" class="form-select">
                            <option value="user" <?php echo (($_POST['role'] ?? '') === 'user') ? 'selected' : ''; ?>>Buyer</option>
                            <option value="seller" <?php echo (($_POST['role'] ?? '') === 'seller') ? 'selected' : ''; ?>>Seller</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-lock text-secondary"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Create a password" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-lock text-secondary"></i></span>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-register mb-3">
                    <i class="fas fa-user-plus me-2"></i> Create Account
                </button>
                <p class="text-center text-muted mb-0">
                    Already have an account? <a href="login.php" style="color: var(--primary);">Sign in</a>
                </p>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
