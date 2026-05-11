<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// دریافت تنظیمات
$settings = getSettings();
$githubUsername = $settings['github_username'] ?? '';

// تنظیمات hero
$hero_title = $settings['hero_title'] ?? "Hi, I'm " . ($settings['site_name'] ?? 'My Portfolio');
$hero_subtitle = $settings['hero_subtitle'] ?? 'Developer & Open Source Creator';
$hero_description = $settings['hero_description'] ?? 'Building web experiences with PHP, JavaScript, and open source. Passionate about clean code and developer experience.';
$hero_button_text = $settings['hero_button_text'] ?? 'View Projects';
$hero_button_link = $settings['hero_button_link'] ?? '/personal-cms/projects.php';
$hero_github_text = $settings['hero_github_button_text'] ?? 'GitHub Profile';
$hero_github_link = $settings['hero_github_button_link'] ?? 'https://github.com/yourusername';

// Fetch featured projects (limit 3)
$featuredProjects = [];
try {
    $stmt = $db->query("SELECT * FROM github_projects WHERE status = 'published' ORDER BY github_stars DESC LIMIT 3");
    $featuredProjects = $stmt->fetchAll();
} catch (Exception $e) {
    $featuredProjects = [];
}

// Fetch latest blog posts (limit 3)
$latestPosts = [];
try {
    $stmt = $db->query("SELECT * FROM posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
    $latestPosts = $stmt->fetchAll();
} catch (Exception $e) {
    $latestPosts = [];
}

// Get stats from DB
$stats = [
    'projects' => 0,
    'stars' => 0,
    'posts' => 0,
    'commits' => 0
];

try {
    $stats['projects'] = $db->fetchOne("SELECT COUNT(*) as count FROM github_projects WHERE status = 'published'")['count'] ?? 0;
    $stats['stars'] = $db->fetchOne("SELECT SUM(github_stars) as total FROM github_projects")['total'] ?? 0;
    $stats['posts'] = $db->fetchOne("SELECT COUNT(*) as count FROM posts WHERE status = 'published'")['count'] ?? 0;
    
    // دریافت تعداد commits از گیت‌هاب
    if (!empty($githubUsername)) {
        // ابتدا از کش فایل استفاده کن
        $cacheFile = CACHE_PATH . '/commits_cache.json';
        $cacheTTL = 86400; // 24 hours
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
            $cachedCommits = json_decode(file_get_contents($cacheFile), true);
            if ($cachedCommits && is_numeric($cachedCommits)) {
                $stats['commits'] = $cachedCommits;
            } else {
                $stats['commits'] = intval($settings['total_commits'] ?? 1247);
            }
        } else {
            // تلاش برای دریافت از GitHub API
            try {
                $github = new GitHubAPI();
                $commits = $github->getUserTotalCommits($githubUsername);
                if ($commits > 0) {
                    $stats['commits'] = $commits;
                    file_put_contents($cacheFile, json_encode($commits));
                } else {
                    // اگر API کار نکرد، از مقدار تنظیمات دستی استفاده کن
                    $stats['commits'] = intval($settings['total_commits'] ?? 1247);
                }
            } catch (Exception $e) {
                // خطا در API، از مقدار پیش‌فرض استفاده کن
                $stats['commits'] = intval($settings['total_commits'] ?? 1247);
            }
        }
    } else {
        // اگر username تنظیم نشده، از مقدار دستی استفاده کن
        $stats['commits'] = intval($settings['total_commits'] ?? 1247);
    }
} catch (Exception $e) {
    $stats['commits'] = 1247;
}

require_once 'includes/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <section class="hero section">
        <h1 class="gradient-text"><?php echo htmlspecialchars($hero_title); ?></h1>
        <h1><?php echo htmlspecialchars($hero_subtitle); ?></h1>
        <p style="font-size: 18px; max-width: 600px;">
            <?php echo nl2br(htmlspecialchars($hero_description)); ?>
        </p>
        <div style="display: flex; gap: 16px; margin-top: 32px; flex-wrap: wrap;">
            <a href="<?php echo htmlspecialchars($hero_button_link); ?>" class="btn btn-primary"><?php echo htmlspecialchars($hero_button_text); ?> →</a>
            <a href="<?php echo htmlspecialchars($hero_github_link); ?>" target="_blank" class="btn btn-secondary"><?php echo htmlspecialchars($hero_github_text); ?></a>
        </div>
        
        <!-- Terminal animation -->
        <div style="margin-top: 48px; background: var(--bg-card); border-radius: var(--radius-md); padding: 20px; border: 1px solid var(--border-color); max-width: 500px; font-family: var(--font-mono); font-size: 14px;">
            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                <span style="width: 12px; height: 12px; background: #ff5f56; border-radius: 50%;"></span>
                <span style="width: 12px; height: 12px; background: #ffbd2e; border-radius: 50%;"></span>
                <span style="width: 12px; height: 12px; background: #27c93f; border-radius: 50%;"></span>
            </div>
            <div>
                <span style="color: var(--accent);">$</span> 
                <span>npx create-portfolio --template=developer</span>
                <div style="color: var(--text-secondary); margin-top: 8px;">
                    ✓ Portfolio created successfully!<br>
                    ✓ <?php echo $stats['projects']; ?> projects loaded<br>
                    ✓ Ready to deploy → 
                </div>
            </div>
        </div>
    </section>
    
    <!-- Quick Stats -->
    <section class="section">
        <div class="grid-4">
            <div class="card stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-number" data-count="<?php echo $stats['projects']; ?>">0</div>
                <div class="stat-label">Open Source Projects</div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-number" data-count="<?php echo $stats['stars']; ?>">0</div>
                <div class="stat-label">GitHub Stars</div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-number" data-count="<?php echo $stats['posts']; ?>">0</div>
                <div class="stat-label">Blog Posts</div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon">💻</div>
                <div class="stat-number" data-count="<?php echo $stats['commits']; ?>">0</div>
                <div class="stat-label">Commits (This Year)</div>
            </div>
        </div>
    </section>
    
    <!-- Featured Projects -->
    <section class="section">
        <div class="section-header">
            <h2>Featured Open Source Work</h2>
            <a href="/personal-cms/projects.php" class="section-link">View all projects →</a>
        </div>
        <div class="grid-3">
            <?php if (count($featuredProjects) > 0): ?>
                <?php foreach ($featuredProjects as $project): ?>
                <div class="card project-card">
                    <?php if ($project['featured_image']): ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $project['featured_image']; ?>" 
                         style="width: 100%; height: 180px; object-fit: cover; border-radius: var(--radius-md); margin-bottom: 16px;">
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($project['custom_description'] ?: $project['github_description'] ?? '', 0, 100)); ?>...</p>
                    <div style="display: flex; gap: 16px; margin-top: 16px; font-size: 14px; color: var(--text-secondary);">
                        <span>⭐ <?php echo number_format($project['github_stars']); ?></span>
                        <span>🍴 <?php echo number_format($project['github_forks']); ?></span>
                        <span class="badge"><?php echo htmlspecialchars($project['github_language'] ?? 'N/A'); ?></span>
                    </div>
                    <a href="/personal-cms/project.php?slug=<?php echo $project['slug']; ?>" style="margin-top: 20px; display: inline-block; color: var(--accent);">View details →</a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card">
                    <p>No projects yet. <a href="/personal-cms/admin/projects.php">Add your first project</a></p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Latest Blog Posts -->
    <section class="section">
        <div class="section-header">
            <h2>Latest Thoughts</h2>
            <a href="/personal-cms/blog.php" class="section-link">Read the blog →</a>
        </div>
        <div class="grid-3">
            <?php if (count($latestPosts) > 0): ?>
                <?php foreach ($latestPosts as $post): ?>
                <div class="card">
                    <div style="font-size: 14px; color: var(--text-secondary); margin-bottom: 12px;">
                        <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                        <span class="badge" style="margin-left: 8px;"><?php echo htmlspecialchars($post['category'] ?? 'Uncategorized'); ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    <p><?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 120)); ?>...</p>
                    <a href="/personal-cms/post.php?slug=<?php echo $post['slug']; ?>" style="margin-top: 16px; display: inline-block; color: var(--accent);">Read more →</a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card">
                    <p>No posts yet. <a href="/personal-cms/admin/posts.php">Write your first post</a></p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>