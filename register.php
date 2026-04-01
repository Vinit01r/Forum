<?php
require_once 'config.php';
require_once 'helpers.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Username or email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            try {
                if ($stmt->execute([$username, $email, $hash])) {
                    $_SESSION['flash_success'] = "Registration successful! Please login.";
                    header('Location: login.php');
                    exit;
                } else {
                    $errors[] = "Something went wrong. Please try again.";
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ForumHub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container" style="max-width: 500px; margin-top: 100px; justify-content: center;">
        <div class="card" style="width: 100%;">
            <h2 style="margin-bottom: 20px; text-align: center;">Register for ForumHub</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="flash-message flash-error">
                    <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
                <div style="margin-top: 15px; text-align: center;">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>
    <script src="scripts.js"></script>
</body>
</html>
