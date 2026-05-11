<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';  // این خط را اضافه کنید

Auth::requireLogin();

$db = Database::getInstance();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $project = $db->fetchOne("SELECT featured_image FROM github_projects WHERE id = ?", [$_GET['delete']]);
    if ($project && $project['featured_image']) {
        Upload::deleteImage($project['featured_image']);
    }
    $db->delete('github_projects', 'id = ?', [$_GET['delete']]);
    header('Location: projects.php?deleted=1');
    exit;
}

$projects = $db->fetchAll("SELECT * FROM github_projects ORDER BY display_order ASC, github_stars DESC");

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">GitHub Projects</h1>
        <div>
            <a href="sync-manual.php" class="btn btn-warning me-2">Sync All</a>
            <a href="project-add.php" class="btn btn-primary">+ New Project</a>
            <a href="sync-manual.php?release=<?php echo $project['id']; ?>" class="btn btn-sm btn-info" title="Sync Latest Release">
    <i class="fas fa-tag"></i> Sync Release
</a>
        </div>
    </div>
    
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Project deleted successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['synced'])): ?>
        <div class="alert alert-success">Projects synced successfully.</div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Title</th>
                            <th>Repository</th>
                            <th>⭐ Stars</th>
                            <th>Language</th>
                            <th>Sync Status</th>
                            <th>Last Synced</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?php echo $project['display_order']; ?></td>
                            <td><?php echo htmlspecialchars($project['title']); ?></td>
                            <td><code><?php echo htmlspecialchars($project['github_owner'] . '/' . $project['github_repo']); ?></code></td>
                            <td><?php echo number_format($project['github_stars']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($project['github_language'] ?? 'N/A'); ?></span></td>
                            <td><?php echo getSyncStatusBadge($project['sync_status']); ?></td>
                            <td><?php echo $project['last_synced_at'] ? formatDate($project['last_synced_at'], 'Y-m-d H:i') : 'Never'; ?></td>
                            <td>
                                <a href="project-edit.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="sync-manual.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-info">Sync</a>
                                <a href="projects.php?delete=<?php echo $project['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this project?')">Delete</a>
                                <a href="<?php echo SITE_URL; ?>/project/<?php echo $project['slug']; ?>" target="_blank" class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projects)): ?>
                        <tr><td colspan="8" class="text-center py-4">No projects yet. <a href="project-add.php">Add your first project</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/admin-footer.php'; ?>