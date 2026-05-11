<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireAdmin();

$db = Database::getInstance();
$github = new GitHubAPI();
$message = '';
$details = [];

// Sync single release
if (isset($_GET['release'])) {
    $id = (int)$_GET['release'];
    $release = $github->syncLatestRelease($id);
    if ($release && !empty($release['version'])) {
        $message = "Release synced: " . $release['version'];
    } else {
        $message = "No release found or error syncing.";
    }
    header("Location: projects.php?release_synced=1");
    exit;
}

$singleId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($singleId) {
    // Sync single project
    $project = $db->fetchOne("SELECT * FROM github_projects WHERE id = ?", [$singleId]);
    if ($project) {
        $result = $github->syncProject($singleId);
        $message = $result['message'];
        $details[] = "Project: " . $project['title'] . " - " . $result['message'];
        if (isset($result['stars'])) {
            $details[] = "⭐ Stars: " . number_format($result['stars']);
        }
        // Also sync release
        $release = $github->syncLatestRelease($singleId);
        if ($release && !empty($release['version'])) {
            $details[] = "🏷️ Latest Release: " . $release['version'];
        }
    } else {
        $message = "Project not found!";
    }
    header("Location: projects.php?synced=1");
    exit;
} else {
    // Sync all published projects
    $projects = $db->fetchAll("SELECT * FROM github_projects WHERE status = 'published'");
    
    if (count($projects) == 0) {
        $message = "No published projects found to sync!";
        $details[] = "Please add a project from the Projects page.";
    } else {
        $details[] = "Found " . count($projects) . " project(s) to sync.";
        
        $success = 0;
        $failed = 0;
        
        foreach ($projects as $project) {
            $details[] = "----------------------------------------";
            $details[] = "Syncing: " . $project['github_owner'] . "/" . $project['github_repo'];
            
            $result = $github->syncProject($project['id']);
            
            if ($result['success']) {
                $success++;
                $details[] = "✅ SUCCESS: " . $result['message'];
                $details[] = "   ⭐ Stars: " . number_format($result['stars']);
                
                // Get release info
                $release = $github->syncLatestRelease($project['id']);
                if ($release && !empty($release['version'])) {
                    $details[] = "   🏷️ Release: " . $release['version'];
                }
            } else {
                $failed++;
                $details[] = "❌ FAILED: " . $result['message'];
            }
            
            // Avoid rate limiting
            usleep(500000);
        }
        
        $message = "Sync completed: {$success} succeeded, {$failed} failed";
        $details[] = "----------------------------------------";
        $details[] = "✅ Successful: $success";
        $details[] = "❌ Failed: $failed";
    }
    
    require_once '../templates/admin-header.php';
    ?>
    
    <div class="container-fluid">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">GitHub Sync Results</h1>
            <a href="projects.php" class="btn btn-secondary">← Back to Projects</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Sync Result</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong><?php echo htmlspecialchars($message); ?></strong>
                </div>
                
                <?php if (count($details) > 0): ?>
                <h6>Details:</h6>
                <pre style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 13px; max-height: 500px;">
<?php echo implode("\n", array_map('htmlspecialchars', $details)); ?>
                </pre>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="projects.php" class="btn btn-primary">← Back to Projects</a>
                    <a href="sync-manual.php" class="btn btn-warning">Sync All Again</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    require_once '../templates/admin-footer.php';
    exit;
}
?>