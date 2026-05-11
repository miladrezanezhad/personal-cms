<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireLogin();

$db = Database::getInstance();

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $slug = slugify($name);
    $description = trim($_POST['description']);
    
    $existing = $db->fetchOne("SELECT id FROM categories WHERE name = ?", [$name]);
    if (!$existing) {
        $db->insert('categories', [
            'name' => $name,
            'slug' => $slug,
            'description' => $description
        ]);
    }
    header('Location: categories.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $db->delete('categories', 'id = ?', [$_GET['delete']]);
    header('Location: categories.php');
    exit;
}

// Update post counts
$db->query("UPDATE categories SET post_count = (SELECT COUNT(*) FROM posts WHERE posts.category = categories.name AND posts.status = 'published')");

$categories = $db->fetchAll("SELECT * FROM categories ORDER BY post_count DESC");

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Categories</h1>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add New Category</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" name="add" class="btn btn-primary w-100">Add Category</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Existing Categories</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr><th>Name</th><th>Slug</th><th>Posts</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                    <td><?php echo $cat['post_count']; ?></td>
                                    <td>
                                        <a href="categories.php?delete=<?php echo $cat['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this category?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/admin-footer.php'; ?>