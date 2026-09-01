<?php
require_once __DIR__ . '/TrustRiskService.php';
if (!class_exists('Campaign', false)) {
    require_once __DIR__ . '/Campaign.php';
}

class Withdrawal {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $this->db = Database::getInstance()->getConnection();
    }

    public function getPendingWithdrawals($userId) {
        try {
            $query = "SELECT COALESCE(SUM(amount), 0) as pending_amount 
                     FROM withdrawals 
                     WHERE user_id = ? AND status = 'pending'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['pending_amount'] ?? 0;
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch pending withdrawals: " . $e->getMessage());
        }
    }

    private function normalizeWithdrawalDetails(array $details): string {
        ksort($details);
        return json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    public function hasRecentPendingWithdrawalMatch(int $userId, string $method, float $amount, array $details): bool {
        $detailsJson = $this->normalizeWithdrawalDetails($details);
        $stmt = $this->db->prepare(
            "SELECT details FROM withdrawals
             WHERE user_id = ?
             AND method = ?
             AND amount = ?
             AND status = 'pending'
             AND created_at >= NOW() - INTERVAL 10 MINUTE
             ORDER BY created_at DESC"
        );
        $stmt->execute([$userId, $method, $amount]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (md5((string) $row['details']) === md5($detailsJson)) {
                return true;
            }
        }

        return false;
    }

    public function createWithdrawal($user_id, $method, $amount, $details) {
        $trustRisk = new TrustRiskService();
        $trustRisk->enforceForAction((int) $user_id, 'withdraw_funds');

        if ($this->hasRecentPendingWithdrawalMatch((int) $user_id, (string) $method, (float) $amount, $details)) {
            return false;
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("INSERT INTO withdrawals (user_id, method, amount, details, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $result = $stmt->execute([
                $user_id,
                $method,
                $amount,
                $this->normalizeWithdrawalDetails($details)
            ]);
            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getUserWithdrawalHistory($userId) {
        try {
            $query = "SELECT * FROM withdrawals 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch withdrawal history: " . $e->getMessage());
        }
    }

    public function getWithdrawalAttempts($userId, $hours) {
        try {
            $query = "SELECT COUNT(*) FROM withdrawals 
                     WHERE user_id = ? 
                     AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $hours]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Failed to check withdrawal attempts: " . $e->getMessage());
        }
    }

    public function validateWithdrawal($userId, $amount, $method) {
        if ($amount < MIN_WITHDRAWAL_AMOUNT) {
            throw new Exception("Minimum withdrawal amount is $" . MIN_WITHDRAWAL_AMOUNT);
        }
        
        // Check available balance
        $campaign = new Campaign();
        $withdrawableDonations = $campaign->getUserWithdrawableDonations($userId);
        $totalWithdrawn = $this->getTotalApprovedWithdrawals($userId);
        $pendingWithdrawals = $this->getPendingWithdrawals($userId);
        $availableBalance = $withdrawableDonations - $totalWithdrawn - $pendingWithdrawals;
        
        if ($amount > $availableBalance) {
            throw new Exception("Insufficient balance");
        }
        
        // Check withdrawal attempts
        $attempts = $this->getWithdrawalAttempts($userId, 24);
        if ($attempts >= MAX_WITHDRAWAL_ATTEMPTS) {
            throw new Exception("Maximum withdrawal attempts reached");
        }
        
        return true;
    }

    public function getPendingWithdrawalsList($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM withdrawals WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalApprovedWithdrawals($user_id) {
    $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM withdrawals WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return floatval($row['total'] ?? 0);
}

    public function deleteWithdrawal($withdrawal_id, $user_id) {
        try {
            // First check if the withdrawal exists and belongs to the user and is pending
            $checkStmt = $this->db->prepare("SELECT id FROM withdrawals WHERE id = ? AND user_id = ? AND status = 'pending'");
            $checkStmt->execute([$withdrawal_id, $user_id]);
            
            if (!$checkStmt->fetch()) {
                return false; // Withdrawal doesn't exist, doesn't belong to user, or is not pending
            }
            
            // Delete the withdrawal
            $deleteStmt = $this->db->prepare("DELETE FROM withdrawals WHERE id = ? AND user_id = ? AND status = 'pending'");
            return $deleteStmt->execute([$withdrawal_id, $user_id]);
        } catch (PDOException $e) {
            throw new Exception("Failed to delete withdrawal: " . $e->getMessage());
        }
    }

    public function getWithdrawalById($withdrawal_id, $user_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM withdrawals WHERE id = ? AND user_id = ?");
            $stmt->execute([$withdrawal_id, $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch withdrawal: " . $e->getMessage());
        }
    }
}