<?php
// ==============================================
// FILE: templates/admin-sidebar.php
// ==============================================
?>
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['pages.php', 'page-add.php', 'page-edit.php']) ? 'active' : ''; ?>" href="pages.php">
            <i class="fas fa-file-alt"></i> Pages
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['posts.php', 'post-add.php', 'post-edit.php']) ? 'active' : ''; ?>" href="posts.php">
            <i class="fas fa-newspaper"></i> Blog Posts
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" href="categories.php">
            <i class="fas fa-tags"></i> Categories
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['projects.php', 'project-add.php', 'project-edit.php']) ? 'active' : ''; ?>" href="projects.php">
            <i class="fab fa-github"></i> Projects
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'seo.php' ? 'active' : ''; ?>" href="seo.php">
            <i class="fas fa-search"></i> SEO
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
            <i class="fas fa-cog"></i> Settings
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : ''; ?>" href="backup.php">
            <i class="fas fa-database"></i> Backup & Restore
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cache-clear.php' ? 'active' : ''; ?>" href="cache-clear.php">
            <i class="fas fa-trash-alt"></i> Clear Cache
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'logs-viewer.php' ? 'active' : ''; ?>" href="logs-viewer.php">
            <i class="fas fa-history"></i> Logs Viewer
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'deploy-checklist.php' ? 'active' : ''; ?>" href="deploy-checklist.php">
            <i class="fas fa-check-circle"></i> Deployment Checklist
        </a>
    </li>
</ul>

<style>
    .sidebar .nav-link {
        font-weight: 500;
        color: #333;
        padding: 10px 15px;
        border-radius: 8px;
        margin: 2px 0;
        transition: all 0.2s ease;
    }
    .sidebar .nav-link:hover {
        background-color: #e9ecef;
        color: #0d6efd;
        transform: translateX(5px);
    }
    .sidebar .nav-link.active {
        color: #0d6efd;
        background-color: #e9ecef;
        border-left: 3px solid #0d6efd;
    }
    .sidebar .nav-link i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }
</style>