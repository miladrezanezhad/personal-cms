<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';  // این خط را اضافه کنید

Auth::requireLogin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

$page = $db->fetchOne("SELECT * FROM pages WHERE id = ?", [$id]);

if (!$page) {
    header('Location: pages.php');
    exit;
}

$error = '';
$success = '';

$templates = ['default' => 'Default', 'full-width' => 'Full Width'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $db->delete('pages', 'id = ?', [$id]);
        header('Location: pages.php?deleted=1');
        exit;
    }
    
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $template = $_POST['template'] ?? 'default';
    $status = $_POST['status'] ?? 'draft';
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $meta_keywords = trim($_POST['meta_keywords'] ?? '');
    $og_title = trim($_POST['og_title'] ?? '');
    $og_description = trim($_POST['og_description'] ?? '');
    $og_image = $page['og_image'];
    $canonical_url = trim($_POST['canonical_url'] ?? '');
    $no_index = isset($_POST['no_index']) ? 1 : 0;
    $no_follow = isset($_POST['no_follow']) ? 1 : 0;
    
    // Handle image upload
    if (isset($_FILES['og_image_file']) && $_FILES['og_image_file']['error'] === UPLOAD_ERR_OK) {
        // Delete old image
        if ($og_image) {
            Upload::deleteImage($og_image);
        }
        $upload = Upload::uploadImage($_FILES['og_image_file'], 'pages', false);
        if ($upload['success']) {
            $og_image = $upload['path'];
        }
    }
    
    if (empty($title)) {
        $error = 'Title is required';
    } else {
        if (empty($slug)) {
            $slug = slugify($title);
        }
        
        // Check unique slug
        $existing = $db->fetchOne("SELECT id FROM pages WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($existing) {
            $slug = generateSlug($title, 'pages', $id);
        }
        
        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'template' => $template,
            'status' => $status,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'og_title' => $og_title,
            'og_description' => $og_description,
            'og_image' => $og_image,
            'canonical_url' => $canonical_url,
            'no_index' => $no_index,
            'no_follow' => $no_follow
        ];
        
        $db->update('pages', $data, 'id = :id', ['id' => $id]);
        $success = 'Page updated successfully.';
        
        // Refresh page data
        $page = $db->fetchOne("SELECT * FROM pages WHERE id = ?", [$id]);
    }
}

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Page: <?php echo htmlspecialchars($page['title']); ?></h1>
        <div>
            <a href="pages.php" class="btn btn-secondary">← Back to Pages</a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($page['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($page['slug']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea name="content" id="content" class="form-control" rows="15"><?php echo htmlspecialchars($page['content']); ?></textarea>
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
                            <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($page['meta_title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="3"><?php echo htmlspecialchars($page['meta_description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Keywords</label>
                            <input type="text" name="meta_keywords" class="form-control" value="<?php echo htmlspecialchars($page['meta_keywords']); ?>">
                        </div>
                        <hr>
                        <h6>Open Graph (Social Media)</h6>
                        <?php if ($page['og_image']): ?>
                        <div class="mb-3">
                            <img src="<?php echo SITE_URL . '/uploads/' . $page['og_image']; ?>" style="max-width: 200px;" class="img-thumbnail">
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">OG Image (new)</label>
                            <input type="file" name="og_image_file" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">OG Title</label>
                            <input type="text" name="og_title" class="form-control" value="<?php echo htmlspecialchars($page['og_title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">OG Description</label>
                            <textarea name="og_description" class="form-control" rows="3"><?php echo htmlspecialchars($page['og_description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Canonical URL</label>
                            <input type="url" name="canonical_url" class="form-control" value="<?php echo htmlspecialchars($page['canonical_url']); ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="no_index" id="no_index" class="form-check-input" <?php echo $page['no_index'] ? 'checked' : ''; ?>>
                                    <label for="no_index" class="form-check-label">No Index</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="no_follow" id="no_follow" class="form-check-input" <?php echo $page['no_follow'] ? 'checked' : ''; ?>>
                                    <label for="no_follow" class="form-check-label">No Follow</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Template</label>
                            <select name="template" class="form-select">
                                <?php foreach ($templates as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $page['template'] === $key ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?php echo $page['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $page['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                            </select>
                        </div>
                        <button type="submit" name="save" class="btn btn-primary w-100 mb-2">Save Changes</button>
                        <button type="submit" name="delete" class="btn btn-danger w-100" onclick="return confirm('Delete this page permanently?')">Delete Page</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
    ClassicEditor.create(document.querySelector('#content')).catch(error => console.error(error));
</script>

<?php require_once '../templates/admin-footer.php'; ?>