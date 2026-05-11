<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireLogin();

$db = Database::getInstance();

// Get stats
$totalProjects = 0;
$totalPosts = 0;
$totalPages = 0;
$totalViews = 0;
$lastSync = null;

try {
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM github_projects");
    $totalProjects = $result ? $result['count'] : 0;
    
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM posts WHERE status = 'published'");
    $totalPosts = $result ? $result['count'] : 0;
    
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM pages WHERE status = 'published'");
    $totalPages = $result ? $result['count'] : 0;
    
    // مجموع بازدیدها از پست‌ها و صفحات
    $postsViews = $db->fetchOne("SELECT SUM(view_count) as total FROM posts")['total'] ?? 0;
    $pagesViews = $db->fetchOne("SELECT SUM(view_count) as total FROM pages")['total'] ?? 0;
    $totalViews = $postsViews + $pagesViews;
    
    $result = $db->fetchOne("SELECT MAX(last_synced_at) as last_sync FROM github_projects");
    $lastSync = $result ? $result['last_sync'] : null;
} catch (Exception $e) {
    // Ignore error
}

// Get recent posts
$recentPosts = [];
try {
    $recentPosts = $db->fetchAll(
        "SELECT id, title, slug, status, published_at, created_at, view_count FROM posts ORDER BY created_at DESC LIMIT 5"
    );
} catch (Exception $e) {
    $recentPosts = [];
}

// Get recent pages
$recentPages = [];
try {
    $recentPages = $db->fetchAll(
        "SELECT id, title, slug, status, view_count FROM pages ORDER BY updated_at DESC LIMIT 5"
    );
} catch (Exception $e) {
    $recentPages = [];
}

// Get popular posts (most viewed)
$popularPosts = [];
try {
    $popularPosts = $db->fetchAll(
        "SELECT id, title, slug, view_count FROM posts WHERE status = 'published' ORDER BY view_count DESC LIMIT 5"
    );
} catch (Exception $e) {
    $popularPosts = [];
}

// Get server info
$phpVersion = phpversion();
$mysqlVersion = $db->getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION);

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="cache-clear.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-trash"></i> Clear Cache
                </a>
                <a href="backup.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-database"></i> Backup
                </a>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards - Row 1 -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Projects</h6>
                            <h2 class="mb-0"><?php echo $totalProjects; ?></h2>
                        </div>
                        <div class="display-4">📦</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Published Posts</h6>
                            <h2 class="mb-0"><?php echo $totalPosts; ?></h2>
                        </div>
                        <div class="display-4">📝</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Published Pages</h6>
                            <h2 class="mb-0"><?php echo $totalPages; ?></h2>
                        </div>
                        <div class="display-4">📄</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Views</h6>
                            <h2 class="mb-0"><?php echo number_format($totalViews); ?></h2>
                        </div>
                        <div class="display-4">👁️</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards - Row 2 -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">PHP Version</h6>
                            <h4 class="mb-0"><?php echo $phpVersion; ?></h4>
                        </div>
                        <div class="display-4">🐘</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">MySQL Version</h6>
                            <h4 class="mb-0"><?php echo substr($mysqlVersion, 0, 10); ?></h4>
                        </div>
                        <div class="display-4">🗄️</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Last GitHub Sync</h6>
                            <h6 class="mb-0"><?php echo $lastSync ? formatDate($lastSync, 'M j, Y g:i A') : 'Never'; ?></h6>
                        </div>
                        <div class="display-4">🔄</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions Row -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0"><i class="fab fa-github"></i> GitHub Sync</h5>
                            <p class="text-muted mb-0">Sync all projects with GitHub API to update stars and forks</p>
                        </div>
                        <a href="sync-manual.php" class="btn btn-primary">Sync All Projects →</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0"><i class="fas fa-file-alt"></i> Quick Actions</h5>
                            <p class="text-muted mb-0">Create new content or manage existing</p>
                        </div>
                        <div>
                            <a href="post-add.php" class="btn btn-sm btn-success">+ New Post</a>
                            <a href="page-add.php" class="btn btn-sm btn-info">+ New Page</a>
                            <a href="project-add.php" class="btn btn-sm btn-primary">+ New Project</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Posts -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-newspaper"></i> Recent Blog Posts</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Views</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recentPosts) > 0): ?>
                                    <?php foreach ($recentPosts as $post): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($post['title']); ?></td>
                                        <td><?php echo getStatusBadge($post['status']); ?></td>
                                        <td><i class="fas fa-eye"></i> <?php echo number_format($post['view_count']); ?></td>
                                        <td><?php echo formatDate($post['created_at']); ?></td>
                                        <td><a href="post-edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center">No posts yet. <a href="post-add.php">Create your first post</a></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Pages -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> Recent Pages</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Views</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recentPages) > 0): ?>
                                    <?php foreach ($recentPages as $page): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($page['title']); ?></td>
                                        <td><?php echo getStatusBadge($page['status']); ?></td>
                                        <td><i class="fas fa-eye"></i> <?php echo number_format($page['view_count']); ?></td>
                                        <td><a href="page-edit.php?id=<?php echo $page['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">No pages yet. <a href="page-add.php">Create your first page</a></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Popular Posts -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-fire"></i> Most Viewed Posts</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Views</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($popularPosts) > 0): ?>
                                    <?php foreach ($popularPosts as $post): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($post['title']); ?></td>
                                        <td><i class="fas fa-eye"></i> <strong><?php echo number_format($post['view_count']); ?></strong></td>
                                        <td><a href="post-edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center">No posts yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Info -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-server"></i> System Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th style="width: 40%;">Server Software:</th>
                            <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                        </tr>
                        <tr>
                            <th>PHP Version:</th>
                            <td><?php echo $phpVersion; ?></td>
                        </tr>
                        <tr>
                            <th>MySQL Version:</th>
                            <td><?php echo $mysqlVersion; ?></td>
                        </tr>
                        <tr>
                            <th>Upload Max Size:</th>
                            <td><?php echo ini_get('upload_max_filesize'); ?></td>
                        </tr>
                        <tr>
                            <th>Post Max Size:</th>
                            <td><?php echo ini_get('post_max_size'); ?></td>
                        </tr>
                        <tr>
                            <th>Memory Limit:</th>
                            <td><?php echo ini_get('memory_limit'); ?></td>
                        </tr>
                        <tr>
                            <th>Max Execution Time:</th>
                            <td><?php echo ini_get('max_execution_time'); ?> seconds</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/admin-footer.php'; ?>