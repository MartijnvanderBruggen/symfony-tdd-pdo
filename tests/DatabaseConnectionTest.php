<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function testDatabaseExistsAndCanBeAccessedWithPdo(): void
    {
        $dsn = getenv('TEST_DATABASE_DSN') ?: 'mysql:host=localhost;dbname=test_db';
        $user = getenv('TEST_DATABASE_USER') ?: 'root';
        $password = getenv('TEST_DATABASE_PASSWORD') ?: '';

        try {
            $pdo = new \PDO($dsn, $user, $password);
            $this->assertInstanceOf(\PDO::class, $pdo);
        } catch (\PDOException $e) {
            $this->fail('Could not connect to the database: ' . $e->getMessage());
        }
    }

    public function testDatabaseHasUsersTable(): void
    {
        $dsn = getenv('TEST_DATABASE_DSN') ?: 'mysql:host=localhost;dbname=test_db';
        $user = getenv('TEST_DATABASE_USER') ?: 'root';
        $password = getenv('TEST_DATABASE_PASSWORD') ?: '';

        try {
            $pdo = new \PDO($dsn, $user, $password);
            $result = $pdo->query("SHOW TABLES LIKE 'users'");
            $tableExists = $result && $result->fetch() !== false;
            $this->assertTrue($tableExists, "Table 'users' does not exist in the database.");
        } catch (\PDOException $e) {
            $this->fail('Could not connect to the database: ' . $e->getMessage());
        }
    }
}
