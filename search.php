<?php
require_once 'config.php';
require_once 'helpers.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$results = [];
if (!empty($q)) {
    $search = "%{$q}%";
    $stmt = $pdo->prepare("
        SELECT 'topic' as type, t.id as link_id, t.title as content, t.created_at, c.name as category_name, c.icon as category_icon, u.username
        FROM topics t
        JOIN categories c ON t.category_id = c.id
        JOIN users u ON t.user_id = u.id
        WHERE t.title LIKE ? AND t.is_deleted = 0
        UNION
        SELECT 'post' as type, p.topic_id as link_id, p.content as content, p.created_at, c.name as category_name, c.icon as category_icon, u.username
        FROM posts p
        JOIN topics t ON p.topic_id = t.id
        JOIN categories c ON t.category_id = c.id
        JOIN users u ON p.user_id = u.id
        WHERE p.content LIKE ? AND p.is_deleted = 0 AND t.is_deleted = 0
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$search, $search]);
    $results = $stmt->fetchAll();
}

function highlight_keyword($text, $keyword) {
    if (empty($keyword)) return $text;
    $text = htmlspecialchars($text);
    $keyword = preg_quote($keyword, '/');
    return preg_replace('/(' . $keyword . ')/i', '<mark style="background-color: rgba(255,255,0,0.4); font-weight: bold; border-radius:3px;">$1</mark>', $text);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - ForumHub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container" style="flex-direction: column;">
        
        <div class="card" style="margin-bottom: 2rem; background: var(--primary); color: white; border: none;">
            <h2 style="color: white; margin-bottom: 1rem;"><i class="fas fa-search"></i> Search</h2>
            <form action="search.php" method="GET" style="display: flex; gap: 1rem;">
                <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Search for topics, discussions..." required style="flex-grow: 1; border: none; font-size: 1.1rem; padding: 1rem;">
                <button type="submit" class="btn btn-secondary" style="background: white; color: var(--primary); font-weight: bold;">Search</button>
            </form>
        </div>

        <?php if (!empty($q)): ?>
            <h3 style="margin-bottom: 1rem;">Results for "<?= htmlspecialchars($q) ?>" (<?= count($results) ?>)</h3>
            
            <?php if (empty($results)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-search" style="font-size: 3rem; color: var(--muted); margin-bottom: 1rem;"></i>
                    <p style="color: var(--muted);">No results found. Try using different keywords.</p>
                </div>
            <?php else: ?>
                <?php foreach ($results as $res): ?>
                    <a href="topic.php?id=<?= $res['link_id'] ?>" class="card topic-card" style="flex-direction: column;">
                        <div style="display: flex; justify-content: space-between; width: 100%; margin-bottom: 0.5rem;">
                            <div class="badge badge-primary">
                                <i class="<?= htmlspecialchars($res['category_icon']) ?>"></i> <?= htmlspecialchars($res['category_name']) ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--muted);">
                                <?= $res['type'] === 'topic' ? 'Topic Title Match' : 'Post Content Match' ?> 
                                • <?= time_ago($res['created_at']) ?>
                            </div>
                        </div>
                        
                        <div style="font-size: 1.1rem; color: var(--text); line-height: 1.5;">
                            <?php 
                                $content = $res['content'];
                                if ($res['type'] === 'post') {
                                    $content = mb_substr(strip_tags($content), 0, 250) . (mb_strlen($content) > 250 ? '...' : '');
                                }
                                echo highlight_keyword($content, $q);
                            ?>
                        </div>
                        
                        <div style="font-size: 0.875rem; color: var(--muted); margin-top: 1rem;">
                            <i class="fas fa-user"></i> By <?= htmlspecialchars($res['username']) ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>

    </div>
    <script src="scripts.js"></script>
</body>
</html>
