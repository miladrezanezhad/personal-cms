<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$settings = getSettings();

// تعریف متغیر slug - این خط مشکل را حل می‌کند
$slug = $_GET['slug'] ?? 'about';

// Handle special pages
if ($slug === 'about') {
    $page_title = $settings['about_title'] ?? 'About Me';
    $page_description = $settings['about_subtitle'] ?? 'Learn more about my journey, skills, and experience as a developer';
    
    $about_my_journey = $settings['about_my_journey'] ?? '<p>I started coding over 5 years ago, building small scripts and gradually moving to full-stack web development. Today, I focus on creating developer tools, open source libraries, and sharing knowledge through my blog.</p><p>I believe in clean code, thoughtful design, and the power of community-driven development. When I\'m not coding, I\'m probably reading tech blogs, contributing to OSS, or exploring new technologies.</p>';
    $about_experience_title = $settings['about_experience_title'] ?? 'Experience Timeline';
    $about_skills_title = $settings['about_skills_title'] ?? 'Skills & Technologies';
    
    // Parse experiences
    $experiences = json_decode($settings['about_experiences'] ?? '[]', true);
    if (empty($experiences)) {
        $experiences = [
            ['year' => '2023 - Present', 'title' => 'Senior Developer @ TechCorp', 'description' => 'Leading frontend architecture and mentoring junior developers.'],
            ['year' => '2020 - 2023', 'title' => 'Full Stack Developer @ StartupX', 'description' => 'Built scalable web applications using PHP, React, and Node.js.'],
            ['year' => '2018 - 2020', 'title' => 'Freelance Web Developer', 'description' => 'Worked with various clients on custom websites and e-commerce solutions.']
        ];
    }
    
    // Parse skills
    $frontendSkills = explode(',', $settings['about_frontend_skills'] ?? 'React,Vue.js,Tailwind,JavaScript,TypeScript,HTML/CSS');
    $backendSkills = explode(',', $settings['about_backend_skills'] ?? 'PHP,Node.js,Python,MySQL,PostgreSQL,Laravel');
    $toolsSkills = explode(',', $settings['about_tools_skills'] ?? 'Git,Docker,AWS,Vercel,Linux');
    
    // Get GitHub stats
    $githubStats = ['total_stars' => 0, 'total_repos' => 0];
    try {
        $stmt = $db->query("SELECT SUM(github_stars) as total_stars, COUNT(*) as total_repos FROM github_projects WHERE status = 'published'");
        $githubStats = $stmt->fetch();
        if (!$githubStats) {
            $githubStats = ['total_stars' => 0, 'total_repos' => 0];
        }
    } catch (Exception $e) {
        $githubStats = ['total_stars' => 0, 'total_repos' => 0];
    }
    
    require_once 'includes/header.php';
    ?>
    
    <div class="container">
        <div class="section">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            <p style="font-size: 18px; max-width: 700px; margin-bottom: 48px;">
                <?php echo htmlspecialchars($page_description); ?>
            </p>
            
            <!-- My Journey & GitHub Stats -->
            <div class="grid-2" style="margin-bottom: 48px;">
                <div class="about-content">
                    <?php echo $about_my_journey; ?>
                </div>
                <div class="info-card">
                    <h3>GitHub Stats</h3>
                    <div style="display: flex; justify-content: space-around; text-align: center;">
                        <div>
                            <div style="font-size: 32px; font-weight: 700; color: var(--accent);"><?php echo number_format($githubStats['total_repos'] ?? 0); ?></div>
                            <div style="font-size: 14px;">Repositories</div>
                        </div>
                        <div>
                            <div style="font-size: 32px; font-weight: 700; color: var(--accent);"><?php echo number_format($githubStats['total_stars'] ?? 0); ?></div>
                            <div style="font-size: 14px;">Total Stars</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Experience Timeline -->
            <h3><?php echo htmlspecialchars($about_experience_title); ?></h3>
            <div style="margin: 32px 0;">
                <div style="border-left: 2px solid var(--accent); padding-left: 24px;">
                    <?php foreach ($experiences as $exp): ?>
                    <?php if (!empty($exp['year'])): ?>
                    <div style="margin-bottom: 32px;">
                        <div style="font-weight: 600; color: var(--accent);"><?php echo htmlspecialchars($exp['year']); ?></div>
                        <h4><?php echo htmlspecialchars($exp['title']); ?></h4>
                        <p><?php echo htmlspecialchars($exp['description']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Skills Grid -->
            <h3><?php echo htmlspecialchars($about_skills_title); ?></h3>
            <div class="grid-3" style="margin-top: 24px;">
                <div class="info-card">
                    <h4>Frontend</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($frontendSkills as $skill): ?>
                        <?php $skill = trim($skill); if (!empty($skill)): ?>
                        <span class="badge"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="info-card">
                    <h4>Backend</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($backendSkills as $skill): ?>
                        <?php $skill = trim($skill); if (!empty($skill)): ?>
                        <span class="badge"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="info-card">
                    <h4>Tools & DevOps</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($toolsSkills as $skill): ?>
                        <?php $skill = trim($skill); if (!empty($skill)): ?>
                        <span class="badge"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    require_once 'includes/footer.php';
    exit;
}
elseif ($slug === 'contact') {
    $page_title = 'Contact';
    $page_description = 'Get in touch with me';
    
    $contact_email = $settings['contact_email'] ?? '';
    $contact_phone = $settings['contact_phone'] ?? '';
    $contact_address = $settings['contact_address'] ?? '';
    $contact_form_enabled = ($settings['contact_form_enabled'] ?? '1') == '1';
    
    require_once 'includes/header.php';
    ?>
    
    <div class="container">
        <div class="section">
            <h1>Let's Connect</h1>
            <p style="font-size: 18px; max-width: 600px; margin-bottom: 48px;">
                Have a project in mind or just want to say hi? I'd love to hear from you.
            </p>
            
            <div class="two-column">
                <?php if ($contact_form_enabled): ?>
                <div class="info-card">
                    <h3>Send a Message</h3>
                    <form method="POST" action="/personal-cms/page.php?slug=contact" data-validate>
                        <div style="margin-bottom: 20px;">
                            <label for="name">Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="6" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message →</button>
                    </form>
                    
                    <?php
                    // Handle form submission
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
                        $name = trim($_POST['name']);
                        $email = trim($_POST['email']);
                        $subject = trim($_POST['subject'] ?: 'Contact Form Message');
                        $message = trim($_POST['message']);
                        
                        $to = $contact_email ?: 'admin@example.com';
                        $headers = "From: $email\r\n";
                        $headers .= "Reply-To: $email\r\n";
                        $fullMessage = "Name: $name\nEmail: $email\n\n$message";
                        
                        if (mail($to, $subject, $fullMessage, $headers)) {
                            echo '<div class="alert alert-success">Message sent successfully! I\'ll get back to you soon.</div>';
                        } else {
                            echo '<div class="alert alert-danger">Failed to send message. Please try again later.</div>';
                        }
                    }
                    ?>
                </div>
                <?php endif; ?>
                
                <div>
                    <div class="info-card">
                        <h3>Contact Info</h3>
                        <?php if ($contact_email): ?>
                        <p>📧 <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a></p>
                        <?php endif; ?>
                        <?php if ($contact_phone): ?>
                        <p>📞 <?php echo htmlspecialchars($contact_phone); ?></p>
                        <?php endif; ?>
                        <?php if ($contact_address): ?>
                        <p>📍 <?php echo nl2br(htmlspecialchars($contact_address)); ?></p>
                        <?php endif; ?>
                        
                        <?php
                        $social_twitter = $settings['social_twitter'] ?? '';
                        $social_github = $settings['social_github'] ?? '';
                        $social_linkedin = $settings['social_linkedin'] ?? '';
                        ?>
                        <?php if ($social_twitter || $social_github || $social_linkedin): ?>
                        <div style="margin-top: 20px;">
                            <h4>Social Media</h4>
                            <?php if ($social_twitter): ?>
                            <p>🐦 <a href="<?php echo htmlspecialchars($social_twitter); ?>" target="_blank">Twitter</a></p>
                            <?php endif; ?>
                            <?php if ($social_github): ?>
                            <p>💻 <a href="<?php echo htmlspecialchars($social_github); ?>" target="_blank">GitHub</a></p>
                            <?php endif; ?>
                            <?php if ($social_linkedin): ?>
                            <p>🔗 <a href="<?php echo htmlspecialchars($social_linkedin); ?>" target="_blank">LinkedIn</a></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    require_once 'includes/footer.php';
    exit;
}
else {
    // Custom page from database
    try {
        $page = $db->fetchOne("SELECT * FROM pages WHERE slug = ? AND status = 'published'", [$slug]);
    } catch (Exception $e) {
        $page = null;
    }
    
    if (!$page) {
        http_response_code(404);
        require_once '404.php';
        exit;
    }
    
    $page_title = $page['title'];
    $page_description = $page['meta_description'] ?? substr(strip_tags($page['content']), 0, 160);
    
    require_once 'includes/header.php';
    ?>
    
    <div class="container">
        <div class="section">
            <h1><?php echo htmlspecialchars($page['title']); ?></h1>
            <div class="post-content" style="max-width: 800px;">
                <?php echo $page['content']; ?>
            </div>
        </div>
    </div>
    
    <?php
    require_once 'includes/footer.php';
    exit;
}
?>