<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireLogin();

$db = Database::getInstance();
$error = '';
$success = '';

// دریافت لیست دسته‌بندی‌ها
$categories = [];
try {
    $categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");
} catch (Exception $e) {
    $categories = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $category = trim($_POST['category'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $og_title = trim($_POST['og_title'] ?? '');
    $og_description = trim($_POST['og_description'] ?? '');
    $no_index = isset($_POST['no_index']) ? 1 : 0;
    
    $featured_image = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $upload = Upload::uploadImage($_FILES['featured_image'], 'posts', true);
        if ($upload['success']) {
            $featured_image = $upload['path'];
        } else {
            $error = $upload['error'];
        }
    }
    
    if (empty($title)) {
        $error = 'Title is required';
    } else {
        if (empty($slug)) {
            $slug = slugify($title);
        }
        
        // Check unique slug
        $existing = $db->fetchOne("SELECT id FROM posts WHERE slug = ?", [$slug]);
        if ($existing) {
            $slug = generateSlug($title, 'posts');
        }
        
        $tagsJson = !empty($tags) ? json_encode(array_map('trim', explode(',', $tags))) : null;
        
        $data = [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'content' => $content,
            'featured_image' => $featured_image,
            'category' => $category,
            'tags' => $tagsJson,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'og_title' => $og_title,
            'og_description' => $og_description,
            'no_index' => $no_index,
            'status' => $status,
            'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
            'created_by' => $_SESSION['user_id']
        ];
        
        try {
            $db->insert('posts', $data);
            $success = 'Post created successfully.';
            header("refresh:2;url=posts.php");
        } catch (Exception $e) {
            $error = 'Failed to save post: ' . $e->getMessage();
        }
    }
}

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Add New Post</h1>
        <a href="posts.php" class="btn btn-secondary">← Back to Posts</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> Redirecting...</div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" id="slug" class="form-control" placeholder="auto-generated">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Excerpt</label>
                            <textarea name="excerpt" class="form-control" rows="3" placeholder="Short summary of the post"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea name="content" id="content" class="form-control" rows="15"></textarea>
                            <small class="text-muted">HTML is supported.</small>
                        </div>
                    </div>
                </div>
                
                <!-- SEO Section -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">SEO Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" maxlength="60">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="3" maxlength="160"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">OG Title</label>
                            <input type="text" name="og_title" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">OG Description</label>
                            <textarea name="og_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="no_index" id="no_index" class="form-check-input">
                            <label for="no_index" class="form-check-label">No Index (hide from search engines)</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Featured Image</label>
                            <input type="file" name="featured_image" class="form-control" accept="image/*">
                            <small class="text-muted">Recommended size: 1200x630px</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">Uncategorized</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tags</label>
                            <input type="text" name="tags" class="form-control" placeholder="php, laravel, javascript">
                            <small class="text-muted">Comma separated</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save Post</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
    ClassicEditor.create(document.querySelector('#content')).catch(error => console.error(error));
    
    // Auto-generate slug from title
    document.getElementById('title').addEventListener('input', function() {
        let slug = this.value.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        if (document.getElementById('slug').value === '') {
            document.getElementById('slug').value = slug;
        }
    });
</script>

<?php require_once '../templates/admin-footer.php'; ?>