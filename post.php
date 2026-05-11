<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: /personal-cms/blog.php');
    exit;
}

// Increment view count
try {
    $db->query("UPDATE posts SET view_count = view_count + 1 WHERE slug = ?", [$slug]);
} catch (Exception $e) {
    // Ignore view count error
}

// Fetch post
try {
    $stmt = $db->query("SELECT * FROM posts WHERE slug = ? AND status = 'published'", [$slug]);
    $post = $stmt->fetch();
} catch (Exception $e) {
    $post = null;
}

if (!$post) {
    http_response_code(404);
    require_once '404.php';
    exit;
}

$page_title = $post['title'];
$page_description = substr(strip_tags($post['excerpt'] ?: $post['content']), 0, 160);

// Get related posts (same category)
$relatedPosts = [];
if ($post['category']) {
    try {
        $stmt = $db->query("SELECT * FROM posts WHERE category = ? AND id != ? AND status = 'published' LIMIT 3", 
            [$post['category'], $post['id']]);
        $relatedPosts = $stmt->fetchAll();
    } catch (Exception $e) {
        $relatedPosts = [];
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <div class="section">
        <!-- Post Header -->
        <div style="text-align: center; margin-bottom: 48px;">
            <div style="display: flex; gap: 16px; justify-content: center; margin-bottom: 24px; flex-wrap: wrap;">
                <?php if ($post['category']): ?>
                <span class="badge badge-accent"><?php echo htmlspecialchars($post['category']); ?></span>
                <?php endif; ?>
                <span>📅 <?php echo date('F j, Y', strtotime($post['published_at'] ?? $post['created_at'])); ?></span>
                <span>👁️ <?php echo number_format($post['view_count']); ?> views</span>
            </div>
            <h1 style="font-size: 48px; margin-bottom: 24px;"><?php echo htmlspecialchars($post['title']); ?></h1>
        </div>
        
        <!-- Featured Image -->
        <?php if ($post['featured_image']): ?>
        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $post['featured_image']; ?>" 
             alt="<?php echo htmlspecialchars($post['title']); ?>"
             style="width: 100%; max-height: 500px; object-fit: cover; border-radius: var(--radius-lg); margin-bottom: 48px;">
        <?php endif; ?>
        
        <!-- Post Content -->
        <div class="post-content" style="max-width: 800px; margin: 0 auto; font-size: 18px; line-height: 1.8;">
            <?php echo $post['content']; ?>
        </div>
        
        <!-- Tags -->
        <?php if ($post['tags']): 
            $tags = json_decode($post['tags'], true);
            if (is_array($tags) && count($tags) > 0):
        ?>
        <div style="margin-top: 48px; padding-top: 24px; border-top: 1px solid var(--border-color); max-width: 800px; margin-left: auto; margin-right: auto;">
            <strong>Tags:</strong>
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;">
                <?php foreach ($tags as $tag): ?>
                <a href="/personal-cms/blog.php?tag=<?php echo urlencode(trim($tag)); ?>" class="badge" style="text-decoration: none;">
                    #<?php echo htmlspecialchars(trim($tag)); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; endif; ?>
        
        <!-- Related Posts -->
        <?php if (count($relatedPosts) > 0): ?>
        <div class="section" style="margin-top: 60px;">
            <h3>Related Posts</h3>
            <div class="grid-3">
                <?php foreach ($relatedPosts as $related): ?>
                <div class="card">
                    <?php if ($related['featured_image']): ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $related['featured_image']; ?>" 
                         style="width: 100%; height: 150px; object-fit: cover; border-radius: var(--radius-md); margin-bottom: 15px;">
                    <?php endif; ?>
                    <div style="font-size: 14px; color: var(--text-secondary); margin-bottom: 8px;">
                        <?php echo date('M j, Y', strtotime($related['created_at'])); ?>
                    </div>
                    <h4><?php echo htmlspecialchars($related['title']); ?></h4>
                    <a href="/personal-cms/post.php?slug=<?php echo $related['slug']; ?>" style="color: var(--accent);">Read more →</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>