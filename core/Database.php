<?php
// core/Database.php
// ============================================================================
// Database Connection Manager
// Mendukung: MySQL, SQLite | Singleton Pattern | Connection Pooling
// Keamanan: Prepared Statements, Error Mode Exception
// ============================================================================

class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    /**
     * Mendapatkan koneksi database (Singleton)
     *
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            self::$driver = strtolower(getenv('DB_DRIVER') ?: 'mysql');

            self::$pdo = match (self::$driver) {
                'sqlite' => self::connectSQLite(),
                default  => self::connectMySQL(),
            };

            // ─── Konfigurasi PDO yang aman ──────────────────────────────
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Gunakan prepared statements native

            if (self::$driver === 'mysql') {
                // Set charset ke utf8mb4 (mendukung emoji)
                self::$pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
                // Set timezone
                self::$pdo->exec("SET time_zone = '+07:00'");
                // Set SQL mode yang strict
                self::$pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            }
        }

        return self::$pdo;
    }

    /**
     * Koneksi ke MySQL/MariaDB
     */
    private static function connectMySQL(): PDO
    {
        $host   = getenv('DB_HOST') ?: '127.0.0.1';
        $port   = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_DATABASE');
        $user   = getenv('DB_USERNAME');
        $pass   = getenv('DB_PASSWORD') ?: '';

        // Validasi
        if (!$dbname || !$user) {
            throw new \RuntimeException(
                "Error: DB_DATABASE atau DB_USERNAME kosong!\n" .
                "Pastikan sudah diisi di file .env\n"
            );
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_TIMEOUT            => 5,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Koneksi MySQL Gagal: " . $e->getMessage() . "\n" .
                "Pastikan MySQL Anda menyala dan database '$dbname' sudah ada.\n" .
                "Host: $host:$port | User: $user\n"
            );
        }
    }

    /**
     * Koneksi ke SQLite
     */
    private static function connectSQLite(): PDO
    {
        $path = getenv('DB_DATABASE') ?: 'database.sqlite';

        // Jika path relatif, resolve dari basePath
        if (!str_starts_with($path, '/') && !str_starts_with($path, '\\') && $path[1] !== ':') {
            $path = Bootstrap::getBasePath() . DIRECTORY_SEPARATOR . $path;
        }

        try {
            $pdo = new PDO("sqlite:{$path}");
            // Enable foreign keys di SQLite (off by default!)
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA journal_mode = WAL');
            return $pdo;
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Koneksi SQLite Gagal: " . $e->getMessage() . "\n" .
                "Path: $path\n"
            );
        }
    }

    /**
     * Mendapatkan driver yang sedang dipakai
     *
     * @return string
     */
    public static function getDriver(): string
    {
        return self::$driver;
    }

    /**
     * Tutup koneksi database
     */
    public static function disconnect(): void
    {
        self::$pdo = null;
    }

    /**
     * Cek apakah sudah terkoneksi
     *
     * @return bool
     */
    public static function isConnected(): bool
    {
        return self::$pdo !== null;
    }

    /**
     * Eksekusi raw query dengan prepared statement (AMAN dari SQL Injection)
     *
     * @param string $sql Query SQL dengan placeholder ?
     * @param array $bindings Parameter yang akan di-bind
     * @return \PDOStatement
     */
    public static function query(string $sql, array $bindings = []): \PDOStatement
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    /**
     * Insert data dengan prepared statement
     *
     * @param string $table Nama tabel
     * @param array $data Associative array [column => value]
     * @return int Last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $table = Sanitizer::tableName($table);
        $columns = array_keys($data);

        // Validasi nama kolom
        foreach ($columns as $col) {
            Sanitizer::columnName($col);
        }

        $grammar = Schema::getGrammar();
        $wrappedTable = $grammar->wrapIdentifier($table);
        $wrappedColumns = array_map([$grammar, 'wrapIdentifier'], $columns);
        $placeholders = array_fill(0, count($data), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $wrappedTable,
            implode(', ', $wrappedColumns),
            implode(', ', $placeholders)
        );

        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return (int)$pdo->lastInsertId();
    }

    /**
     * Insert multiple rows
     *
     * @param string $table
     * @param array $columns
     * @param array $rows Array of arrays
     * @return int Jumlah row yang berhasil di-insert
     */
    public static function insertMany(string $table, array $columns, array $rows): int
    {
        $table = Sanitizer::tableName($table);
        foreach ($columns as $col) {
            Sanitizer::columnName($col);
        }

        $grammar = Schema::getGrammar();
        $wrappedTable = $grammar->wrapIdentifier($table);
        $wrappedColumns = array_map([$grammar, 'wrapIdentifier'], $columns);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $pdo = self::getConnection();
        $count = 0;

        // Batch insert untuk performa
        $batchSize = 100;
        $batches = array_chunk($rows, $batchSize);

        foreach ($batches as $batch) {
            $allPlaceholders = implode(', ', array_fill(0, count($batch), $placeholders));
            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES %s',
                $wrappedTable,
                implode(', ', $wrappedColumns),
                $allPlaceholders
            );

            $values = [];
            foreach ($batch as $row) {
                foreach ($row as $value) {
                    $values[] = $value;
                }
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $count += $stmt->rowCount();
        }

        return $count;
    }

    /**
     * Jalankan callback dalam database transaction
     *
     * @param callable $callback
     * @return mixed
     * @throws \Throwable
     */
    public static function transaction(callable $callback)
    {
        $pdo = self::getConnection();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
