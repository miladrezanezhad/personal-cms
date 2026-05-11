<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireLogin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

$post = $db->fetchOne("SELECT * FROM posts WHERE id = ?", [$id]);

if (!$post) {
    header('Location: posts.php');
    exit;
}

$error = '';
$success = '';

// دریافت لیست دسته‌بندی‌ها
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        if ($post['featured_image']) {
            Upload::deleteImage($post['featured_image']);
        }
        $db->delete('posts', 'id = ?', [$id]);
        header('Location: posts.php?deleted=1');
        exit;
    }
    
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
    
    $featured_image = $post['featured_image'];
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        if ($featured_image) {
            Upload::deleteImage($featured_image);
        }
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
        $existing = $db->fetchOne("SELECT id FROM posts WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($existing) {
            $slug = generateSlug($title, 'posts', $id);
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
            'status' => $status
        ];
        
        if ($status === 'published' && $post['status'] !== 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        
        $db->update('posts', $data, 'id = :id', ['id' => $id]);
        $success = 'Post updated successfully.';
        
        // Refresh post data
        $post = $db->fetchOne("SELECT * FROM posts WHERE id = ?", [$id]);
    }
}

// Parse tags for display
$tagsString = '';
if ($post['tags']) {
    $tagsArray = json_decode($post['tags'], true);
    $tagsString = is_array($tagsArray) ? implode(', ', $tagsArray) : '';
}

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Post: <?php echo htmlspecialchars($post['title']); ?></h1>
        <a href="posts.php" class="btn btn-secondary">← Back to Posts</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" id="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($post['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" id="slug" class="form-control" 
                                   value="<?php echo htmlspecialchars($post['slug']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Excerpt</label>
                            <textarea name="excerpt" class="form-control" rows="3"><?php 
                                echo htmlspecialchars($post['excerpt']); 
                            ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea name="content" id="content" class="form-control" rows="15"><?php 
                                echo htmlspecialchars($post['content']); 
                            ?></textarea>
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
                            <input type="text" name="meta_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($post['meta_title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="3"><?php 
                                echo htmlspecialchars($post['meta_description']); 
                            ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">OG Title</label>
                            <input type="text" name="og_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($post['og_title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">OG Description</label>
                            <textarea name="og_description" class="form-control" rows="3"><?php 
                                echo htmlspecialchars($post['og_description']); 
                            ?></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="no_index" id="no_index" class="form-check-input" 
                                   <?php echo $post['no_index'] ? 'checked' : ''; ?>>
                            <label for="no_index" class="form-check-label">No Index</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <?php if ($post['featured_image']): ?>
                        <div class="mb-3 text-center">
                            <img src="<?php echo SITE_URL . '/uploads/' . $post['featured_image']; ?>" 
                                 style="max-width: 100%; border-radius: 8px;">
                            <br><small class="text-muted">Current image</small>
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Featured Image (new)</label>
                            <input type="file" name="featured_image" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">Uncategorized</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                    <?php echo $post['category'] === $cat['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tags</label>
                            <input type="text" name="tags" class="form-control" 
                                   value="<?php echo htmlspecialchars($tagsString); ?>"
                                   placeholder="php, laravel, javascript">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?php echo $post['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $post['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                            </select>
                        </div>
                        <button type="submit" name="save" class="btn btn-primary w-100 mb-2">Save Changes</button>
                        <button type="submit" name="delete" class="btn btn-danger w-100" 
                                onclick="return confirm('Delete this post permanently?')">Delete Post</button>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <small class="text-muted">
                            <strong>Created:</strong> <?php echo formatDate($post['created_at']); ?><br>
                            <strong>Updated:</strong> <?php echo formatDate($post['updated_at']); ?><br>
                            <strong>Views:</strong> <?php echo number_format($post['view_count']); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
    ClassicEditor.create(document.querySelector('#content')).catch(error => console.error(error));
    
    document.getElementById('title').addEventListener('input', function() {
        let slug = this.value.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        let slugInput = document.getElementById('slug');
        if (slugInput.value === '' || slugInput.value === slug) {
            slugInput.value = slug;
        }
    });
</script>

<?php require_once '../templates/admin-footer.php'; ?>