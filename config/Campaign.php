<?php
require_once __DIR__ . '/Database.php';

class Campaign {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    } 

    public function create($data) {
        try {
            error_log("[Campaign::create] Entered create() for user=" . ($_SESSION['user_id'] ?? 'unknown'));
            $this->validateCampaignData($data);
            error_log("[Campaign::create] Data validation passed");

            if (!isset($_FILES['campaign_image'])) {
                error_log("[Campaign::create] campaign_image missing from FILES");
                throw new Exception("Campaign image is required");
            }

            error_log("[Campaign::create] campaign_image metadata: " . json_encode([
                'name' => $_FILES['campaign_image']['name'] ?? null,
                'type' => $_FILES['campaign_image']['type'] ?? null,
                'size' => $_FILES['campaign_image']['size'] ?? null,
                'error' => $_FILES['campaign_image']['error'] ?? null,
                'tmp_name' => $_FILES['campaign_image']['tmp_name'] ?? null,
            ]));

            $imagePath = $this->handleImageUpload($_FILES['campaign_image']);
            error_log("[Campaign::create] Image uploaded successfully to " . $imagePath);

            $sql = "INSERT INTO campaigns (
                user_id, title, category, goal_amount, 
                description, image_path, created_at, status
            ) VALUES (?, ?, ?, ?, ?, ?,  NOW(), 'active')";

            $params = [
                $_SESSION['user_id'],
                $data['title'],
                $data['category'],
                $data['goal'],
                $data['description'],
                $imagePath
            ];

            error_log("[Campaign::create] Executing insert into campaigns table");
            $campaignId = $this->db->insert($sql, $params);
            error_log("[Campaign::create] Insert successful, campaign ID=" . $campaignId);
            return $campaignId;
        } catch (Exception $e) {
            error_log("[Campaign::create] Exception: " . $e->getMessage());
            throw new Exception("Failed to create campaign: " . $e->getMessage());
        }
    }

    private function validateCampaignData($data) {
        if (empty($data['title'])) {
            throw new Exception("Campaign title is required");
        }

        if (empty($data['category'])) {
            throw new Exception("Category is required");
        }

        if (empty($data['goal']) || !is_numeric($data['goal']) || $data['goal'] < 100) {
            throw new Exception("Valid funding goal is required (minimum $100)");
        }


        if (empty($data['description'])) {
            throw new Exception("Campaign description is required");
        }
    }

    private function handleImageUpload($file) {
        error_log("[Campaign::handleImageUpload] Starting image upload handling");
        if (!$file) {
            throw new Exception("Campaign image is required");
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            error_log("[Campaign::handleImageUpload] Upload error code=" . ($file['error'] ?? 'null'));
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception("Image size must be less than 10MB");
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception("Campaign image is required");
                default:
                    throw new Exception("Failed to upload image");
            }
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            error_log("[Campaign::handleImageUpload] tmp_name is not a valid uploaded file: " . ($file['tmp_name'] ?? 'null'));
            throw new Exception("Failed to upload image");
        }

        $detectedMimeType = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detectedMimeType = finfo_file($finfo, $file['tmp_name']) ?: null;
                finfo_close($finfo);
            }
        }

        if ($detectedMimeType === null && function_exists('mime_content_type')) {
            $detectedMimeType = mime_content_type($file['tmp_name']) ?: null;
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        error_log("[Campaign::handleImageUpload] Detected mime type=" . ($detectedMimeType ?? 'unknown') . ", extension=" . $extension);

        if (($detectedMimeType && !in_array($detectedMimeType, $allowedMimeTypes, true)) || !in_array($extension, $allowedExtensions, true)) {
            throw new Exception("Invalid image format. Please use JPG, PNG, GIF, or WEBP");
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception("Image size must be less than 10MB");
        }

        $uploadDir = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'campaigns' . DIRECTORY_SEPARATOR;
        $publicUploadPrefix = 'uploads/campaigns/';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            throw new Exception("Failed to prepare image upload directory");
        }

        @chmod(rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR), 0777);
        @chmod($uploadDir, 0777);

        if (!is_writable($uploadDir)) {
            $permissions = substr(sprintf('%o', @fileperms($uploadDir)), -4);
            $ownerId = @fileowner($uploadDir);
            $groupId = @filegroup($uploadDir);
            error_log("[Campaign::handleImageUpload] Upload directory is not writable: " . $uploadDir . ", perms=" . $permissions . ", owner=" . ($ownerId === false ? 'unknown' : $ownerId) . ", group=" . ($groupId === false ? 'unknown' : $groupId));
            throw new Exception("Campaign uploads directory is not writable for the web server process. Please make /opt/lampp/htdocs/crofund/uploads/campaigns writable by Apache (daemon).");
        }

        $safeBaseName = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $safeBaseName = trim($safeBaseName, '._-');
        if ($safeBaseName === '') {
            $safeBaseName = 'campaign';
        }

        $filename = uniqid('', true) . '_' . $safeBaseName . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        error_log("[Campaign::handleImageUpload] Moving uploaded file to " . $targetPath);

        clearstatcache(true, $file['tmp_name']);
        if (!file_exists($file['tmp_name'])) {
            error_log("[Campaign::handleImageUpload] Temporary upload file does not exist: " . ($file['tmp_name'] ?? 'null'));
            throw new Exception("Uploaded image temporary file could not be found");
        }

        $moved = move_uploaded_file($file['tmp_name'], $targetPath);

        if (!$moved) {
            $lastError = error_get_last();
            error_log("[Campaign::handleImageUpload] move_uploaded_file failed for tmp_name=" . ($file['tmp_name'] ?? 'null') . ", targetPath=" . $targetPath . ", details=" . json_encode($lastError));

            if (@rename($file['tmp_name'], $targetPath)) {
                $moved = true;
                error_log("[Campaign::handleImageUpload] Fallback rename() succeeded");
            } elseif (@copy($file['tmp_name'], $targetPath)) {
                @unlink($file['tmp_name']);
                $moved = true;
                error_log("[Campaign::handleImageUpload] Fallback copy() succeeded");
            }
        }

        if (!$moved) {
            throw new Exception("Failed to move uploaded image to the campaign uploads directory");
        }

        error_log("[Campaign::handleImageUpload] Upload move successful to " . $uploadDir);
        return $publicUploadPrefix . $filename;
    }

    public function getUserCampaignStats($userId) {
        try {
            // Get total campaigns
            $total = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM campaigns WHERE user_id = ?", 
                [$userId]
            )['count'];

            // Get active campaigns
            $active = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM campaigns 
                 WHERE user_id = ? AND status = 'active' 
                 AND end_date > NOW()", 
                [$userId]
            )['count'];

            // Get total raised
            $totalRaised = $this->db->fetchOne(
                "SELECT COALESCE(SUM(d.amount), 0) as total 
                 FROM campaigns c 
                 LEFT JOIN donations d ON c.id = d.campaign_id 
                 WHERE c.user_id = ?", 
                [$userId]
            )['total'];

            // Calculate success rate
            $successfulCampaigns = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM campaigns c 
                 WHERE c.user_id = ? 
                 AND (SELECT COALESCE(SUM(amount), 0) 
                      FROM donations d 
                      WHERE d.campaign_id = c.id) >= c.goal_amount", 
                [$userId]
            )['count'];

            $successRate = $total > 0 ? round(($successfulCampaigns / $total) * 100) : 0;

            return [
                'total' => (int)$total,
                'active' => (int)$active,
                'total_raised' => (float)$totalRaised,
                'success_rate' => $successRate
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to fetch campaign stats: " . $e->getMessage());
        }
    }
    
       public function getUserInfo($userId) {
        try {
            $query = "SELECT id, name, email, phone, country, city, created_at 
                     FROM users 
                     WHERE id = ?";
            return $this->db->fetchOne($query, [$userId]);
        } catch (Exception $e) {
            throw new Exception("Failed to fetch user info: " . $e->getMessage());
        }
    }



 public function hasUserVerifiedId($userId) {
        try {
            $query = "SELECT id_verified FROM users WHERE id = ?";
            $result = $this->db->fetchOne($query, [$userId]);
            return $result && $result['id_verified'] == 1;
        } catch (Exception $e) {
            return false;
        }
    }
    
    

    public function markIdAsVerified($userId) {
        try {
            $query = "UPDATE users SET id_verified = 1 WHERE id = ?";
            return $this->db->query($query, [$userId]);
        } catch (Exception $e) {
            throw new Exception("Failed to mark ID as verified: " . $e->getMessage());
        }
    }


    public function hasUserAttemptedVerification($userId) {
        try {
            $query = "SELECT verification_attempted FROM users WHERE id = ?";
            $result = $this->db->fetchOne($query, [$userId]);
            return $result && $result['verification_attempted'] == 1;
        } catch (Exception $e) {
            return false;
        }
    }
    
        public function getVerificationAttempts($userId) {
        try {
            $query = "SELECT verification_attempts FROM users WHERE id = ?";
            $result = $this->db->fetchOne($query, [$userId]);
            return $result ? (int)$result['verification_attempts'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    public function hasExceededMaxAttempts($userId, $maxAttempts = 3) {
        try {
            $attempts = $this->getVerificationAttempts($userId);
            return $attempts >= $maxAttempts;
        } catch (Exception $e) {
            return false;
        }
    }

    public function incrementVerificationAttempts($userId) {
        try {
            $query = "UPDATE users SET verification_attempts = verification_attempts + 1, verification_attempted = 1 WHERE id = ?";
            return $this->db->query($query, [$userId]);
        } catch (Exception $e) {
            throw new Exception("Failed to increment verification attempts: " . $e->getMessage());
        }
    }

    public function markVerificationAttempted($userId) {
        try {
            $query = "UPDATE users SET verification_attempted = 1 WHERE id = ?";
            return $this->db->query($query, [$userId]);
        } catch (Exception $e) {
            throw new Exception("Failed to mark verification as attempted: " . $e->getMessage());
        }
    }


    public function getUserCampaigns($userId, $status = 'all', $sort = 'newest', $search = '') {
        try {
            $params = [$userId];
            $sql = "SELECT c.*, 
                    COALESCE(SUM(d.amount), 0) as current_amount,
                    COUNT(DISTINCT d.donor_email) as donor_count 
                    FROM campaigns c 
                    LEFT JOIN donations d ON c.id = d.campaign_id 
                    WHERE c.user_id = ?";

            if ($status !== 'all') {
                $sql .= " AND c.status = ?";
                $params[] = $status;
            }

            if ($search) {
                $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";

                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            $sql .= " GROUP BY c.id, c.title, c.description, c.goal_amount, 
                          c.image_path, c.category, c.status, c.created_at, 
                          c.user_id";

            switch ($sort) {
                case 'oldest':
                    $sql .= " ORDER BY c.created_at ASC";
                    break;
                case 'most_funded':
                    $sql .= " ORDER BY current_amount DESC";
                    break;
                case 'ending_soon':
                    $sql .= " ORDER BY c.end_date ASC";
                    break;
                default: // newest
                    $sql .= " ORDER BY c.created_at DESC";
            }

            $campaigns = $this->db->fetchAll($sql, $params);

            // Format campaign data
            foreach ($campaigns as &$campaign) {
                $campaign['donor_count'] = (int)$campaign['donor_count'];
                $campaign['current_amount'] = (float)$campaign['current_amount'];
                $campaign['goal_amount'] = (float)$campaign['goal_amount'];
                $campaign['progress'] = $campaign['goal_amount'] > 0 ? 
                    min(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 100) : 0;
            }

            return $campaigns;
        } catch (Exception $e) {
            error_log("Error in getUserCampaigns: " . $e->getMessage());
            throw new Exception("Failed to fetch user campaigns: " . $e->getMessage());
        }
    }

    public function getUserTotalDonations($userId) {
        try {
            $query = "SELECT COALESCE(SUM(d.amount), 0) as total_donations
                     FROM donations d
                     JOIN campaigns c ON d.campaign_id = c.id
                     WHERE c.user_id = ?";
            return $this->db->fetchOne($query, [$userId])['total_donations'] ?? 0;
        } catch (Exception $e) {
            throw new Exception("Failed to fetch user total donations: " . $e->getMessage());
        }
    }

    public function getBrowsableCampaigns($category = 'all', $sort = 'newest', $limit = 12, $offset = 0) {
        try {
            $params = [];
            $sql = "SELECT c.*, 
                    CASE 
                        WHEN u.account_type = 'organization' AND u.organization_name IS NOT NULL AND u.organization_name != '' 
                            THEN u.organization_name
                        ELSE u.name
                    END as creator_name,
                    COUNT(DISTINCT CASE WHEN d.status = 'completed' THEN d.id END) as donor_count,
                    COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as current_amount
                    FROM campaigns c 
                    LEFT JOIN users u ON c.user_id = u.id 
                    LEFT JOIN donations d ON c.id = d.campaign_id 
                    WHERE c.status = 'active'";

            // Only add category condition if it's not 'all'
            if ($category !== 'all') {
                $sql .= " AND c.category = ?";
                $params[] = $category;
            }

            $sql .= " GROUP BY c.id, c.title, c.description, c.goal_amount, 
                           c.image_path, c.category, c.status, c.created_at, 
                           c.user_id, u.name, u.account_type, u.organization_name";

            // Add sorting
            switch ($sort) {
                case 'most-funded':
                    $sql .= " ORDER BY current_amount DESC";
                    break;
                case 'ending-soon':
                    $sql .= " ORDER BY c.end_date ASC";
                    break;
                default: // newest
                    $sql .= " ORDER BY c.created_at DESC";
            }

            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;

            $campaigns = $this->db->fetchAll($sql, $params);

            // Format campaign data
            foreach ($campaigns as &$campaign) {
                $campaign['donor_count'] = (int)$campaign['donor_count'];
                $campaign['current_amount'] = (float)$campaign['current_amount'];
                $campaign['goal_amount'] = (float)$campaign['goal_amount'];
                $campaign['progress'] = $campaign['goal_amount'] > 0 ? 
                    min(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 100) : 0;
            }

            return $campaigns;
        } catch (Exception $e) {
            error_log("Error in getBrowsableCampaigns: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalCampaigns($search = '', $category = '') {
        try {
            $params = [];
            $sql = "SELECT COUNT(DISTINCT c.id) FROM campaigns c WHERE c.status = 'active'";
            
            if ($search) {
                $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($category) {
                $sql .= " AND c.category = ?";
                $params[] = $category;
            }
            
            return $this->db->fetchOne($sql, $params)['COUNT(DISTINCT c.id)'];
        } catch (Exception $e) {
            throw new Exception("Failed to count campaigns: " . $e->getMessage());
        }
    }

    public function getCategories() {
        try {
            return $this->db->fetchAll("SELECT * FROM categories ORDER BY name");
        } catch (Exception $e) {
            throw new Exception("Failed to fetch categories: " . $e->getMessage());
        }
    }

    public function getCampaignById($id) {
        try {
            $sql = "SELECT c.*, 
                    CASE 
                        WHEN u.account_type = 'organization' AND u.organization_name IS NOT NULL AND u.organization_name != '' 
                            THEN u.organization_name
                        ELSE u.name
                    END as creator_name,
                    COUNT(DISTINCT CASE WHEN d.status = 'completed' THEN d.id END) as donor_count,
                    COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as current_amount,
                    COALESCE(SUM(CASE WHEN d.status = 'pending' THEN d.amount ELSE 0 END), 0) as pending_amount,
                    c.category as category_name
                    FROM campaigns c 
                    LEFT JOIN users u ON c.user_id = u.id 
                    LEFT JOIN donations d ON c.id = d.campaign_id 
                    WHERE c.id = ? 
                    GROUP BY c.id, c.title, c.description, c.goal_amount, 
                             c.image_path, c.category, c.status, c.created_at, 
                             c.user_id, u.name, u.account_type, u.organization_name";
            
            $campaign = $this->db->fetchOne($sql, [$id]);
            
            if (!$campaign) {
                return null;
            }

            // Format monetary values
            $campaign['current_amount'] = floatval($campaign['current_amount']);
            $campaign['pending_amount'] = floatval($campaign['pending_amount']);
            $campaign['goal_amount'] = floatval($campaign['goal_amount']);
            $campaign['donor_count'] = intval($campaign['donor_count']);
            
            return $campaign;
        } catch (Exception $e) {
            throw new Exception("Failed to fetch campaign: " . $e->getMessage());
        }
    }

    public function getDashboardStats($userId) {
        try {
            // Current period stats
            $currentStats = $this->getCurrentPeriodStats($userId);
            
            // Previous period stats for growth calculation
            $previousStats = $this->getPreviousPeriodStats($userId);
            
            // Calculate growth percentages
            $campaignsGrowth = $this->calculateGrowth(
                $previousStats['total_campaigns'], 
                $currentStats['total_campaigns']
            );
            
            $donationsGrowth = $this->calculateGrowth(
                $previousStats['total_donations'], 
                $currentStats['total_donations']
            );
            
            $donorsGrowth = $this->calculateGrowth(
                $previousStats['active_donors'], 
                $currentStats['active_donors']
            );
            
            $successGrowth = $this->calculateGrowth(
                $previousStats['success_rate'], 
                $currentStats['success_rate']
            );

            return [
                'total_campaigns' => $currentStats['total_campaigns'],
                'total_donations' => $currentStats['total_donations'],
                'active_donors' => $currentStats['active_donors'],
                'success_rate' => $currentStats['success_rate'],
                'campaigns_growth' => $campaignsGrowth,
                'donations_growth' => $donationsGrowth,
                'donors_growth' => $donorsGrowth,
                'success_growth' => $successGrowth
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to fetch dashboard stats: " . $e->getMessage());
        }
    }

    private function getCurrentPeriodStats($userId) {
        // Total campaigns
        $totalCampaigns = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM campaigns WHERE user_id = ?", 
            [$userId]
        )['count'];

        // Total donations this month
        $totalDonations = $this->db->fetchOne(
            "SELECT COALESCE(SUM(d.amount), 0) as total 
             FROM campaigns c 
             LEFT JOIN donations d ON c.id = d.campaign_id 
             WHERE c.user_id = ? 
             AND MONTH(d.created_at) = MONTH(CURRENT_DATE())
             AND YEAR(d.created_at) = YEAR(CURRENT_DATE())", 
            [$userId]
        )['total'];

        // Active donors (last 30 days)
        $activeDonors = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT COALESCE(NULLIF(d.donor_email, ''), CONCAT('donation-', d.id))) as count 
             FROM campaigns c 
             JOIN donations d ON c.id = d.campaign_id 
             WHERE c.user_id = ? 
             AND d.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", 
            [$userId]
        )['count'];

        // Success rate
        $successfulCampaigns = $this->db->fetchOne(
            "SELECT COUNT(*) as count 
             FROM campaigns c 
             WHERE user_id = ? 
             AND (SELECT COALESCE(SUM(amount), 0) FROM donations d WHERE d.campaign_id = c.id) >= c.goal_amount", 
            [$userId]
        )['count'];

        $successRate = $totalCampaigns > 0 ? ($successfulCampaigns / $totalCampaigns) * 100 : 0;

        return [
            'total_campaigns' => (int)$totalCampaigns,
            'total_donations' => (float)$totalDonations,
            'active_donors' => (int)$activeDonors,
            'success_rate' => round($successRate)
        ];
    }

    private function getPreviousPeriodStats($userId) {
        // Previous month's stats
        $previousTotalCampaigns = $this->db->fetchOne(
            "SELECT COUNT(*) as count 
             FROM campaigns 
             WHERE user_id = ? 
             AND created_at <= DATE_SUB(NOW(), INTERVAL 1 MONTH)", 
            [$userId]
        )['count'];

        $previousDonations = $this->db->fetchOne(
            "SELECT COALESCE(SUM(d.amount), 0) as total 
             FROM campaigns c 
             LEFT JOIN donations d ON c.id = d.campaign_id 
             WHERE c.user_id = ? 
             AND MONTH(d.created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
             AND YEAR(d.created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))", 
            [$userId]
        )['total'];

        $previousDonors = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT COALESCE(NULLIF(d.donor_email, ''), CONCAT('donation-', d.id))) as count 
             FROM campaigns c 
             JOIN donations d ON c.id = d.campaign_id 
             WHERE c.user_id = ? 
             AND d.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)", 
            [$userId]
        )['count'];

        // Previous success rate
        $previousSuccessful = $this->db->fetchOne(
            "SELECT COUNT(*) as count 
             FROM campaigns c 
             WHERE user_id = ? 
             AND created_at <= DATE_SUB(NOW(), INTERVAL 1 MONTH)
             AND (SELECT COALESCE(SUM(amount), 0) FROM donations d WHERE d.campaign_id = c.id) >= c.goal_amount", 
            [$userId]
        )['count'];

        $previousSuccessRate = $previousTotalCampaigns > 0 ? 
            ($previousSuccessful / $previousTotalCampaigns) * 100 : 0;

        return [
            'total_campaigns' => (int)$previousTotalCampaigns,
            'total_donations' => (float)$previousDonations,
            'active_donors' => (int)$previousDonors,
            'success_rate' => round($previousSuccessRate)
        ];
    }

    private function calculateGrowth($previous, $current) {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100);
    }

    public function getRecentCampaigns($userId, $limit = 5) {
        try {
            $sql = "SELECT 
                c.*,
                COALESCE(SUM(d.amount), 0) as current_amount,
                COUNT(DISTINCT COALESCE(NULLIF(d.donor_email, ''), CONCAT('donation-', d.id))) as donors,
                c.category as name,
                (COALESCE(SUM(d.amount), 0) / c.goal_amount * 100) as progress,
                DATE_FORMAT(c.end_date, '%b %d, %Y') as formatted_end_date
            FROM campaigns c 
            LEFT JOIN donations d ON c.id = d.campaign_id 
            WHERE c.user_id = ? 
            GROUP BY c.id 
            ORDER BY c.created_at DESC 
            LIMIT ?";

            $campaigns = $this->db->fetchAll($sql, [$userId, (int)$limit]);

            // Format the data
            foreach ($campaigns as &$campaign) {
                $campaign['progress'] = min(round($campaign['progress'] ?? 0), 100);
                $campaign['donors'] = (int)$campaign['donors'];
                $campaign['current_amount'] = (float)$campaign['current_amount'];
                $campaign['name'] = $campaign['category'] ?? 'Uncategorized';
            }

            return $campaigns;
        } catch (Exception $e) {
            throw new Exception("Failed to fetch recent campaigns: " . $e->getMessage());
        }
    }

    public function getTrendingCampaigns($limit = 6) {
        try {
            $sql = "SELECT c.*, 
                    CASE 
                        WHEN u.account_type = 'organization' AND u.organization_name IS NOT NULL AND u.organization_name != '' 
                            THEN u.organization_name
                        ELSE u.name
                    END as creator_name,
                    COUNT(DISTINCT CASE WHEN d.status = 'completed' THEN d.id END) as donor_count,
                    COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as current_amount,
                    COALESCE(MAX(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as biggest_donation
                    FROM campaigns c 
                    LEFT JOIN users u ON c.user_id = u.id 
                    LEFT JOIN donations d ON c.id = d.campaign_id 
                    WHERE c.status = 'active' 
                    AND c.end_date > NOW()
                    GROUP BY c.id, c.title, c.description, c.goal_amount, 
                             c.image_path, c.category, c.status, c.created_at, 
                             c.user_id, u.name, u.account_type, u.organization_name
                    ORDER BY biggest_donation DESC, current_amount DESC, donor_count DESC, c.created_at DESC 
                    LIMIT ?";
            
            $campaigns = $this->db->fetchAll($sql, [$limit]);
            
            // Format campaign data
            foreach ($campaigns as &$campaign) {
                $campaign['donor_count'] = (int)$campaign['donor_count'];
                $campaign['current_amount'] = (float)$campaign['current_amount'];
                $campaign['goal_amount'] = (float)$campaign['goal_amount'];
                $campaign['biggest_donation'] = (float)$campaign['biggest_donation'];
                $campaign['progress'] = $campaign['goal_amount'] > 0 ? 
                    min(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 100) : 0;
            }
            
            return $campaigns;
        } catch (Exception $e) {
            error_log("Failed to fetch trending campaigns: " . $e->getMessage());
            return [];
        }
    }

    public function getPlatformStats() {
        try {
            // Get total raised from completed donations only
            $totalRaised = $this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM donations 
                 WHERE status = 'completed'"
            )['total'];

            // Get total active campaigns
            $totalCampaigns = $this->db->fetchOne(
                "SELECT COUNT(*) as count 
                 FROM campaigns 
                 WHERE status = 'active'"
            )['count'];

            // Get total unique donors from completed donations
            $totalDonors = $this->db->fetchOne(
                "SELECT COUNT(DISTINCT donor_email) as count 
                 FROM donations 
                 WHERE status = 'completed' 
                 AND donor_email IS NOT NULL"
            )['count'];

            return [
                'total_raised' => floatval($totalRaised),
                'total_campaigns' => intval($totalCampaigns),
                'total_donors' => intval($totalDonors)
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to fetch platform stats: " . $e->getMessage());
        }
    }

    private function generateShareCode() {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $code = '';
        for ($i = 0; $i < 7; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $code;
    }

    private function generateAttributionId() {
        return 'sl:' . bin2hex(random_bytes(16));
    }

    public function createCampaign($data) {
        // Generate unique share code
        do {
            $shareCode = $this->generateShareCode();
            $checkStmt = $this->db->prepare("SELECT id FROM campaigns WHERE share_code = ?");
            $checkStmt->execute([$shareCode]);
        } while ($checkStmt->fetch());

        $attributionId = $this->generateAttributionId();
        $timestamp = time();

        $sql = "INSERT INTO campaigns (title, description, goal_amount, category, 
                image_path, user_id, share_code, created_timestamp, attribution_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['goal_amount'],
                $data['category'],
                $data['image_path'],
                $data['user_id'],
                $shareCode,
                $timestamp,
                $attributionId
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function updateSharingFields($campaignId) {
        try {
            // Fetch the campaign to check existing share code
            $campaign = $this->db->fetchOne(
                "SELECT share_code, attribution_id FROM campaigns WHERE id = ?",
                [$campaignId]
            );

            if (!$campaign) {
                throw new Exception("Campaign not found");
            }

            $shareCode = $campaign['share_code'];
            $attributionId = $campaign['attribution_id'];

            // Update the campaign with new share code and attribution ID
            $sql = "UPDATE campaigns 
                    SET share_code = ?, attribution_id = ?
                    WHERE id = ?";

            $params = [
                $shareCode,
                $attributionId,
                $campaignId
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } catch (Exception $e) {
            throw new Exception("Failed to update sharing fields: " . $e->getMessage());
        }
    }
    
    public function generateShareUrl($campaignId, $title) {
        $cleanTitle = $this->cleanUrlTitle($title);
        return sprintf(
            "%s/campaigns/public-view.php?id=%d/%s/o?lang=en_US&utm_source=share",
            BASE_URL,
            $campaignId,
            $cleanTitle
        );
    }

    private function cleanUrlTitle($title) {
        $title = strtolower($title);
        $title = preg_replace('/[^a-z0-9]+/', '-', $title);
        $title = trim($title, '-');
        return $title;
    }
    
public function getActiveCampaigns($limit = 10) {
    try {
        $sql = "SELECT c.*, 
                CASE 
                    WHEN u.account_type = 'organization' AND u.organization_name IS NOT NULL AND u.organization_name != '' 
                        THEN u.organization_name
                    ELSE u.name
                END as creator_name,
                COUNT(DISTINCT CASE WHEN d.status = 'completed' THEN d.id END) as donor_count,
                COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as current_amount
                FROM campaigns c 
                LEFT JOIN users u ON c.user_id = u.id 
                LEFT JOIN donations d ON c.id = d.campaign_id 
                WHERE c.status = 'active'
                GROUP BY c.id, c.title, c.description, c.goal_amount, 
                         c.image_path, c.category, c.status, c.created_at, 
                         c.user_id, u.name, u.account_type, u.organization_name 
                ORDER BY current_amount DESC 
                LIMIT ?";

        $params = [(int)$limit];
        $campaigns = $this->db->fetchAll($sql, $params);

        // Format campaign data
        foreach ($campaigns as &$campaign) {
            $campaign['donor_count'] = (int)$campaign['donor_count'];
            $campaign['current_amount'] = (float)$campaign['current_amount'];
            $campaign['goal_amount'] = (float)$campaign['goal_amount'];
            $campaign['progress'] = $campaign['goal_amount'] > 0 ? 
                min(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 100) : 0;
        }

        return $campaigns;
    } catch (Exception $e) {
        error_log("Error in getActiveCampaigns: " . $e->getMessage());
        throw $e;
    }
}

    public function getDB() {
        return $this->db;
    }

public function getAllActiveCampaigns($page = 1, $perPage = 12, $filters = []) {
    try {
        // Build base query
        $sql = "SELECT c.*, 
                CASE 
                    WHEN u.account_type = 'organization' AND u.organization_name IS NOT NULL AND u.organization_name != '' 
                        THEN u.organization_name
                    ELSE u.name
                END as creator_name,
                COUNT(DISTINCT CASE WHEN d.status = 'completed' THEN d.id END) as donor_count,
                COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as current_amount
                FROM campaigns c 
                LEFT JOIN users u ON c.user_id = u.id 
                LEFT JOIN donations d ON c.id = d.campaign_id 
                WHERE c.status = 'active'";

        $params = [];

        // Add filters if provided
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $sql .= " AND c.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        // Group by clause
        $sql .= " GROUP BY c.id, c.title, c.description, c.goal_amount, 
                         c.image_path, c.category, c.status, c.created_at, 
                         c.user_id, u.name, u.account_type, u.organization_name";

        // Add sorting
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'most-funded':
                $sql .= " ORDER BY current_amount DESC";
                break;
            case 'ending-soon':
                $sql .= " ORDER BY c.end_date ASC";
                break;
            default: // newest
                $sql .= " ORDER BY c.created_at DESC";
        }

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = (int)$perPage;
        $params[] = (int)$offset;

        $campaigns = $this->db->fetchAll($sql, $params);

        // Format campaign data
        foreach ($campaigns as &$campaign) {
            $campaign['donor_count'] = (int)$campaign['donor_count'];
            $campaign['current_amount'] = (float)$campaign['current_amount'];
            $campaign['goal_amount'] = (float)$campaign['goal_amount'];
            $campaign['progress'] = $campaign['goal_amount'] > 0 ? 
                min(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 100) : 0;
            
            // Add formatted dates
            $campaign['created_at_formatted'] = date('M j, Y', strtotime($campaign['created_at']));
            $campaign['end_date_formatted'] = !empty($campaign['end_date']) ? 
                date('M j, Y', strtotime($campaign['end_date'])) : null;
        }

        return [
            'campaigns' => $campaigns,
            'total' => $this->getTotalCampaigns($filters['search'] ?? '', $filters['category'] ?? ''),
            'page' => $page,
            'per_page' => $perPage
        ];
    } catch (Exception $e) {
        error_log("Error in getAllActiveCampaigns: " . $e->getMessage());
        throw $e;
    }
}

public function getUserStats($userId) {
    try {
        // Get total raised for user's campaigns
        $totalRaised = $this->db->fetchOne(
            "SELECT COALESCE(SUM(d.amount), 0) as total 
             FROM campaigns c 
             LEFT JOIN donations d ON c.id = d.campaign_id 
             WHERE c.user_id = ? AND d.status = 'completed'",
            [$userId]
        )['total'] ?? 0;

        // Get total campaigns for user
        $totalCampaigns = $this->db->fetchOne(
            "SELECT COUNT(*) as total 
             FROM campaigns 
             WHERE user_id = ?",
            [$userId]
        )['total'] ?? 0;

        // Get total unique donors for user's campaigns
        $totalDonors = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT d.donor_email) as total 
             FROM campaigns c 
             LEFT JOIN donations d ON c.id = d.campaign_id 
             WHERE c.user_id = ? AND d.status = 'completed'",
            [$userId]
        )['total'] ?? 0;

        // Get recent donations for user's campaigns
        $recentDonations = $this->db->fetchAll(
            "SELECT d.*, c.title as campaign_title 
             FROM donations d 
             JOIN campaigns c ON d.campaign_id = c.id 
             WHERE c.user_id = ? AND d.status = 'completed' 
             ORDER BY d.created_at DESC 
             LIMIT 5",
            [$userId]
        );

        return [
            'total_raised' => (float)$totalRaised,
            'total_campaigns' => (int)$totalCampaigns,
            'total_donors' => (int)$totalDonors,
            'recent_donations' => $recentDonations
        ];
    } catch (Exception $e) {
        error_log("Error getting user stats: " . $e->getMessage());
        throw new Exception("Failed to fetch user statistics");
    }
}

public function getFormattedCampaigns() {
    try {
        // Direct database query to get all campaign data
        $sql = "SELECT 
                c.*,
                CASE 
                    WHEN u.account_type = 'organization' AND u.organization_name IS NOT NULL AND u.organization_name != '' 
                        THEN u.organization_name
                    ELSE u.name
                END as creator_name,
                COUNT(DISTINCT CASE WHEN d.status = 'completed' THEN d.id END) as donor_count,
                COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as current_amount
                FROM campaigns c 
                LEFT JOIN users u ON c.user_id = u.id 
                LEFT JOIN donations d ON c.id = d.campaign_id 
                WHERE c.status = 'active'
                GROUP BY c.id, c.title, c.description, c.goal_amount, 
                         c.image_path, c.category, c.status, c.created_at, 
                         c.user_id, u.name, u.account_type, u.organization_name 
                ORDER BY c.created_at DESC";

        $campaigns = $this->db->fetchAll($sql);

        // Format each campaign with proper type casting
        $formattedCampaigns = array_map(function($campaign) {
            // Calculate progress percentage
            $goalAmount = (float)($campaign['goal_amount'] ?? 0);
            $currentAmount = (float)($campaign['current_amount'] ?? 0);
            $progress = $goalAmount > 0 ? min(($currentAmount / $goalAmount) * 100, 100) : 0;

            return [
                'id' => (int)($campaign['id']),
                'title' => $campaign['title'],
                'description' => $campaign['description'],
                'category' => $campaign['category'],
                'goal_amount' => (float)$campaign['goal_amount'],
                'current_amount' => (float)$campaign['current_amount'],
                'donor_count' => (int)($campaign['donor_count']),
                'image_path' => $campaign['image_path'],
                'created_at' => $campaign['created_at'],
                'end_date' => $campaign['end_date'],
                'user_id' => (int)$campaign['user_id'],
                'status' => $campaign['status'],
                'creator_name' => $campaign['creator_name'],
                'progress' => round($progress),
                'formatted_amount' => number_format($currentAmount, 2),
                'formatted_goal' => number_format($goalAmount, 2),
                'created_at_formatted' => date('M j, Y', strtotime($campaign['created_at'])),
                'end_date_formatted' => !empty($campaign['end_date']) ? 
                    date('M j, Y', strtotime($campaign['end_date'])) : null
            ];
        }, $campaigns);

        return $formattedCampaigns;

    } catch (Exception $e) {
        error_log("Error in getFormattedCampaigns: " . $e->getMessage());
        return [];
    }
}

public function getBrowseCampaigns($category = 'all', $sort = '', $page = 1, $perPage = 9) {
    $params = [];
    
    $sql = "SELECT c.*, 
            COUNT(DISTINCT d.id) as donor_count,
            COALESCE(SUM(d.amount), 0) as current_amount
            FROM campaigns c
            LEFT JOIN donations d ON c.id = d.campaign_id
            WHERE c.status = 'active'";
    
    if ($category !== 'all') {
        $sql .= " AND c.category = ?";
        $params[] = $category;
    }
    
    $sql .= " GROUP BY c.id";
    
    // Add sorting
    switch($sort) {
        case 'most-funded':
            $sql .= " ORDER BY current_amount DESC";
            break;
        case 'ending-soon':
            $sql .= " ORDER BY end_date ASC";
            break;
        case 'oldest':
            $sql .= " ORDER BY c.created_at ASC";
            break;
        case 'newest':
            $sql .= " ORDER BY c.created_at DESC";
            break;
        default: // no sorting - show in database order
            // No ORDER BY clause added
    }
    
    // Only add LIMIT if perPage is reasonable (not 1000+)
    if ($perPage < 1000) {
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
    }
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/TrustRiskService.php';
    $trustRiskService = new TrustRiskService();

    foreach ($campaigns as &$campaign) {
        $campaign['owner_donation_blocked'] = false;

        if (!empty($campaign['user_id'])) {
            try {
                $profile = $trustRiskService->getUserRiskProfile((int) $campaign['user_id']);
                $campaign['owner_donation_blocked'] = !empty(($profile['restrictions'] ?? [])['receive_donations']);
            } catch (Throwable $e) {
                error_log('Unable to resolve browse campaign trust/risk state: ' . $e->getMessage());
            }
        }
    }
    unset($campaign);
    
    // Check if there are more campaigns (only if using pagination)
    $hasMore = false;
    if ($perPage < 1000) {
        $countSql = "SELECT COUNT(*) FROM campaigns WHERE status = 'active'";
        if ($category !== 'all') {
            
            $countSql .= " AND category = ?";
            $stmt = $this->db->prepare($countSql);
            $stmt->execute([$category]);
        } else {
            $stmt = $this->db->prepare($countSql);
            $stmt->execute();
        }
        $total = $stmt->fetchColumn();
        $hasMore = ($offset + $perPage) < $total;
    }
    

    return [
        'campaigns' => $campaigns,
        'hasMore' => $hasMore
    ];
}


    public function recordDonation($campaignId, $donorName, $donorEmail, $paymentDetails) {
        try {
            // Sanitize inputs
            $donorName = is_array($donorName) ? json_encode($donorName) : $donorName;
            $donorEmail = is_array($donorEmail) ? json_encode($donorEmail) : $donorEmail;

        $originalAmount = $paymentDetails['amount'] ?? 0;
        $recordedAmount = $originalAmount * 0.8;
        $status = $paymentDetails['status'] ?? 'completed';
        $transactionId = $paymentDetails['payment_id'] ?? '';
        $gatewayResponse = json_encode($paymentDetails);

        $sql = "INSERT INTO donations (
                campaign_id, donor_name, donor_email, amount, 
                original_amount, payment_gateway, status, transaction_id, 
                gateway_response, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $donationId = $this->db->insert($sql, [
            $campaignId,
            $donorName,
            $donorEmail,
            $recordedAmount,
            $originalAmount,
            'paypal',
            $status,
            $transactionId,
            $gatewayResponse
        ]);

        if ($donationId) {
            // Update campaign amount
            $this->updateCampaignAmount($campaignId, $recordedAmount);
        }

        return $donationId;
        } catch (Exception $e) {
            error_log("Error recording PayPal donation: " . $e->getMessage());
            throw $e;
        }
    }
    
    

    public function getCampaignDonors($campaignId, $limit = 10, $offset = 0) {
        try {
            $sql = "SELECT donor_name, amount, donated_at, status 
                    FROM donations 
                    WHERE campaign_id = ? AND status = 'completed' 
                    ORDER BY donated_at DESC 
                    LIMIT ? OFFSET ?";
            
            $donors = $this->db->fetchAll($sql, [$campaignId, $limit, $offset]);
            
            // Format the data
            foreach ($donors as &$donor) {
                $donor['amount'] = floatval($donor['amount']);
                $donor['donated_at_formatted'] = !empty($donor['donated_at'])
                    ? date('M j, Y', strtotime($donor['donated_at']))
                    : null;
                // Mask email for privacy
                $email = $donor['donor_email'] ?? '';
                if ($email) {
                    $parts = explode('@', $email);
                    if (count($parts) == 2) {
                        $username = $parts[0];
                        $domain = $parts[1];
                        $maskedUsername = substr($username, 0, 2) . str_repeat('*', max(0, strlen($username) - 2));
                        $donor['masked_email'] = $maskedUsername . '@' . $domain;
                    }
                }
            }
            
            return $donors;
        } catch (Exception $e) {
            error_log("Error fetching campaign donors: " . $e->getMessage());
            return [];
        }
    }

    public function getCampaignDonorCount($campaignId) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM donations 
                    WHERE campaign_id = ? AND status = 'completed'";
            
            $result = $this->db->fetchOne($sql, [$campaignId]);
            return intval($result['count']);
        } catch (Exception $e) {
            error_log("Error fetching campaign donor count: " . $e->getMessage());
            return 0;
        }
    }

    public function getCampaignUpdates($campaignId) {
        try {
            $sql = "SELECT u.*, 
                           (SELECT COUNT(*) FROM update_likes WHERE update_id = u.id) as likes_count,
                           (SELECT COUNT(*) FROM update_comments WHERE update_id = u.id) as comments_count
                    FROM campaign_updates u 
                    WHERE u.campaign_id = ? 
                    ORDER BY u.created_at DESC";
            
            $updates = $this->db->fetchAll($sql, [$campaignId]);
            return $updates;
        } catch (Exception $e) {
            error_log("Error fetching campaign updates: " . $e->getMessage());
            return [];
        }
    }

    public function addCampaignUpdate($data) {
        try {
            $sql = "INSERT INTO campaign_updates (campaign_id, user_id, title, content, image_path, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $data['campaign_id'],
                $data['user_id'],
                $data['title'],
                $data['content'],
                $data['image_path'] ?? null
            ];
            
            return $this->db->insert($sql, $params);
        } catch (Exception $e) {
            error_log("Error adding campaign update: " . $e->getMessage());
            throw new Exception("Failed to add campaign update: " . $e->getMessage());
        }
    }

public function update($campaignId, $updateData) {
        // Example PDO update logic, adjust table/fields as needed
        $db = $this->getDb(); // Assume getDb() returns PDO instance

        $fields = [];
        $params = [];
        if (isset($updateData['title'])) {
            $fields[] = 'title = :title';
            $params[':title'] = $updateData['title'];
        }
        if (isset($updateData['category'])) {
            $fields[] = 'category = :category';
            $params[':category'] = $updateData['category'];
        }
        if (isset($updateData['goal'])) {
            $fields[] = 'goal_amount = :goal';
            $params[':goal'] = $updateData['goal'];
        }
        if (isset($updateData['end_date'])) {
            $fields[] = 'end_date = :end_date';
            $params[':end_date'] = $updateData['end_date'];
        }
        if (isset($updateData['description'])) {
            $fields[] = 'description = :description';
            $params[':description'] = $updateData['description'];
        }
        if (isset($updateData['campaign_image']) && is_array($updateData['campaign_image']) && $updateData['campaign_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = 'uploads/' . uniqid() . '_' . basename($updateData['campaign_image']['name']);
            move_uploaded_file($updateData['campaign_image']['tmp_name'], __DIR__ . '/../' . $imagePath);
            $fields[] = 'image_path = :image_path';
            $params[':image_path'] = $imagePath;
        }

        if (empty($fields)) {
            throw new Exception("No data to update.");
        }

        $params[':id'] = $campaignId;
        $sql = "UPDATE campaigns SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        if (!$stmt->execute($params)) {
            throw new Exception("Failed to update campaign.");
        }
    }

    /**
     * Create a new donation record
     * @param array $data Donation data
     * @return int|false Donation ID on success, false on failure
     */
    public function createDonation($data) {
        try {
            // Apply 20% platform fee
            $originalAmount = $data['original_amount'] ?? $data['amount'];
            $recordedAmount = $originalAmount * 0.8;

            $sql = "INSERT INTO donations (
                campaign_id, donor_name, donor_email, amount, 
                original_amount, payment_gateway, status, country, postal_code, 
                comment, anonymous, mobile_money_number, transaction_id, 
                gateway_response, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['campaign_id'],
                $data['donor_name'],
                $data['donor_email'],
                $recordedAmount,
                $originalAmount,
                $data['payment_gateway'],
                $data['status'],
                $data['country'],
                $data['postal_code'],
                $data['comment'],
                $data['anonymous'],
                $data['mobile_money_number'] ?? null,
                $data['transaction_id'] ?? null,
                $data['gateway_response'] ?? null,
                $data['created_at']
            ];
            
            $donationId = $this->db->insert($sql, $params);
            
            if ($donationId) {
                // Update campaign current amount
                $this->updateCampaignAmount($data['campaign_id'], $recordedAmount);
            }
            
            return $donationId;
        } catch (Exception $e) {
            error_log("Error creating donation: " . $e->getMessage());
            throw new Exception("Failed to create donation: " . $e->getMessage());
        }
    }

    /**
     * Update campaign amount after donation
     * @param int $campaignId
     * @param float $amount
     */
    public function updateCampaignAmount($campaignId, $amount) {
        try {
            $sql = "UPDATE campaigns SET 
                    current_amount = current_amount + ?
                    WHERE id = ?";
            
            $this->db->query($sql, [$amount, $campaignId]);
        } catch (Exception $e) {
            error_log("Error updating campaign amount: " . $e->getMessage());
        }
    }



/**
     * Delete a campaign
     * @param int $campaignId
     * @return bool
     */
    public function deleteCampaign($campaignId) {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Delete related records first (foreign key constraints)
            $this->db->query("DELETE FROM campaign_updates WHERE campaign_id = ?", [$campaignId]);
            $this->db->query("DELETE FROM donations WHERE campaign_id = ?", [$campaignId]);
            
            // Delete the campaign
            $sql = "DELETE FROM campaigns WHERE id = ?";
            $this->db->query($sql, [$campaignId]);
            
            // Commit transaction
            $this->db->commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollBack();
            error_log("Error deleting campaign: " . $e->getMessage());
            throw new Exception("Failed to delete campaign: " . $e->getMessage());
        }
    }


}

class UserCampaigns {
    private $campaignModel;

    public function __construct() {
        $this->campaignModel = new Campaign();
    }

    /**
     * Get all campaigns for a specific user.
     * @param int $userId
     * @param string $status (optional)
     * @param string $sort (optional)
     * @param string $search (optional)
     * @return array
     */
    public function getAllByUser($userId, $status = 'all', $sort = 'newest', $search = '') {
        return $this->campaignModel->getUserCampaigns($userId, $status, $sort, $search);
    }
}
