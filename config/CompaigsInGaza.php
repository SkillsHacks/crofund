<?php

class CampaignsInGaza
{
    private PDO $pdo;

    public function __construct()
    {
        // Adjust these constants or replace with your config values
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch a campaign by its ID.
     * @param int $campaignId
     * @return array|null Returns associative array of campaign data or null if not found
     */
    public function getById(int $campaignId): ?array
    {
        $sql = "SELECT id, user_id, title, category, goal_amount, current_amount, end_date, description, image_path 
                FROM campaigns 
                WHERE id = :id 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $campaignId]);
        $campaign = $stmt->fetch();

        if (!$campaign) {
            return null;
        }

        return $campaign;
    }
}
