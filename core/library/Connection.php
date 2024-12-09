<?php

namespace core\library;

use PDOException;
use PDO;

class Connection
{
    private ?PDO $conn = null;
    public function __construct(
        private string $host,
        private int $port,
        private string $dbname,
        private string $username,
        private string $password
    ) {}

    public function connect(): PDO | false
    {
        try {
            if (!$this->conn) {
                $this->conn = new PDO(
                    "mysql:host={$this->host};port={$this->port};dbname={$this->dbname}",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
                    ]
                );
            }
            return $this->conn;
        } catch (PDOException $e) {
            return false;
        }
    }
}
