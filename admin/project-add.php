<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireLogin();

$db = Database::getInstance();
$error = '';
$success = '';

$githubUsername = getSettings('github_username');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $github_repo = trim($_POST['github_repo'] ?? '');
    $github_owner = trim($_POST['github_owner'] ?? $githubUsername);
    $custom_description = trim($_POST['custom_description'] ?? '');
    $install_command = trim($_POST['install_command'] ?? '');
    $featured_image = '';
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $status = $_POST['status'] ?? 'draft';
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    
    // Handle image upload
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $upload = Upload::uploadImage($_FILES['featured_image'], 'projects', true);
        if ($upload['success']) {
            $featured_image = $upload['path'];
        } else {
            $error = $upload['error'];
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
        $existing = $db->fetchOne("SELECT id FROM github_projects WHERE slug = ?", [$slug]);
        if ($existing) {
            $slug = generateSlug($title, 'github_projects');
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
            'meta_description' => $meta_description,
            'sync_status' => 'pending'
        ];
        
        $db->insert('github_projects', $data);
        $success = 'Project created successfully.';
        
        header("refresh:2;url=projects.php");
    }
}

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Add New Project</h1>
        <a href="projects.php" class="btn btn-secondary">← Back to Projects</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?> Redirecting...</div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" placeholder="auto-generated">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">GitHub Owner *</label>
                                    <input type="text" name="github_owner" class="form-control" value="<?php echo htmlspecialchars($githubUsername); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">GitHub Repository Name *</label>
                                    <input type="text" name="github_repo" class="form-control" placeholder="e.g., laravel" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Custom Description</label>
                            <textarea name="custom_description" class="form-control" rows="4" placeholder="Override GitHub description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Featured Image</label>
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
                            <textarea name="install_command" id="install_command" class="form-control" rows="3" placeholder="Example: pip install package-name&#10;or&#10;npx create-react-app my-app"></textarea>
                            <small class="text-muted">Enter the command users can copy to install or run your package. Supports multi-line commands.</small>
                        </div>
                        <div class="alert alert-light border">
                            <strong>Preview:</strong>
                            <div id="command_preview" style="background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 13px;">
                                <span style="color: #6a9955;">Select a package manager above...</span>
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
                            <input type="text" name="meta_title" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0">
                            <small class="text-muted">Lower numbers appear first.</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_featured" id="is_featured" class="form-check-input">
                                <label for="is_featured" class="form-check-label">Featured on Homepage</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save Project</button>
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