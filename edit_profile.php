<?php
require_once 'config.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    // Process form upload / profile update
    $bio = sanitize_input($_POST['bio'] ?? '');
    
    // Handle File Upload
    $avatar_url = $u['avatar_url'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['avatar']['tmp_name'];
        $file_name = $_FILES['avatar']['name'];
        $file_size = $_FILES['avatar']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_ext, $allowed_ext)) {
            $error = "Only JPG, PNG and GIF files are allowed.";
        } elseif ($file_size > 2097152) { // 2MB
            $error = "File size must be exactly 2 MB or smaller.";
        } else {
            // assure uploads dir exists
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            $new_file_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = 'uploads/' . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $avatar_url = $upload_path;
            } else {
                $error = "Failed to upload avatar.";
            }
        }
    }
    
    // Update Password logic
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $password_has_changed = false;
    
    if (!empty($new_password)) {
        if (!password_verify($current_password, $u['password_hash'])) {
            $error = "Current password is incorrect.";
        } elseif (strlen($new_password) < 8) {
            $error = "New password must be at least 8 characters.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } else {
            $password_has_changed = true;
        }
    }
    
    if (empty($error)) {
        if ($password_has_changed) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET bio = ?, avatar_url = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$bio, $avatar_url, $hash, $user_id]);
            $_SESSION['flash_success'] = "Profile and password updated successfully.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET bio = ?, avatar_url = ? WHERE id = ?");
            $stmt->execute([$bio, $avatar_url, $user_id]);
            $_SESSION['flash_success'] = "Profile updated successfully.";
        }
        
        $_SESSION['avatar_url'] = $avatar_url; // update navbar instantly
        header('Location: edit_profile.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - ForumHub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container" style="max-width: 800px; justify-content: center;">
        <div class="card" style="width: 100%;">
            <a href="profile.php?id=<?= $user_id ?>" class="btn btn-secondary btn-sm" style="margin-bottom: 1rem;"><i class="fas fa-arrow-left"></i> Back to Profile</a>
            <h2 style="margin-bottom: 20px;">Edit Profile</h2>
            
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="flash-message flash-success"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="flash-message flash-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div style="display: flex; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <?php if ($u['avatar_url']): ?>
                            <img src="<?= htmlspecialchars($u['avatar_url']) ?>" alt="Avatar" class="avatar" style="width: 120px; height: 120px; margin-bottom: 10px;">
                        <?php else: ?>
                            <div class="avatar-initials" style="width: 120px; height: 120px; font-size: 3rem; margin-bottom: 10px;">
                                <?= get_avatar_initials($u['username']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="flex-grow: 1;">
                        <div class="form-group">
                            <label>Change Avatar (JPG/PNG/GIF max 2MB)</label>
                            <input type="file" name="avatar" class="form-control" accept="image/png, image/jpeg, image/gif">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" class="form-control" style="min-height: 100px;"><?= htmlspecialchars($u['bio'] ?? '') ?></textarea>
                </div>
                
                <h3 style="margin: 2rem 0 1rem 0; border-top: 1px solid var(--border); padding-top: 2rem;">Change Password</h3>
                <p style="color:var(--muted); font-size:0.875rem; margin-bottom:1rem;">Leave blank if you do not want to change your password.</p>
                
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" minlength="8">
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" minlength="8">
                </div>
                
                <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1rem; text-align: right;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <script src="scripts.js"></script>
</body>
</html>
