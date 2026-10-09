<?php

class Database {
    private static $instance = null;
    private $connection;
    private static $config = [
        'host' => 'localhost',
        'dbname' => 'crofundo_donations',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
    private $stmt;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . self::$config['host'] . 
                  ";dbname=" . self::$config['dbname'] . 
                  ";charset=" . self::$config['charset'];
            
            $this->connection = new PDO(
                $dsn, 
                self::$config['username'], 
                self::$config['password'], 
                self::$config['options']
            );
            
            $this->connection->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    // Singleton pattern to ensure only one database connection
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Get the PDO connection
    public function getConnection() {
        return $this->connection;
    }

    // Prepare and execute a query with parameters
    public function query($sql, $params = []) {
        try {
            $this->stmt = $this->connection->prepare($sql);
            $this->stmt->execute($params);
            return $this->stmt;
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }

    // Insert a record and return the last inserted ID
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }

    // Get a single record
    public function fetchOne($query, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database query error: " . $e->getMessage());
        }
    }

    // Get multiple records
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    // Count number of rows
    public function count($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }

    // Transaction methods
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollBack() {
        return $this->connection->rollBack();
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance (must be public)
    public function __wakeup() {}

    public function getPDO() {
        return $this->connection;
    }

    /**
     * Prepare a statement for execution
     */
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    /**
     * Get the number of affected rows from the last SQL statement
     * @return int Number of affected rows
     */
    public function affectedRows() {
        if ($this->stmt) {
            return $this->stmt->rowCount();
        }
        return 0;
    }

    /**
     * Get the ID of the last inserted row
     * @return string The last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}