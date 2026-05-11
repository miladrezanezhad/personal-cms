<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$page_title = 'Projects';
$page_description = 'Browse my open source projects and contributions';

$search = $_GET['search'] ?? '';
$language = $_GET['language'] ?? '';
$sort = $_GET['sort'] ?? 'stars';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Build query
$query = "SELECT * FROM github_projects WHERE status = 'published'";
$params = [];

if ($search) {
    $query .= " AND (title LIKE ? OR custom_description LIKE ? OR github_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($language) {
    $query .= " AND github_language = ?";
    $params[] = $language;
}

// Sorting
switch ($sort) {
    case 'forks':
        $query .= " ORDER BY github_forks DESC";
        break;
    case 'updated':
        $query .= " ORDER BY updated_at DESC";
        break;
    default:
        $query .= " ORDER BY github_stars DESC";
}

// Get total count for pagination
$countQuery = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
try {
    $result = $db->fetchOne($countQuery, $params);
    $total = $result ? $result['total'] : 0;
} catch (Exception $e) {
    $total = 0;
}
$totalPages = ceil($total / $perPage);

// Add pagination
$query .= " LIMIT $perPage OFFSET $offset";

try {
    $projects = $db->fetchAll($query, $params);
} catch (Exception $e) {
    $projects = [];
}

// Get unique languages for filter
try {
    $langs = $db->fetchAll("SELECT DISTINCT github_language FROM github_projects WHERE github_language IS NOT NULL AND github_language != '' AND status = 'published' ORDER BY github_language");
} catch (Exception $e) {
    $langs = [];
}

require_once 'includes/header.php';
?>

<div class="container">
    <div class="section">
        <h1>All Projects</h1>
        <p style="font-size: 18px; color: var(--text-secondary); margin-bottom: 32px;">
            Open source contributions and side projects
        </p>
        
        <!-- Controls Bar -->
        <div class="controls-bar">
            <div class="search-input">
                <form method="GET" action="/personal-cms/projects.php" id="search-form">
                    <input type="text" name="search" placeholder="Search projects..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="width: 100%;">
                </form>
            </div>
            
            <div class="filters">
                <select name="language" class="filter-select" id="language-filter">
                    <option value="">All Languages</option>
                    <?php foreach ($langs as $lang): ?>
                    <option value="<?php echo htmlspecialchars($lang['github_language']); ?>" 
                        <?php echo $language === $lang['github_language'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lang['github_language']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="sort" class="filter-select" id="sort-filter">
                    <option value="stars" <?php echo $sort === 'stars' ? 'selected' : ''; ?>>Most Stars</option>
                    <option value="forks" <?php echo $sort === 'forks' ? 'selected' : ''; ?>>Most Forks</option>
                    <option value="updated" <?php echo $sort === 'updated' ? 'selected' : ''; ?>>Recently Updated</option>
                </select>
            </div>
        </div>
        
        <!-- Projects Grid -->
        <div class="grid-3">
            <?php if (count($projects) > 0): ?>
                <?php foreach ($projects as $project): ?>
                <div class="card project-card">
                    <?php if ($project['featured_image']): ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $project['featured_image']; ?>" 
                         style="width: 100%; height: 180px; object-fit: cover; border-radius: var(--radius-md); margin-bottom: 16px;">
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($project['custom_description'] ?: $project['github_description'] ?? 'No description', 0, 100)); ?>...</p>
                    <div style="display: flex; gap: 16px; margin-top: 16px; font-size: 14px; color: var(--text-secondary); flex-wrap: wrap;">
                        <span>⭐ <?php echo number_format($project['github_stars']); ?></span>
                        <span>🍴 <?php echo number_format($project['github_forks']); ?></span>
                        <?php if ($project['github_language']): ?>
                        <span class="badge"><?php echo htmlspecialchars($project['github_language']); ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="/personal-cms/project.php?slug=<?php echo $project['slug']; ?>" 
                       style="margin-top: 20px; display: inline-block; color: var(--accent);">View details →</a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card" style="grid-column: 1/-1; text-align: center; padding: 48px;">
                    <p>No projects found.</p>
                    <?php if ($search || $language): ?>
                        <a href="/personal-cms/projects.php" class="btn btn-secondary">Clear Filters</a>
                    <?php else: ?>
                        <p style="margin-top: 16px;">Add your first project from the <a href="/personal-cms/admin/projects.php">admin panel</a>.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&language=<?php echo urlencode($language); ?>&sort=<?php echo urlencode($sort); ?>">← Prev</a>
            <?php endif; ?>
            
            <?php 
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($i = $startPage; $i <= $endPage; $i++): 
            ?>
                <?php if ($i == $page): ?>
                <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&language=<?php echo urlencode($language); ?>&sort=<?php echo urlencode($sort); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&language=<?php echo urlencode($language); ?>&sort=<?php echo urlencode($sort); ?>">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Handle filter changes
const languageFilter = document.getElementById('language-filter');
if (languageFilter) {
    languageFilter.addEventListener('change', function() {
        const params = new URLSearchParams(window.location.search);
        if (this.value) params.set('language', this.value);
        else params.delete('language');
        params.set('page', '1');
        window.location.href = '?' + params.toString();
    });
}

const sortFilter = document.getElementById('sort-filter');
if (sortFilter) {
    sortFilter.addEventListener('change', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('sort', this.value);
        params.set('page', '1');
        window.location.href = '?' + params.toString();
    });
}

// Auto-submit search
let searchTimeout;
const searchInput = document.querySelector('.search-input input');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const params = new URLSearchParams(window.location.search);
            if (this.value) params.set('search', this.value);
            else params.delete('search');
            params.set('page', '1');
            window.location.href = '?' + params.toString();
        }, 500);
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>