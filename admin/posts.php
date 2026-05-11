<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';  // این خط را اضافه کنید

Auth::requireLogin();

$db = Database::getInstance();

// Handle filters
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$query = "SELECT p.*, u.username as author_name 
          FROM posts p 
          LEFT JOIN users u ON p.created_by = u.id 
          WHERE 1=1";
$params = [];

if ($statusFilter) {
    $query .= " AND p.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter) {
    $query .= " AND p.category = ?";
    $params[] = $categoryFilter;
}

$query .= " ORDER BY p.created_at DESC";

$posts = $db->fetchAll($query, $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT name FROM categories ORDER BY name");

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $post = $db->fetchOne("SELECT featured_image FROM posts WHERE id = ?", [$_GET['delete']]);
    if ($post && $post['featured_image']) {
        Upload::deleteImage($post['featured_image']);
    }
    $db->delete('posts', 'id = ?', [$_GET['delete']]);
    header('Location: posts.php?deleted=1');
    exit;
}

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Blog Posts</h1>
        <a href="post-add.php" class="btn btn-primary">+ New Post</a>
    </div>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Post deleted successfully.</div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-auto">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo $categoryFilter === $cat['name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="posts.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Posts Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                        <tr>
                            <td><?php echo $post['id']; ?></td>
                            <td><?php echo htmlspecialchars($post['title']); ?></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($post['category'] ?? 'Uncategorized'); ?></span></td>
                            <td><?php echo getStatusBadge($post['status']); ?></td>
                            <td><?php echo number_format($post['view_count']); ?></td>
                            <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo formatDate($post['created_at']); ?></td>
                            <td>
                                <a href="post-edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="posts.php?delete=<?php echo $post['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this post?')">Delete</a>
                                <?php if ($post['status'] === 'published'): ?>
                                    <a href="<?php echo SITE_URL; ?>/blog/<?php echo $post['slug']; ?>" target="_blank" class="btn btn-sm btn-secondary">View</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($posts)): ?>
                        <tr><td colspan="8" class="text-center py-4">No posts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/admin-footer.php'; ?>