<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$page_title = 'Blog';
$page_description = 'Posts about development, tech, and everything in between';

$page = max(1, (int)($_GET['page'] ?? 1));
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$perPage = 6;
$offset = ($page - 1) * $perPage;

// Build query
$query = "SELECT * FROM posts WHERE status = 'published'";
$params = [];

if ($category) {
    $query .= " AND category = ?";
    $params[] = $category;
}

if ($search) {
    $query .= " AND (title LIKE ? OR content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY published_at DESC LIMIT $perPage OFFSET $offset";

try {
    $stmt = $db->query($query, $params);
    $posts = $stmt->fetchAll();
} catch (Exception $e) {
    $posts = [];
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM posts WHERE status = 'published'";
if ($category) $countQuery .= " AND category = '$category'";
if ($search) $countQuery .= " AND (title LIKE '%$search%' OR content LIKE '%$search%')";

try {
    $result = $db->fetchOne($countQuery);
    $total = $result ? $result['total'] : 0;
} catch (Exception $e) {
    $total = 0;
}
$totalPages = ceil($total / $perPage);

// Get categories with counts
try {
    $categories = $db->fetchAll("SELECT category, COUNT(*) as count FROM posts WHERE status = 'published' GROUP BY category ORDER BY count DESC");
} catch (Exception $e) {
    $categories = [];
}

// Get popular posts
try {
    $popularPosts = $db->fetchAll("SELECT title, slug, view_count FROM posts WHERE status = 'published' ORDER BY view_count DESC LIMIT 5");
} catch (Exception $e) {
    $popularPosts = [];
}

require_once 'includes/header.php';
?>

<div class="container">
    <div class="section">
        <h1>Blog</h1>
        <p style="font-size: 18px; color: var(--text-secondary); margin-bottom: 48px;">
            Posts about development, tech, and everything in between
        </p>
        
        <div class="two-column">
            <!-- Main Content -->
            <div>
                <?php if (count($posts) > 0): ?>
                    <?php foreach ($posts as $post): ?>
                    <div class="card" style="margin-bottom: 32px;">
                        <?php if ($post['featured_image']): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $post['featured_image']; ?>" 
                             alt="<?php echo htmlspecialchars($post['title']); ?>"
                             style="width: 100%; height: 200px; object-fit: cover; border-radius: var(--radius-md); margin-bottom: 20px;">
                        <?php endif; ?>
                        
                        <div style="display: flex; gap: 16px; font-size: 14px; color: var(--text-secondary); margin-bottom: 12px; flex-wrap: wrap;">
                            <span>📅 <?php echo date('M j, Y', strtotime($post['published_at'] ?? $post['created_at'])); ?></span>
                            <?php if ($post['category']): ?>
                            <span class="badge"><?php echo htmlspecialchars($post['category']); ?></span>
                            <?php endif; ?>
                            <span>👁️ <?php echo number_format($post['view_count']); ?> views</span>
                        </div>
                        
                        <h2 style="font-size: 24px; margin-bottom: 12px;">
                            <a href="/personal-cms/post.php?slug=<?php echo $post['slug']; ?>" style="color: inherit;">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h2>
                        
                        <p><?php echo htmlspecialchars(substr(strip_tags($post['excerpt'] ?: $post['content']), 0, 160)); ?>...</p>
                        
                        <a href="/personal-cms/post.php?slug=<?php echo $post['slug']; ?>" 
                           style="margin-top: 16px; display: inline-block; color: var(--accent);">
                            Read more →
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card" style="text-align: center; padding: 48px;">
                        <p>No posts found.</p>
                        <?php if ($search): ?>
                            <a href="/personal-cms/blog.php" class="btn btn-secondary">Clear Search</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">← Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page+1; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">Next →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Search Widget -->
                <div class="info-card">
                    <h4>Search</h4>
                    <form method="GET" action="/personal-cms/blog.php">
                        <input type="text" name="search" placeholder="Search articles..." 
                               value="<?php echo htmlspecialchars($search); ?>" style="width: 100%;">
                        <button type="submit" class="btn btn-primary" style="margin-top: 12px; width: 100%;">Search</button>
                    </form>
                </div>
                
                <!-- Categories -->
                <?php if (count($categories) > 0): ?>
                <div class="info-card">
                    <h4>Categories</h4>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($categories as $cat): ?>
                        <li style="margin-bottom: 8px;">
                            <a href="/personal-cms/blog.php?category=<?php echo urlencode($cat['category']); ?>" 
                               style="color: var(--text-secondary);">
                                <?php echo htmlspecialchars($cat['category']); ?> (<?php echo $cat['count']; ?>)
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Popular Posts -->
                <?php if (count($popularPosts) > 0): ?>
                <div class="info-card">
                    <h4>Popular Posts</h4>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($popularPosts as $popular): ?>
                        <li style="margin-bottom: 12px;">
                            <a href="/personal-cms/post.php?slug=<?php echo $popular['slug']; ?>" 
                               style="color: var(--text-secondary);">
                                <?php echo htmlspecialchars($popular['title']); ?>
                                <span style="font-size: 12px; display: block; color: var(--text-tertiary);">
                                    👁️ <?php echo number_format($popular['view_count']); ?> views
                                </span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>