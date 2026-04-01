<nav class="navbar">
    <a href="index.php" class="navbar-brand">
        <i class="fas fa-graduation-cap"></i> ForumHub
    </a>
    
    <div class="search-bar">
        <form action="search.php" method="GET" style="margin:0; padding:0;">
            <input type="text" id="searchInput" name="q" placeholder="Search topics..." autocomplete="off">
        </form>
        <div class="search-results" id="searchResults"></div>
    </div>
    
    <div class="nav-links">
        <a href="index.php">Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'moderator'): ?>
                <a href="admin/index.php">Admin Tools</a>
            <?php endif; ?>
            <a href="profile.php?id=<?= $_SESSION['user_id'] ?>" style="display:flex; align-items:center; gap:0.5rem; text-decoration:none;">
                <?php
                    // Fetch user info for navbar to be robust if avatar changes
                    global $pdo;
                    $nav_stmt = $pdo->prepare("SELECT avatar_url, username FROM users WHERE id = ?");
                    $nav_stmt->execute([$_SESSION['user_id']]);
                    $nav_u = $nav_stmt->fetch();
                ?>
                <?php if ($nav_u && $nav_u['avatar_url']): ?>
                    <img src="<?= htmlspecialchars($nav_u['avatar_url']) ?>" alt="Avatar" class="avatar">
                <?php else: ?>
                    <div class="avatar-initials"><?= get_avatar_initials($nav_u['username'] ?? 'U') ?></div>
                <?php endif; ?>
                <span style="font-weight: 600;"><?= htmlspecialchars($nav_u['username'] ?? '') ?></span>
            </a>
            <a href="logout.php" class="btn btn-secondary btn-sm" style="margin-left: 1rem;">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-secondary btn-sm">Login</a>
            <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
        <?php endif; ?>
    </div>
</nav>
