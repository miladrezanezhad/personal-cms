<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireLogin();

$db = Database::getInstance();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $db->delete('pages', 'id = ?', [$_GET['delete']]);
    header('Location: pages.php?deleted=1');
    exit;
}

// Get all pages
$pages = $db->fetchAll("SELECT * FROM pages ORDER BY created_at DESC");

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Pages</h1>
        <a href="page-add.php" class="btn btn-primary">+ Add New Page</a>
    </div>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Page deleted successfully.</div>
    <?php endif; ?>
    
    <?php if (empty($pages)): ?>
        <div class="alert alert-info">
            No pages found. <a href="page-add.php">Create your first page</a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Title</th>
                                <th>Slug</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 100px;">Views</th>
                                <th style="width: 130px;">Updated</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><?php echo $page['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($page['title']); ?></strong>
                                </td>
                                <td>
                                    <code>/page/<?php echo htmlspecialchars($page['slug']); ?></code>
                                </td>
                                <td><?php echo getStatusBadge($page['status']); ?></td>
                                <td><?php echo number_format($page['view_count']); ?></td>
                                <td><?php echo formatDate($page['updated_at']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="page-edit.php?id=<?php echo $page['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="pages.php?delete=<?php echo $page['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this page permanently?')"
                                           title="Delete">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                        <?php if ($page['status'] === 'published'): ?>
                                        <a href="<?php echo SITE_URL; ?>/page.php?slug=<?php echo $page['slug']; ?>" 
                                           target="_blank" class="btn btn-sm btn-secondary" title="View">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .btn-group .btn {
        margin-right: 3px;
    }
    .btn-group .btn:last-child {
        margin-right: 0;
    }
    code {
        background: #f4f4f4;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
    }
</style>

<?php require_once '../templates/admin-footer.php'; ?>