<?php
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

    public function createWithdrawal($user_id, $method, $amount, $details) {
        $stmt = $this->db->prepare("INSERT INTO withdrawals (user_id, method, amount, details, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        return $stmt->execute([
            $user_id,
            $method,
            $amount,
            json_encode($details)
        ]);
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
        $totalDonations = $campaign->getUserTotalDonations($userId);
        $pendingWithdrawals = $this->getPendingWithdrawals($userId);
        $availableBalance = $totalDonations - $pendingWithdrawals;
        
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