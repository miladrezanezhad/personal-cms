<?php
// ==============================================
// FILE: includes/GitHubAPI.php
// ==============================================

class GitHubAPI {
    private $token;
    private $db;
    private $cacheTTL = 21600; // 6 hours
    
    public function __construct($token = null) {
        $this->db = Database::getInstance();
        if ($token) {
            $this->token = $token;
        } else {
            $setting = $this->db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'github_token'");
            $this->token = $setting ? $setting['setting_value'] : '';
        }
    }
    
    private function getFromCache($key) {
        $cached = $this->db->fetchOne(
            "SELECT response, expires_at FROM github_cache WHERE cache_key = ? AND expires_at > NOW()",
            [$key]
        );
        if ($cached) {
            return json_decode($cached['response'], true);
        }
        return null;
    }
    
    private function saveToCache($key, $data, $ttl = null) {
        $ttl = $ttl ?: $this->cacheTTL;
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        
        $this->db->query(
            "INSERT INTO github_cache (cache_key, response, expires_at) 
             VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE response = ?, expires_at = ?",
            [$key, json_encode($data), $expiresAt, json_encode($data), $expiresAt]
        );
    }
    
    private function request($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PortfolioCMS/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $headers = [
            'Accept: application/vnd.github.v3+json'
        ];
        
        if ($this->token) {
            $headers[] = 'Authorization: token ' . $this->token;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("GitHub API error: $httpCode for $url");
            return null;
        }
        
        return json_decode($response, true);
    }
    
    public function getRepository($owner, $repo) {
        $cacheKey = "repo_{$owner}_{$repo}";
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached) {
            return $cached;
        }
        
        $url = "https://api.github.com/repos/{$owner}/{$repo}";
        $data = $this->request($url);
        
        if ($data) {
            $this->saveToCache($cacheKey, $data);
        }
        
        return $data;
    }
    
    /**
     * Get latest release from GitHub repository
     */
    public function getLatestRelease($owner, $repo) {
        $cacheKey = "release_{$owner}_{$repo}";
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached) {
            return $cached;
        }
        
        $url = "https://api.github.com/repos/{$owner}/{$repo}/releases/latest";
        $data = $this->request($url);
        
        if ($data && !isset($data['message'])) {
            $releaseData = [
                'version' => $data['tag_name'] ?? '',
                'name' => $data['name'] ?? $data['tag_name'] ?? '',
                'published_at' => $data['published_at'] ?? '',
                'html_url' => $data['html_url'] ?? '',
                'body' => substr($data['body'] ?? '', 0, 500)
            ];
            $this->saveToCache($cacheKey, $releaseData, 43200); // 12 hours cache
            return $releaseData;
        }
        
        return null;
    }
    
    /**
     * Sync latest release for a project
     */
    public function syncLatestRelease($projectId) {
        $db = $this->db;
        $project = $db->fetchOne("SELECT * FROM github_projects WHERE id = ?", [$projectId]);
        
        if (!$project) {
            return false;
        }
        
        $owner = $project['github_owner'];
        $repo = $project['github_repo'];
        
        $release = $this->getLatestRelease($owner, $repo);
        
        if ($release && !empty($release['version'])) {
            $updateData = [
                'latest_release_version' => $release['version'],
                'latest_release_date' => !empty($release['published_at']) ? date('Y-m-d H:i:s', strtotime($release['published_at'])) : null,
                'latest_release_url' => $release['html_url']
            ];
            $db->update('github_projects', $updateData, 'id = :id', ['id' => $projectId]);
            return $release;
        }
        
        return null;
    }
    
    public function syncProject($projectId) {
        $db = $this->db;
        $project = $db->fetchOne("SELECT * FROM github_projects WHERE id = ?", [$projectId]);
        
        if (!$project) {
            return ['success' => false, 'message' => 'Project not found'];
        }
        
        $owner = $project['github_owner'];
        $repo = $project['github_repo'];
        
        $repoData = $this->getRepository($owner, $repo);
        
        if (!$repoData) {
            $db->query(
                "UPDATE github_projects SET sync_status = 'failed', last_synced_at = NOW() WHERE id = ?",
                [$projectId]
            );
            return ['success' => false, 'message' => 'Failed to fetch from GitHub API'];
        }
        
        $updateData = [
            'github_full_name' => $repoData['full_name'] ?? '',
            'github_description' => $repoData['description'] ?? '',
            'github_stars' => $repoData['stargazers_count'] ?? 0,
            'github_forks' => $repoData['forks_count'] ?? 0,
            'github_language' => $repoData['language'] ?? '',
            'github_url' => $repoData['html_url'] ?? '',
            'github_topics' => isset($repoData['topics']) ? implode(',', $repoData['topics']) : '',
            'sync_status' => 'synced',
            'last_synced_at' => date('Y-m-d H:i:s')
        ];
        
        $db->update('github_projects', $updateData, 'id = :id', ['id' => $projectId]);
        
        // Sync latest release
        $this->syncLatestRelease($projectId);
        
        return ['success' => true, 'message' => 'Synced successfully', 'stars' => $updateData['github_stars']];
    }
    
    public function syncAllProjects() {
        $db = $this->db;
        $projects = $db->fetchAll("SELECT id FROM github_projects WHERE status = 'published'");
        
        $success = 0;
        $failed = 0;
        
        foreach ($projects as $project) {
            $result = $this->syncProject($project['id']);
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
            // Rate limiting - sleep a bit
            usleep(500000); // 0.5 seconds
        }
        
        return ['success' => $success, 'failed' => $failed];
    }
    
    /**
     * Get total commits for a user
     */
    public function getUserTotalCommits($username) {
        if (empty($username)) {
            return 0;
        }
        
        $cacheKey = "user_commits_total_{$username}";
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached) {
            return $cached;
        }
        
        $query = '{
            user(login: "' . $username . '") {
                contributionsCollection {
                    totalCommitContributions
                    restrictedContributionsCount
                }
            }
        }';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/graphql');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PortfolioCMS/1.0');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $headers = ['Content-Type: application/json'];
        if ($this->token) {
            $headers[] = 'Authorization: bearer ' . $this->token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return 0;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['data']['user']['contributionsCollection'])) {
            $total = $data['data']['user']['contributionsCollection']['totalCommitContributions'] ?? 0;
            $total += $data['data']['user']['contributionsCollection']['restrictedContributionsCount'] ?? 0;
            $this->saveToCache($cacheKey, $total, 86400);
            return $total;
        }
        
        return 0;
    }
}
?>