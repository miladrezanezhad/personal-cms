# Mini CMS with GitHub Integration

A complete, lightweight CMS for developer portfolios with automatic GitHub repository syncing, blog management, and SEO tools.

## System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Extensions**: PDO, cURL, GD, JSON, Session
- **Web Server**: Apache with mod_rewrite (Nginx compatible with custom rules)
- **Composer**: Not required (no dependencies)

## Installation

### 1. Upload Files

Upload all files to your web server's document root (e.g., `public_html/`).

### 2. Set Permissions

```bash
chmod 755 uploads/ logs/ backups/ cache/
chmod 644 config.php
```

### 3. Create Database

Create a MySQL database and import the schema:

```sql
-- Run the complete database schema from Prompt 1
```

### 4. Configure config.php

Edit `config.php` and update:

```php
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('SITE_URL', 'https://yourdomain.com');
```

### 5. Configure .htaccess

Uncomment the HTTPS redirect in `.htaccess` if you have SSL enabled.

### 6. Login to Admin

- URL: `https://yourdomain.com/admin/login.php`
- Default credentials: `admin` / `password`
- **IMPORTANT**: Change the default password immediately!

## Features

### Content Management
- **Pages**: Create and manage static pages with SEO fields
- **Blog Posts**: Write articles with categories, tags, and featured images
- **Projects**: Showcase GitHub repositories with auto-synced stats

### GitHub Integration
- Auto-sync repository data (stars, forks, language, description)
- GitHub API caching to respect rate limits
- Manual or cron-based synchronization

### SEO Tools
- Meta titles, descriptions, Open Graph tags
- Automatic sitemap.xml generation
- Customizable robots.txt
- JSON-LD schema support

### Admin Features
- Dashboard with statistics
- Role-based access (Admin/Editor)
- Database backup system
- Log viewer for errors and sync logs
- Cache management

## Cron Job Setup

### For GitHub Auto-Sync (Every 6 hours)

**cPanel:**
1. Go to "Cron Jobs"
2. Set to run every 6 hours
3. Command: `/usr/bin/php /home/username/public_html/cron/github-sync.php`

**Command Line (Linux):**
```bash
crontab -e
# Add this line:
0 */6 * * * /usr/bin/php /path/to/public_html/cron/github-sync.php >> /path/to/public_html/logs/sync.log 2>&1
```

**Windows Task Scheduler:**
Create a task to run `php C:\path\to\cron\github-sync.php` every 6 hours.

### For Sitemap Regeneration (Daily)

```bash
0 0 * * * curl -s https://yourdomain.com/sitemap.php > /dev/null
```

## Directory Structure

```
public_html/
├── admin/           # Admin panel files
├── api/             # API endpoints
├── assets/          # CSS, JS, images
├── backups/         # Database backups
├── cache/           # Sitemap cache
├── cron/            # Cron job scripts
├── includes/        # PHP classes
├── logs/            # Error and sync logs
├── templates/       # Admin templates
├── uploads/         # User uploaded images
├── config.php       # Main configuration
├── index.php        # Homepage
├── projects.php     # Projects listing
├── project.php      # Single project
├── blog.php         # Blog listing
├── post.php         # Single post
├── page.php         # Dynamic pages
├── search.php       # Search results
├── sitemap.php      # XML sitemap generator
├── robots.php       # robots.txt generator
└── .htaccess        # URL rewrite rules
```

## Post-Installation Checklist

1. [ ] Change default admin password
2. [ ] Configure site name and description in Settings
3. [ ] Add GitHub Personal Access Token (Settings → GitHub Integration)
4. [ ] Create your first blog post
5. [ ] Add your first GitHub project
6. [ ] Generate and submit sitemap to Google Search Console
7. [ ] Set up cron job for auto-sync
8. [ ] Test contact form (if enabled)
9. [ ] Verify responsive design on mobile devices
10. [ ] Test dark/light mode toggle

## Troubleshooting

### "Database connection failed"
- Verify database credentials in config.php
- Ensure MySQL is running
- Check if database exists

### GitHub sync fails
- Verify GitHub token is valid
- Check rate limits (60/hour without token, 5000/hour with token)
- Ensure repository names are correct

### Images not uploading
- Check `uploads/` directory permissions (755)
- Verify `MAX_FILE_SIZE` in config.php
- Check PHP `upload_max_filesize` setting

### 404 errors on pages
- Ensure `.htaccess` is uploaded
- Verify mod_rewrite is enabled in Apache
- Check RewriteBase in .htaccess

### White screen / PHP errors
- Set `display_errors = 1` in config.php temporarily
- Check `logs/errors.log` for details
- Verify PHP version (7.4+ required)

## Security Recommendations

1. **Change default admin password** immediately
2. **Enable HTTPS** (SSL certificate)
3. **Set strong passwords** for MySQL and admin
4. **Regular backups** - Use admin/backup.php weekly
5. **Limit login attempts** - Consider adding reCAPTCHA
6. **Keep PHP updated** to latest version
7. **Restrict file permissions**: 
   - `config.php` (644)
   - `uploads/` (755, not 777)
   - `logs/` (755, protected by .htaccess)

## Support

For issues or feature requests:
- Check `logs/errors.log` for error details
- Review system requirements
- Ensure all cron jobs are configured correctly

## License

MIT License - Free for personal and commercial use.

## Credits

Built with PHP 7.4+, MySQL, and modern web technologies.

---

**Version**: 1.0.0
**Last Updated**: 2024
