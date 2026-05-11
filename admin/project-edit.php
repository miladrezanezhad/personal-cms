<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireLogin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

$project = $db->fetchOne("SELECT * FROM github_projects WHERE id = ?", [$id]);

if (!$project) {
    header('Location: projects.php');
    exit;
}

$error = '';
$success = '';

$githubUsername = getSettings('github_username');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        if ($project['featured_image']) {
            Upload::deleteImage($project['featured_image']);
        }
        $db->delete('github_projects', 'id = ?', [$id]);
        header('Location: projects.php?deleted=1');
        exit;
    }
    
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $github_repo = trim($_POST['github_repo'] ?? '');
    $github_owner = trim($_POST['github_owner'] ?? $githubUsername);
    $custom_description = trim($_POST['custom_description'] ?? '');
    $install_command = trim($_POST['install_command'] ?? '');
    $featured_image = $project['featured_image'];
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $status = $_POST['status'] ?? 'draft';
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    
    // Handle image upload
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        if ($featured_image) {
            Upload::deleteImage($featured_image);
        }
        $upload = Upload::uploadImage($_FILES['featured_image'], 'projects', true);
        if ($upload['success']) {
            $featured_image = $upload['path'];
        }
    }
    
    if (empty($title)) {
        $error = 'Title is required';
    } elseif (empty($github_repo)) {
        $error = 'GitHub repository name is required';
    } else {
        if (empty($slug)) {
            $slug = slugify($title);
        }
        
        // Check unique slug
        $existing = $db->fetchOne("SELECT id FROM github_projects WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($existing) {
            $slug = generateSlug($title, 'github_projects', $id);
        }
        
        $data = [
            'title' => $title,
            'slug' => $slug,
            'custom_description' => $custom_description,
            'install_command' => $install_command,
            'github_repo' => $github_repo,
            'github_owner' => $github_owner,
            'featured_image' => $featured_image,
            'display_order' => $display_order,
            'is_featured' => $is_featured,
            'status' => $status,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description
        ];
        
        $db->update('github_projects', $data, 'id = :id', ['id' => $id]);
        $success = 'Project updated successfully.';
        
        // Refresh project data
        $project = $db->fetchOne("SELECT * FROM github_projects WHERE id = ?", [$id]);
    }
}

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Project: <?php echo htmlspecialchars($project['title']); ?></h1>
        <div>
            <a href="projects.php" class="btn btn-secondary">← Back to Projects</a>
            <a href="sync-manual.php?id=<?php echo $id; ?>" class="btn btn-info">Sync with GitHub</a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- GitHub Stats Card -->
    <?php if ($project['github_stars'] > 0): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>⭐ Stars:</strong> <?php echo number_format($project['github_stars']); ?>
                </div>
                <div class="col-md-3">
                    <strong>🍴 Forks:</strong> <?php echo number_format($project['github_forks']); ?>
                </div>
                <div class="col-md-3">
                    <strong>📝 Language:</strong> <?php echo htmlspecialchars($project['github_language'] ?? 'N/A'); ?>
                </div>
                <div class="col-md-3">
                    <strong>🔄 Last Synced:</strong> <?php echo $project['last_synced_at'] ? formatDate($project['last_synced_at']) : 'Never'; ?>
                </div>
            </div>
            <?php if ($project['latest_release_version']): ?>
            <div class="row mt-2">
                <div class="col-md-12">
                    <strong>🏷️ Latest Release:</strong> 
                    <a href="<?php echo htmlspecialchars($project['latest_release_url']); ?>" target="_blank">
                        <?php echo htmlspecialchars($project['latest_release_version']); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($project['slug']); ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">GitHub Owner</label>
                                    <input type="text" name="github_owner" class="form-control" value="<?php echo htmlspecialchars($project['github_owner']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">GitHub Repository Name</label>
                                    <input type="text" name="github_repo" class="form-control" value="<?php echo htmlspecialchars($project['github_repo']); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Custom Description</label>
                            <textarea name="custom_description" class="form-control" rows="4"><?php echo htmlspecialchars($project['custom_description']); ?></textarea>
                            <?php if ($project['github_description']): ?>
                            <small class="text-muted">GitHub description: <?php echo htmlspecialchars(substr($project['github_description'], 0, 100)); ?>...</small>
                            <?php endif; ?>
                        </div>
                        <?php if ($featured_image): ?>
                        <div class="mb-3">
                            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $featured_image; ?>" style="max-width: 200px;" class="img-thumbnail">
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Featured Image (new)</label>
                            <input type="file" name="featured_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                
                <!-- Install Command Section -->
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-terminal"></i> Package Manager Command</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Install / Run Command</label>
                            <select id="command_type" class="form-select mb-2" onchange="updateCommandPlaceholder()">
                                <option value="">Select package manager...</option>
                                <option value="pip">pip install</option>
                                <option value="npm">npm install</option>
                                <option value="npx">npx</option>
                                <option value="composer">composer require</option>
                                <option value="yarn">yarn add</option>
                                <option value="custom">Custom command</option>
                            </select>
                            <textarea name="install_command" id="install_command" class="form-control" rows="3" placeholder="Example: pip install package-name"><?php echo htmlspecialchars($project['install_command'] ?? ''); ?></textarea>
                            <small class="text-muted">Enter the command users can copy to install or run your package.</small>
                        </div>
                        <div class="alert alert-light border">
                            <strong>Preview:</strong>
                            <div id="command_preview" style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 13px;">
                                <?php 
                                $cmd = $project['install_command'] ?? '';
                                if ($cmd) {
                                    echo '<span style="color: #4ec9b0;">$</span> ' . nl2br(htmlspecialchars($cmd));
                                } else {
                                    echo '<span style="color: #6a9955;">Select a package manager above...</span>';
                                }
                                ?>
                            </div>
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
                            <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($project['meta_title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="3"><?php echo htmlspecialchars($project['meta_description']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="<?php echo $project['display_order']; ?>">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_featured" id="is_featured" class="form-check-input" <?php echo $project['is_featured'] ? 'checked' : ''; ?>>
                                <label for="is_featured" class="form-check-label">Featured on Homepage</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?php echo $project['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $project['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                            </select>
                        </div>
                        <button type="submit" name="save" class="btn btn-primary w-100 mb-2">Save Changes</button>
                        <button type="submit" name="delete" class="btn btn-danger w-100" onclick="return confirm('Delete this project permanently?')">Delete Project</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function updateCommandPlaceholder() {
    const type = document.getElementById('command_type').value;
    const textarea = document.getElementById('install_command');
    const preview = document.getElementById('command_preview');
    let command = '';
    
    switch(type) {
        case 'pip':
            command = 'pip install your-package-name';
            break;
        case 'npm':
            command = 'npm install your-package-name';
            break;
        case 'npx':
            command = 'npx create-your-app my-app';
            break;
        case 'composer':
            command = 'composer require vendor/package';
            break;
        case 'yarn':
            command = 'yarn add your-package-name';
            break;
        default:
            command = '';
    }
    
    if (command && type !== 'custom') {
        textarea.value = command;
        preview.innerHTML = '<span style="color: #4ec9b0;">$</span> ' + command.replace(/\n/g, '<br>');
    } else if (type === 'custom') {
        preview.innerHTML = '<span style="color: #6a9955;">Enter your custom command above...</span>';
    }
}

// Real-time preview
document.getElementById('install_command')?.addEventListener('input', function() {
    const preview = document.getElementById('command_preview');
    const cmd = this.value;
    if (cmd) {
        preview.innerHTML = '<span style="color: #4ec9b0;">$</span> ' + cmd.replace(/\n/g, '<br>');
    } else {
        preview.innerHTML = '<span style="color: #6a9955;">Enter a command...</span>';
    }
});
</script>

<?php require_once '../templates/admin-footer.php'; ?>