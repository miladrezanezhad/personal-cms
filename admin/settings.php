<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';

Auth::requireAdmin();

$db = Database::getInstance();
$error = '';
$success = '';

// Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // Get all settings from form
    $settings = [
        // General
        'site_name' => $_POST['site_name'] ?? '',
        'site_description' => $_POST['site_description'] ?? '',
        'site_logo' => $_POST['site_logo'] ?? '',
        'site_favicon' => $_POST['site_favicon'] ?? '',
        
        // Hero Section
        'hero_title' => $_POST['hero_title'] ?? '',
        'hero_subtitle' => $_POST['hero_subtitle'] ?? '',
        'hero_description' => $_POST['hero_description'] ?? '',
        'hero_button_text' => $_POST['hero_button_text'] ?? '',
        'hero_button_link' => $_POST['hero_button_link'] ?? '',
        'hero_github_button_text' => $_POST['hero_github_button_text'] ?? '',
        'hero_github_button_link' => $_POST['hero_github_button_link'] ?? '',
        
        // Footer
        'footer_copyright_text' => $_POST['footer_copyright_text'] ?? '',
        'footer_github_url' => $_POST['footer_github_url'] ?? '',
        'footer_twitter_url' => $_POST['footer_twitter_url'] ?? '',
        'footer_linkedin_url' => $_POST['footer_linkedin_url'] ?? '',
        
        // Social
        'social_twitter' => $_POST['social_twitter'] ?? '',
        'social_github' => $_POST['social_github'] ?? '',
        'social_linkedin' => $_POST['social_linkedin'] ?? '',
        
        // About Page
        'about_title' => $_POST['about_title'] ?? '',
        'about_subtitle' => $_POST['about_subtitle'] ?? '',
        'about_my_journey' => $_POST['about_my_journey'] ?? '',
        'about_experience_title' => $_POST['about_experience_title'] ?? '',
        'about_experiences' => json_encode([
            ['year' => $_POST['exp1_year'] ?? '', 'title' => $_POST['exp1_title'] ?? '', 'description' => $_POST['exp1_desc'] ?? ''],
            ['year' => $_POST['exp2_year'] ?? '', 'title' => $_POST['exp2_title'] ?? '', 'description' => $_POST['exp2_desc'] ?? ''],
            ['year' => $_POST['exp3_year'] ?? '', 'title' => $_POST['exp3_title'] ?? '', 'description' => $_POST['exp3_desc'] ?? '']
        ]),
        'about_skills_title' => $_POST['about_skills_title'] ?? '',
        'about_frontend_skills' => $_POST['about_frontend_skills'] ?? '',
        'about_backend_skills' => $_POST['about_backend_skills'] ?? '',
        'about_tools_skills' => $_POST['about_tools_skills'] ?? '',
        
        // Contact
        'contact_email' => $_POST['contact_email'] ?? '',
        'contact_phone' => $_POST['contact_phone'] ?? '',
        'contact_address' => $_POST['contact_address'] ?? '',
        'contact_form_enabled' => isset($_POST['contact_form_enabled']) ? '1' : '0',
        
        // GitHub
        'github_token' => $_POST['github_token'] ?? '',
        'github_username' => $_POST['github_username'] ?? '',
        'sync_hour' => $_POST['sync_hour'] ?? '2'
    ];
    
    // Upload logo
    if (isset($_FILES['site_logo_file']) && $_FILES['site_logo_file']['error'] === UPLOAD_ERR_OK) {
        $upload = Upload::uploadImage($_FILES['site_logo_file'], 'settings', false);
        if ($upload['success']) {
            $settings['site_logo'] = $upload['path'];
        }
    }
    
    // Upload favicon
    if (isset($_FILES['site_favicon_file']) && $_FILES['site_favicon_file']['error'] === UPLOAD_ERR_OK) {
        $upload = Upload::uploadImage($_FILES['site_favicon_file'], 'settings', false);
        if ($upload['success']) {
            $settings['site_favicon'] = $upload['path'];
        }
    }
    
    try {
        foreach ($settings as $key => $value) {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
        }
        $success = 'Settings saved successfully!';
        
        // Clear cache
        getSettings(null, true);
    } catch (Exception $e) {
        $error = 'Failed to save settings: ' . $e->getMessage();
    }
}

// Get current settings
$currentSettings = getSettings();

// Get experiences for display
$experiences = json_decode($currentSettings['about_experiences'] ?? '[]', true);
if (empty($experiences)) {
    $experiences = [
        ['year' => '2023 - Present', 'title' => 'Senior Developer @ TechCorp', 'description' => 'Leading frontend architecture and mentoring junior developers.'],
        ['year' => '2020 - 2023', 'title' => 'Full Stack Developer @ StartupX', 'description' => 'Built scalable web applications using PHP, React, and Node.js.'],
        ['year' => '2018 - 2020', 'title' => 'Freelance Web Developer', 'description' => 'Worked with various clients on custom websites and e-commerce solutions.']
    ];
}

require_once '../templates/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Site Settings</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success" id="successAlert"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" id="settingsForm">
        <input type="hidden" name="save_settings" value="1">
        
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-3" id="settingsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#general" role="tab">General</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#hero" role="tab">Hero Section</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#footer" role="tab">Footer</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#social" role="tab">Social Media</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#about" role="tab">About Page</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#contact" role="tab">Contact Page</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#github" role="tab">GitHub</button>
            </li>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content">
            
            <!-- Tab 1: General -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">General Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['site_name'] ?? 'My Portfolio'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Site Description</label>
                            <textarea name="site_description" class="form-control" rows="3"><?php 
                                echo htmlspecialchars($currentSettings['site_description'] ?? ''); 
                            ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Site Logo</label>
                            <?php if (!empty($currentSettings['site_logo'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $currentSettings['site_logo']; ?>" style="max-height: 50px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="site_logo_file" class="form-control" accept="image/*">
                            <input type="hidden" name="site_logo" value="<?php echo $currentSettings['site_logo'] ?? ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Favicon</label>
                            <input type="file" name="site_favicon_file" class="form-control" accept="image/*">
                            <input type="hidden" name="site_favicon" value="<?php echo $currentSettings['site_favicon'] ?? ''; ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab 2: Hero Section -->
            <div class="tab-pane fade" id="hero" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Hero Section Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Hero Title (Gradient Text)</label>
                            <input type="text" name="hero_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['hero_title'] ?? "Hi, I'm My Portfolio"); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hero Subtitle</label>
                            <input type="text" name="hero_subtitle" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['hero_subtitle'] ?? 'Developer & Open Source Creator'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hero Description</label>
                            <textarea name="hero_description" class="form-control" rows="3"><?php 
                                echo htmlspecialchars($currentSettings['hero_description'] ?? 'Building web experiences with PHP, JavaScript, and open source. Passionate about clean code and developer experience.'); 
                            ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Primary Button Text</label>
                                    <input type="text" name="hero_button_text" class="form-control" 
                                           value="<?php echo htmlspecialchars($currentSettings['hero_button_text'] ?? 'View Projects'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Primary Button Link</label>
                                    <input type="text" name="hero_button_link" class="form-control" 
                                           value="<?php echo htmlspecialchars($currentSettings['hero_button_link'] ?? '/personal-cms/projects.php'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">GitHub Button Text</label>
                                    <input type="text" name="hero_github_button_text" class="form-control" 
                                           value="<?php echo htmlspecialchars($currentSettings['hero_github_button_text'] ?? 'GitHub Profile'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">GitHub Button Link</label>
                                    <input type="text" name="hero_github_button_link" class="form-control" 
                                           value="<?php echo htmlspecialchars($currentSettings['hero_github_button_link'] ?? 'https://github.com/yourusername'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab 3: Footer -->
            <div class="tab-pane fade" id="footer" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Footer Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Copyright Text</label>
                            <input type="text" name="footer_copyright_text" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['footer_copyright_text'] ?? 'Built with PHP & ❤️'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">GitHub URL</label>
                            <input type="text" name="footer_github_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['footer_github_url'] ?? 'https://github.com/yourusername'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Twitter URL</label>
                            <input type="text" name="footer_twitter_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['footer_twitter_url'] ?? 'https://twitter.com/yourusername'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">LinkedIn URL</label>
                            <input type="text" name="footer_linkedin_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['footer_linkedin_url'] ?? 'https://linkedin.com/in/yourusername'); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab 4: Social Media -->
            <div class="tab-pane fade" id="social" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Social Media Links</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Twitter</label>
                            <input type="text" name="social_twitter" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['social_twitter'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">GitHub</label>
                            <input type="text" name="social_github" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['social_github'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">LinkedIn</label>
                            <input type="text" name="social_linkedin" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['social_linkedin'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab 5: About Page -->
            <div class="tab-pane fade" id="about" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">About Page Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Page Title</label>
                            <input type="text" name="about_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['about_title'] ?? 'About Me'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subtitle / Tagline</label>
                            <input type="text" name="about_subtitle" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['about_subtitle'] ?? 'Developer, open source contributor, and lifelong learner.'); ?>">
                        </div>
                        
                        <hr>
                        <h6>Main Content - My Journey</h6>
                        <div class="mb-3">
                            <label class="form-label">My Journey (HTML allowed)</label>
                            <textarea name="about_my_journey" class="form-control" rows="8"><?php 
                                echo htmlspecialchars($currentSettings['about_my_journey'] ?? ''); 
                            ?></textarea>
                        </div>
                        
                        <hr>
                        <h6>Experience Timeline</h6>
                        <div class="mb-3">
                            <label class="form-label">Timeline Title</label>
                            <input type="text" name="about_experience_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['about_experience_title'] ?? 'Experience Timeline'); ?>">
                        </div>
                        
                        <!-- Experience 1 -->
                        <div class="card mb-3" style="background: #f8f9fa;">
                            <div class="card-body">
                                <h6>Experience 1</h6>
                                <div class="mb-2">
                                    <label class="form-label">Year</label>
                                    <input type="text" name="exp1_year" class="form-control" 
                                           value="<?php echo htmlspecialchars($experiences[0]['year'] ?? ''); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="exp1_title" class="form-control" 
                                           value="<?php echo htmlspecialchars($experiences[0]['title'] ?? ''); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Description</label>
                                    <textarea name="exp1_desc" class="form-control" rows="2"><?php 
                                        echo htmlspecialchars($experiences[0]['description'] ?? '');
                                    ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Experience 2 -->
                        <div class="card mb-3" style="background: #f8f9fa;">
                            <div class="card-body">
                                <h6>Experience 2</h6>
                                <div class="mb-2">
                                    <label class="form-label">Year</label>
                                    <input type="text" name="exp2_year" class="form-control" 
                                           value="<?php echo htmlspecialchars($experiences[1]['year'] ?? ''); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="exp2_title" class="form-control" 
                                           value="<?php echo htmlspecialchars($experiences[1]['title'] ?? ''); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Description</label>
                                    <textarea name="exp2_desc" class="form-control" rows="2"><?php 
                                        echo htmlspecialchars($experiences[1]['description'] ?? '');
                                    ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Experience 3 -->
                        <div class="card mb-3" style="background: #f8f9fa;">
                            <div class="card-body">
                                <h6>Experience 3</h6>
                                <div class="mb-2">
                                    <label class="form-label">Year</label>
                                    <input type="text" name="exp3_year" class="form-control" 
                                           value="<?php echo htmlspecialchars($experiences[2]['year'] ?? ''); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="exp3_title" class="form-control" 
                                           value="<?php echo htmlspecialchars($experiences[2]['title'] ?? ''); ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Description</label>
                                    <textarea name="exp3_desc" class="form-control" rows="2"><?php 
                                        echo htmlspecialchars($experiences[2]['description'] ?? '');
                                    ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        <h6>Skills Section</h6>
                        <div class="mb-3">
                            <label class="form-label">Skills Section Title</label>
                            <input type="text" name="about_skills_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['about_skills_title'] ?? 'Skills & Technologies'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Frontend Skills (comma separated)</label>
                            <input type="text" name="about_frontend_skills" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['about_frontend_skills'] ?? 'React,Vue.js,Tailwind,JavaScript,TypeScript,HTML/CSS'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Backend Skills (comma separated)</label>
                            <input type="text" name="about_backend_skills" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['about_backend_skills'] ?? 'PHP,Node.js,Python,MySQL,PostgreSQL,Laravel'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tools & DevOps Skills (comma separated)</label>
                            <input type="text" name="about_tools_skills" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['about_tools_skills'] ?? 'Git,Docker,AWS,Vercel,Linux'); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab 6: Contact Page -->
            <div class="tab-pane fade" id="contact" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Contact Page Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="contact_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['contact_email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="contact_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['contact_phone'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="contact_address" class="form-control" rows="3"><?php 
                                echo htmlspecialchars($currentSettings['contact_address'] ?? ''); 
                            ?></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="contact_form_enabled" id="contact_form_enabled" 
                                       class="form-check-input" value="1" 
                                       <?php echo ($currentSettings['contact_form_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                <label for="contact_form_enabled" class="form-check-label">Enable Contact Form</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab 7: GitHub -->
            <div class="tab-pane fade" id="github" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">GitHub Integration</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">GitHub Personal Access Token</label>
                            <input type="password" name="github_token" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['github_token'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">GitHub Username</label>
                            <input type="text" name="github_username" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['github_username'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Auto-Sync Hour (UTC)</label>
                            <select name="sync_hour" class="form-select">
                                <?php for ($i = 0; $i <= 23; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($currentSettings['sync_hour'] ?? '2') == $i ? 'selected' : ''; ?>>
                                    <?php echo sprintf("%02d:00", $i); ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Save Button -->
        <div class="mt-3 mb-4">
            <button type="submit" name="save_settings" value="1" class="btn btn-primary btn-lg">Save All Settings</button>
            <a href="index.php" class="btn btn-secondary btn-lg">Back to Dashboard</a>
        </div>
    </form>
</div>

<script>
    // Auto-hide success alert after 3 seconds
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.display = 'none';
        }, 3000);
    }
    
    // Store active tab in localStorage and restore after page load
    const tabs = document.querySelectorAll('#settingsTab button');
    const tabContents = document.querySelectorAll('.tab-pane');
    
    // Restore active tab from localStorage on page load
    const activeTab = localStorage.getItem('activeSettingsTab');
    if (activeTab) {
        // Remove active class from all tabs and contents
        tabs.forEach(tab => tab.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('show', 'active'));
        
        // Add active class to saved tab
        const targetBtn = document.querySelector(`#settingsTab button[data-bs-target="${activeTab}"]`);
        if (targetBtn) {
            targetBtn.classList.add('active');
            const targetContent = document.querySelector(activeTab);
            if (targetContent) {
                targetContent.classList.add('show', 'active');
            }
        }
    }
    
    // Save active tab when clicked
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.getAttribute('data-bs-target');
            localStorage.setItem('activeSettingsTab', target);
        });
    });
</script>

<?php require_once '../templates/admin-footer.php'; ?>