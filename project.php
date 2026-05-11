<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: /personal-cms/projects.php');
    exit;
}

// Increment view count
try {
    $db->query("UPDATE github_projects SET view_count = view_count + 1 WHERE slug = ?", [$slug]);
} catch (Exception $e) {
    // Ignore view count error
}

// Fetch project details
try {
    $project = $db->fetchOne("SELECT * FROM github_projects WHERE slug = ? AND status = 'published'", [$slug]);
} catch (Exception $e) {
    $project = null;
}

if (!$project) {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}

$page_title = $project['title'];
$page_description = $project['meta_description'] ?: $project['custom_description'] ?: $project['github_description'];

// Detect release type
function getReleaseType($version) {
    $version_lower = strtolower($version);
    if (strpos($version_lower, 'beta') !== false) {
        return ['type' => 'beta', 'label' => 'Beta', 'icon' => '🧪'];
    }
    if (strpos($version_lower, 'alpha') !== false) {
        return ['type' => 'prerelease', 'label' => 'Alpha', 'icon' => '⚡'];
    }
    if (strpos($version_lower, 'rc') !== false || strpos($version_lower, 'release-candidate') !== false) {
        return ['type' => 'prerelease', 'label' => 'Release Candidate', 'icon' => '🔧'];
    }
    return ['type' => 'latest', 'label' => 'Latest', 'icon' => '✅'];
}

$releaseInfo = !empty($project['latest_release_version']) ? getReleaseType($project['latest_release_version']) : null;

// Fetch related projects (same language)
$relatedProjects = [];
if ($project['github_language']) {
    try {
        $relatedProjects = $db->fetchAll(
            "SELECT * FROM github_projects WHERE github_language = ? AND id != ? AND status = 'published' LIMIT 3",
            [$project['github_language'], $project['id']]
        );
    } catch (Exception $e) {
        $relatedProjects = [];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="section">
        <!-- Hero -->
        <div class="section-header" style="margin-bottom: 24px;">
            <h1><?php echo htmlspecialchars($project['title']); ?></h1>
            <a href="<?php echo htmlspecialchars($project['github_url'] ?: 'https://github.com/' . $project['github_owner'] . '/' . $project['github_repo']); ?>" 
               target="_blank" class="btn btn-secondary">GitHub ↗</a>
        </div>
        
        <div class="stats-row">
            <div class="stat-item">⭐ <strong><?php echo number_format($project['github_stars']); ?></strong> stars</div>
            <div class="stat-item">🍴 <strong><?php echo number_format($project['github_forks']); ?></strong> forks</div>
            <?php if ($project['github_language']): ?>
            <div class="stat-item">📝 <strong><?php echo htmlspecialchars($project['github_language']); ?></strong></div>
            <?php endif; ?>
            <div class="stat-item">🔄 Last synced: <?php echo $project['last_synced_at'] ? date('M j, Y', strtotime($project['last_synced_at'])) : 'Never'; ?></div>
        </div>
        
        <!-- Two Column Layout -->
        <div class="two-column">
            <!-- Left Column: Description -->
            <div>
                <div class="info-card">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($project['custom_description'] ?: $project['github_description'] ?? 'No description provided.')); ?></p>
                </div>
                
                <?php if ($project['github_topics']): ?>
                <div class="info-card">
                    <h3>Topics</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php 
                        $topics = explode(',', $project['github_topics']);
                        foreach ($topics as $topic):
                            $topic = trim($topic);
                            if ($topic):
                        ?>
                        <span class="badge"><?php echo htmlspecialchars($topic); ?></span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column: Sidebar -->
            <div class="sidebar">
                <!-- Install Command Section -->
                <?php if (!empty($project['install_command'])): ?>
                <div class="info-card">
                    <h4><i class="fas fa-terminal"></i> Quick Install</h4>
                    <div style="background: #1e1e1e; color: #d4d4d4; padding: 12px; border-radius: 8px; font-family: monospace; font-size: 13px; overflow-x: auto;">
                        <?php 
                        $commands = explode("\n", $project['install_command']);
                        foreach ($commands as $cmd):
                            $cmd = trim($cmd);
                            if (!empty($cmd)):
                        ?>
                        <div style="margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                            <div style="flex: 1;">
                                <span style="color: #4ec9b0;">$</span> 
                                <span style="color: #ce9178;"><?php echo htmlspecialchars($cmd); ?></span>
                            </div>
                            <button class="copy-icon-btn" onclick="copyToClipboard('<?php echo addslashes($cmd); ?>', this)" title="Copy command">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Latest Release Section -->
                <?php if (!empty($project['latest_release_version']) && $releaseInfo): ?>
                <div class="info-card">
                    <h4><i class=""></i> Latest Release</h4>
                    <div style="text-align: center; padding: 10px 0;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 8px; flex-wrap: wrap;">
                            <div style="font-size: 28px; font-weight: bold; color: var(--accent);">
                                <?php echo htmlspecialchars($project['latest_release_version']); ?>
                            </div>
                            <span class="release-badge <?php echo $releaseInfo['type']; ?>">
                                <span><?php echo $releaseInfo['icon']; ?></span>
                                <?php echo $releaseInfo['label']; ?>
                            </span>
                        </div>
                        <?php if ($project['latest_release_date']): ?>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 5px;">
                            📅 Released: <?php echo date('M j, Y', strtotime($project['latest_release_date'])); ?>
                        </div>
                        <?php endif; ?>
                        <a href="<?php echo htmlspecialchars($project['latest_release_url'] ?: $project['github_url'] . '/releases'); ?>" 
                           target="_blank" class="btn btn-primary btn-sm mt-3" style="width: 100%;">
                            <i class="fab fa-github"></i> View Release Notes
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Links Section -->
                <div class="info-card">
                    <h4><i class="fas fa-link"></i> Links</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 12px;">
                            <a href="<?php echo htmlspecialchars($project['github_url'] ?: 'https://github.com/' . $project['github_owner'] . '/' . $project['github_repo']); ?>" 
                               target="_blank" style="color: var(--accent); text-decoration: none;">
                                <i class=""></i> 📦 GitHub Repository →
                            </a>
                        </li>
                        <?php if ($project['latest_release_url']): ?>
                        <li style="margin-bottom: 12px;">
                            <a href="<?php echo htmlspecialchars($project['latest_release_url']); ?>" 
                               target="_blank" style="color: var(--accent); text-decoration: none;">
                                <i class=""></i> 🏷️ Latest Release: <?php echo htmlspecialchars($project['latest_release_version']); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Share Section -->
                <div class="info-card">
                    <h4><i class="fas fa-share-alt"></i> Share</h4>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="https://twitter.com/intent/tweet?text=Check out <?php echo urlencode($project['title']); ?>&url=<?php echo urlencode(SITE_URL . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank" class="btn btn-secondary" style="padding: 8px 12px; font-size: 13px;">
                           🐦 Twitter
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(SITE_URL . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank" class="btn btn-secondary" style="padding: 8px 12px; font-size: 13px;">
                           🔗 LinkedIn
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- More Projects -->
        <?php if (count($relatedProjects) > 0): ?>
        <div class="section">
            <h3>More Projects in <?php echo htmlspecialchars($project['github_language']); ?></h3>
            <div class="grid-3">
                <?php foreach ($relatedProjects as $related): ?>
                <div class="card">
                    <h4><?php echo htmlspecialchars($related['title']); ?></h4>
                    <p><?php echo htmlspecialchars(substr($related['custom_description'] ?: $related['github_description'] ?? '', 0, 80)); ?>...</p>
                    <a href="/personal-cms/project.php?slug=<?php echo $related['slug']; ?>" style="color: var(--accent);">View project →</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyToClipboard(text, buttonElement) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success state on the button
        const originalHtml = buttonElement.innerHTML;
        buttonElement.innerHTML = '<i class="fas fa-check"></i>';
        buttonElement.classList.add('copied');
        
        setTimeout(function() {
            buttonElement.innerHTML = originalHtml;
            buttonElement.classList.remove('copied');
        }, 2000);
    }, function() {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        
        // Show feedback
        const originalHtml = buttonElement.innerHTML;
        buttonElement.innerHTML = '<i class="fas fa-check"></i>';
        buttonElement.classList.add('copied');
        
        setTimeout(function() {
            buttonElement.innerHTML = originalHtml;
            buttonElement.classList.remove('copied');
        }, 2000);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>