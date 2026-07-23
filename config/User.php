<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Mail.php';
require_once __DIR__ . '/TrustRiskService.php';

class User {
    private $db;
    private $maxLoginAttempts = 5;
    private $lockoutTime = 900; 

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function checkLoginAttempts($email) {
        $sql = "SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt 
                FROM login_attempts 
                WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        
        $result = $this->db->fetchOne($sql, [$email]);
        
        if ($result && $result['attempts'] >= $this->maxLoginAttempts) {
            $timeSinceLastAttempt = time() - strtotime($result['last_attempt']);
            if ($timeSinceLastAttempt < $this->lockoutTime) {
                $timeLeft = ceil(($this->lockoutTime - $timeSinceLastAttempt) / 60);
                throw new Exception("For security reasons, your account has been temporarily locked. Please try again in {$timeLeft} minutes.");
            }
            
         
            $this->db->query("DELETE FROM login_attempts WHERE email = ?", [$email]);
        }
    }

    private function recordLoginAttempt($email, $success) {
        if (!$success) {
            $this->db->query(
                "INSERT INTO login_attempts (email, attempt_time) VALUES (?, NOW())",
                [$email]
            );
        } else {
            
            $this->db->query("DELETE FROM login_attempts WHERE email = ?", [$email]);
        }
    }

    public function register($data) {
        try {
            $existingUser = $this->db->fetchOne(
                "SELECT id FROM users WHERE email = ?",
                [$data['email']]
            );

            if ($existingUser) {
                throw new Exception("Email already exists");
            }

            $existingNIN = $this->db->fetchOne(
                "SELECT id FROM users WHERE nin = ?",
                [$data['nin']]
            );

            if ($existingNIN) {
                throw new Exception("National ID Number already registered");
            }

            $sql = "INSERT INTO users (name, email, password, phone, phone_country_code, nin, country, nationality, city, newsletter, status, account_type, organization_name, organization_type, registration_number, organization_website, organization_contact, is_verified, verification_token, verification_code, verification_code_expires_at, is_phone_verified, phone_verified_at, phone_risk_score, phone_verification_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, ?, 1, NULL, NULL, NULL, ?, ?, ?, ?)";
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $isPhoneVerified = !empty($data['is_phone_verified']) ? 1 : 0;
            $phoneVerificationStatus = $data['phone_verification_status'] ?? ($isPhoneVerified ? 'verified' : 'pending');
            $phoneVerifiedAt = $data['phone_verified_at'] ?? ($isPhoneVerified ? date('Y-m-d H:i:s') : null);
            
            $params = [
                $data['name'],
                $data['email'],
                $hashedPassword,
                $data['phone'],
                $data['phone_country_code'] ?? null,
                $data['nin'],
                $data['country'],
                $data['nationality'],
                $data['city'],
                $data['newsletter'] ? 1 : 0,
                $data['account_type'] ?? 'personal',
                $data['organization_name'] ?? null,
                $data['organization_type'] ?? null,
                $data['registration_number'] ?? null,
                $data['organization_website'] ?? null,
                $data['organization_contact'] ?? null,
                $isPhoneVerified,
                $phoneVerifiedAt,
                (int) ($data['phone_risk_score'] ?? 0),
                $phoneVerificationStatus
            ];
            
            $this->db->query($sql, $params);
            $userId = $this->db->lastInsertId();
            
            // Handle avatar upload if provided
            if (isset($data['avatar']) && $data['avatar']['error'] === UPLOAD_ERR_OK) {
                $this->handleAvatarUpload($userId, $data['avatar']);
            }
            
            return [
                'id' => $userId,
                'email' => $data['email']
            ];
        } catch (Exception $e) {
            throw new Exception("Registration failed: " . $e->getMessage());
        }
    }

    public function login($email, $password) {
        try {
            $this->checkLoginAttempts($email);

            $user = $this->db->fetchOne(
                "SELECT id, email, password, name, is_verified, risk_status FROM users WHERE email = ?",
                [$email]
            );

            if (!$user || !password_verify($password, $user['password'])) {
                $this->recordLoginAttempt($email, false);
                throw new Exception("The email or password you entered is incorrect. Please try again.");
            }

            $trustRisk = new TrustRiskService();
            $riskStatus = strtoupper((string) ($user['risk_status'] ?? TrustRiskService::STATUS_ACTIVE));
            if (!$trustRisk->canLogin($riskStatus)) {
                throw new Exception('Your account has been permanently disabled. Please contact support if you believe this is a mistake.');
            }

            // Check if email is verified first
            if (!$user['is_verified']) {
                // Store email in session for verification
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['verify_email'] = $user['email'];
                throw new Exception("Your email is not verified. Please check your inbox for a verification code.");
            }

            $this->recordLoginAttempt($email, true);

            // Generate 6-digit 2FA code
            $twoFactorCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store temporary data in session
            $_SESSION['2fa_user_id'] = $user['id'];
            $_SESSION['2fa_email'] = $user['email'];
            $_SESSION['2fa_user_name'] = $user['name'];
            $_SESSION['2fa_code'] = $twoFactorCode;
            $_SESSION['2fa_code_time'] = time();
            $_SESSION['2fa_attempts'] = 0;

            // Send email with 2FA code (log error but continue if it fails)
            try {
                $mailObj = new Mail();
                $mailObj->sendTwoFactorCode($user['email'], $twoFactorCode);
            } catch (Exception $mailException) {
                // Log the error but don't block login
                error_log("Failed to send 2FA email: " . $mailException->getMessage());
                // You can add a message here to let the user know if needed, but for now we'll continue
            }

            return $user;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function handleAvatarUpload($userId, $file) {
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $userId . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Failed to upload avatar");
        }

      
        $this->db->query(
            "UPDATE users SET avatar = ? WHERE id = ?",
            [$filename, $userId]
        );
    }

    public function logout() {
        session_destroy();
        session_start();
    }

    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

     
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'] ||
            $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            $this->logout();
            return false;
        }

      
        if (time() - $_SESSION['last_activity'] > 1800) {
            $this->logout();
            return false;
        }

        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $sql = "SELECT id, name, email, phone, country, city, newsletter, status, avatar, 
                created_at, updated_at, email_verified, is_verified, verification_token, risk_status, risk_status_reason, risk_review_expires_at, is_admin
                FROM users 
                WHERE id = ?";
        return $this->db->fetchOne($sql, [$_SESSION['user_id']]);
    }

    public function getUserById($userId) {
        try {
            $query = "SELECT id, name, email, phone, password, created_at FROM users WHERE id = ?";
            $user = $this->db->fetchOne($query, [$userId]);
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            return $user;
        } catch (Exception $e) {
            throw new Exception("Failed to fetch user data: " . $e->getMessage());
        }
    }

    public function updateProfile($userId, $data) {
        try {
            throw new Exception("Profile updates for identity fields are disabled");
        } catch (Exception $e) {
            error_log("Profile update blocked for user $userId: " . $e->getMessage());
            error_log("Blocked update data: " . json_encode($data));
            throw new Exception("Profile details such as name, email, and phone cannot be changed from settings.");
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $user = $this->getUserById($userId);
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $query = "UPDATE users SET password = ? WHERE id = ?";
            return $this->db->query($query, [$hashedPassword, $userId]);
        } catch (Exception $e) {
            // Log the error for debugging
            error_log("Password change failed for user $userId: " . $e->getMessage());
            throw new Exception("Failed to change password: " . $e->getMessage());
        }
    }

    public function verifyEmail($token) {
        try {
            $sql = "UPDATE users SET is_verified = 1, verification_token = NULL 
                    WHERE verification_token = ?";
            $result = $this->db->query($sql, [$token]);
            
            if ($this->db->affectedRows() > 0) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new Exception("Email verification failed: " . $e->getMessage());
        }
    }

    public function verifyEmailCode($email, $code) {
        try {
            $sql = "SELECT id, verification_code, verification_code_expires_at 
                    FROM users 
                    WHERE email = ?";
            $user = $this->db->fetchOne($sql, [$email]);
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            if (!$user['verification_code'] || $user['verification_code'] !== $code) {
                throw new Exception("Invalid verification code");
            }
            
            if (strtotime($user['verification_code_expires_at']) < time()) {
                throw new Exception("Verification code has expired");
            }
            
            // Mark email as verified
            $updateSql = "UPDATE users 
                          SET is_verified = 1, 
                              verification_token = NULL, 
                              verification_code = NULL, 
                              verification_code_expires_at = NULL 
                          WHERE id = ?";
            $this->db->query($updateSql, [$user['id']]);
            
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function resendVerificationCode($userId) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }
            
            $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $verificationExpiresAt = date('Y-m-d H:i:s', time() + 3600);
            
            $sql = "UPDATE users 
                    SET verification_code = ?, 
                        verification_code_expires_at = ? 
                    WHERE id = ?";
            $this->db->query($sql, [$verificationCode, $verificationExpiresAt, $userId]);
            
            return $verificationCode;
        } catch (Exception $e) {
            throw new Exception("Failed to resend verification code: " . $e->getMessage());
        }
    }

    public function resendVerification($userId) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

            $verificationToken = bin2hex(random_bytes(32));
            $sql = "UPDATE users SET verification_token = ? WHERE id = ?";
            $this->db->query($sql, [$verificationToken, $userId]);

            return $verificationToken;
        } catch (Exception $e) {
            throw new Exception("Failed to generate verification token: " . $e->getMessage());
        }
    }

    public function updateVerificationToken($userId, $token) {
        try {
            $sql = "UPDATE users SET verification_token = ? WHERE id = ?";
            return $this->db->query($sql, [$token, $userId]);
        } catch (Exception $e) {
            throw new Exception("Failed to update verification token: " . $e->getMessage());
        }
    }

    public function getUserByEmail($email) {
    $sql = "SELECT * FROM users WHERE email = ?";
    return $this->db->fetchOne($sql, [$email]);
}
}