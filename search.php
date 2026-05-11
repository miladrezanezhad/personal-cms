<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

$query = trim($_GET['q'] ?? '');
$page_title = $query ? "Search: $query" : "Search";
$page_description = "Search results for your query";

$results = ['projects' => [], 'posts' => []];

if ($query) {
    // Search projects
    try {
        $results['projects'] = $db->fetchAll(
            "SELECT * FROM github_projects WHERE status = 'published' AND (title LIKE ? OR custom_description LIKE ? OR github_description LIKE ?) LIMIT 10",
            ["%$query%", "%$query%", "%$query%"]
        );
    } catch (Exception $e) {
        $results['projects'] = [];
    }
    
    // Search blog posts
    try {
        $results['posts'] = $db->fetchAll(
            "SELECT * FROM posts WHERE status = 'published' AND (title LIKE ? OR content LIKE ? OR excerpt LIKE ?) LIMIT 10",
            ["%$query%", "%$query%", "%$query%"]
        );
    } catch (Exception $e) {
        $results['posts'] = [];
    }
}

$totalResults = count($results['projects']) + count($results['posts']);

require_once 'includes/header.php';
?>

<div class="container">
    <div class="section">
        <h1>Search</h1>
        
        <div style="max-width: 600px; margin-bottom: 48px;">
            <form method="GET" action="/personal-cms/search.php">
                <div style="display: flex; gap: 12px;">
                    <input type="text" name="q" placeholder="Search projects, articles..." 
                           value="<?php echo htmlspecialchars($query); ?>"
                           style="flex: 1;">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>
        
        <?php if ($query): ?>
            <p style="margin-bottom: 32px;">Found <?php echo $totalResults; ?> result(s) for "<strong><?php echo htmlspecialchars($query); ?></strong>"</p>
            
            <?php if (empty($results['projects']) && empty($results['posts'])): ?>
                <div class="info-card" style="text-align: center; padding: 48px;">
                    <div style="font-size: 48px; margin-bottom: 16px;">🔍</div>
                    <h3>No results found</h3>
                    <p>Try different keywords or check out the <a href="/personal-cms/projects.php">projects page</a> or <a href="/personal-cms/blog.php">blog</a>.</p>
                </div>
            <?php else: ?>
                
                <!-- Projects Results -->
                <?php if (!empty($results['projects'])): ?>
                <div class="search-result-group">
                    <h3>Projects (<?php echo count($results['projects']); ?>)</h3>
                    <?php foreach ($results['projects'] as $project): ?>
                    <div class="card" style="margin-bottom: 16px;">
                        <h4><a href="/personal-cms/project.php?slug=<?php echo $project['slug']; ?>"><?php echo highlight_term($project['title'], $query); ?></a></h4>
                        <p><?php echo highlight_term(substr($project['custom_description'] ?: $project['github_description'] ?? '', 0, 200), $query); ?></p>
                        <div style="display: flex; gap: 12px; font-size: 14px;">
                            <span>⭐ <?php echo number_format($project['github_stars']); ?></span>
                            <?php if ($project['github_language']): ?>
                            <span class="badge"><?php echo htmlspecialchars($project['github_language']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Posts Results -->
                <?php if (!empty($results['posts'])): ?>
                <div class="search-result-group">
                    <h3>Blog Posts (<?php echo count($results['posts']); ?>)</h3>
                    <?php foreach ($results['posts'] as $post): ?>
                    <div class="card" style="margin-bottom: 16px;">
                        <h4><a href="/personal-cms/post.php?slug=<?php echo $post['slug']; ?>"><?php echo highlight_term($post['title'], $query); ?></a></h4>
                        <p><?php echo highlight_term(substr(strip_tags($post['excerpt'] ?: $post['content']), 0, 200), $query); ?></p>
                        <div style="display: flex; gap: 12px; font-size: 14px;">
                            <span>📅 <?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                            <?php if ($post['category']): ?>
                            <span class="badge"><?php echo htmlspecialchars($post['category']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
            <?php endif; ?>
        <?php else: ?>
            <div class="info-card" style="text-align: center; padding: 48px;">
                <h3>Search for projects or articles</h3>
                <p>Try searching for "react", "php", or "open source"</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper function to highlight search terms
function highlight_term($text, $term) {
    if (!$term) return htmlspecialchars($text);
    $term = preg_quote($term, '/');
    return preg_replace('/(' . $term . ')/i', '<span class="search-highlight">$1</span>', htmlspecialchars($text));
}

require_once 'includes/footer.php';
?>