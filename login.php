<?php
require_once 'config.php';
require_once 'helpers.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Both fields are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['role'] === 'banned') {
                $error = "This account has been banned.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Update last seen
                $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$user['id']]);
                
                $_SESSION['flash_success'] = "Welcome back, " . $user['username'] . "!";
                header('Location: index.php');
                exit;
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ForumHub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container" style="max-width: 500px; margin-top: 100px; justify-content: center;">
        <div class="card" style="width: 100%;">
            <h2 style="margin-bottom: 20px; text-align: center;">Login to ForumHub</h2>
            
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="flash-message flash-success"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="flash-message flash-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                <div style="margin-top: 15px; text-align: center;">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </form>
        </div>
    </div>
    <script src="scripts.js"></script>
</body>
</html>
