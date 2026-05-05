<?php
// core/Schema/Schema.php
// ============================================================================
// Schema Facade - Static API untuk mengelola tabel database
// Ini adalah class utama yang dipanggil di file migrasi
//
// Contoh:
//   Schema::create('users', function(Blueprint $table) { ... });
//   Schema::table('users', function(Blueprint $table) { ... });
//   Schema::drop('users');
//   Schema::hasTable('users');
// ============================================================================

class Schema
{
    private static ?GrammarInterface $grammar = null;
    private static ?PDO $pdo = null;

    /**
     * Inisialisasi Schema dengan koneksi database
     *
     * @param PDO $pdo Koneksi PDO
     * @param string $driver Driver database (mysql/sqlite)
     */
    public static function init(PDO $pdo, string $driver = 'mysql'): void
    {
        self::$pdo = $pdo;

        self::$grammar = match ($driver) {
            'sqlite' => new SQLiteGrammar(),
            default  => new MySqlGrammar(),
        };
    }

    /**
     * Pastikan Schema sudah diinisialisasi
     */
    private static function ensureInitialized(): void
    {
        if (self::$pdo === null || self::$grammar === null) {
            // Auto-init jika belum
            self::$pdo = Database::getConnection();
            $driver = getenv('DB_DRIVER') ?: 'mysql';
            self::$grammar = match ($driver) {
                'sqlite' => new SQLiteGrammar(),
                default  => new MySqlGrammar(),
            };
        }
    }

    /**
     * Buat tabel baru
     *
     * @param string $table Nama tabel
     * @param callable $callback function(Blueprint $table) { ... }
     */
    public static function create(string $table, callable $callback): void
    {
        self::ensureInitialized();

        $blueprint = new Blueprint($table, true);
        $callback($blueprint);

        $statements = self::$grammar->compileCreate($blueprint);
        self::executeStatements($statements);
    }

    /**
     * Modifikasi tabel yang sudah ada
     *
     * @param string $table Nama tabel
     * @param callable $callback function(Blueprint $table) { ... }
     */
    public static function table(string $table, callable $callback): void
    {
        self::ensureInitialized();

        $blueprint = new Blueprint($table, false);
        $callback($blueprint);

        $statements = self::$grammar->compileAlter($blueprint);
        self::executeStatements($statements);
    }

    /**
     * Drop tabel (error jika tidak ada)
     *
     * @param string $table
     */
    public static function drop(string $table): void
    {
        self::ensureInitialized();
        self::$pdo->exec(self::$grammar->compileDrop($table));
    }

    /**
     * Drop tabel jika ada
     *
     * @param string $table
     */
    public static function dropIfExists(string $table): void
    {
        self::ensureInitialized();
        self::$pdo->exec(self::$grammar->compileDropIfExists($table));
    }

    /**
     * Rename tabel
     *
     * @param string $from Nama lama
     * @param string $to Nama baru
     */
    public static function rename(string $from, string $to): void
    {
        self::ensureInitialized();
        self::$pdo->exec(self::$grammar->compileRename($from, $to));
    }

    /**
     * Cek apakah tabel ada
     *
     * @param string $table
     * @return bool
     */
    public static function hasTable(string $table): bool
    {
        self::ensureInitialized();
        $sql = self::$grammar->compileHasTable($table);
        $stmt = self::$pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0) > 0;
    }

    /**
     * Cek apakah kolom ada di tabel
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function hasColumn(string $table, string $column): bool
    {
        self::ensureInitialized();
        $driver = getenv('DB_DRIVER') ?: 'mysql';

        if ($driver === 'sqlite') {
            $sql = self::$grammar->compileHasColumn($table, $column);
            $stmt = self::$pdo->query($sql);
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                if ($col['name'] === $column) {
                    return true;
                }
            }
            return false;
        }

        $sql = self::$grammar->compileHasColumn($table, $column);
        $stmt = self::$pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0) > 0;
    }

    /**
     * Ambil daftar kolom dari tabel
     *
     * @param string $table
     * @return array
     */
    public static function getColumnListing(string $table): array
    {
        self::ensureInitialized();
        $driver = getenv('DB_DRIVER') ?: 'mysql';

        $sql = self::$grammar->compileGetColumnListing($table);
        $stmt = self::$pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($driver === 'sqlite') {
            return array_column($rows, 'name');
        }

        return array_column($rows, 'COLUMN_NAME');
    }

    /**
     * Drop semua tabel di database
     * HATI-HATI: Ini akan menghapus SEMUA tabel!
     */
    public static function dropAllTables(): void
    {
        self::ensureInitialized();
        $driver = getenv('DB_DRIVER') ?: 'mysql';

        if ($driver === 'mysql') {
            // Disable foreign key checks sementara
            self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            $sql = self::$grammar->compileDropAllTables();
            $stmt = self::$pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                self::$pdo->exec($row['query']);
            }

            self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } else {
            $sql = self::$grammar->compileDropAllTables();
            $stmt = self::$pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                self::$pdo->exec($row['query']);
            }
        }
    }

    /**
     * Truncate tabel (hapus semua data, reset auto increment)
     *
     * @param string $table
     */
    public static function truncate(string $table): void
    {
        self::ensureInitialized();
        self::$pdo->exec(self::$grammar->compileTruncate($table));
    }

    /**
     * Disable foreign key checks (berguna saat seeding/migration)
     */
    public static function disableForeignKeyConstraints(): void
    {
        self::ensureInitialized();
        $driver = getenv('DB_DRIVER') ?: 'mysql';

        if ($driver === 'mysql') {
            self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        } else {
            self::$pdo->exec('PRAGMA foreign_keys = OFF');
        }
    }

    /**
     * Enable foreign key checks
     */
    public static function enableForeignKeyConstraints(): void
    {
        self::ensureInitialized();
        $driver = getenv('DB_DRIVER') ?: 'mysql';

        if ($driver === 'mysql') {
            self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } else {
            self::$pdo->exec('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * Mendapatkan koneksi PDO
     *
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        self::ensureInitialized();
        return self::$pdo;
    }

    /**
     * Mendapatkan grammar yang dipakai
     *
     * @return GrammarInterface
     */
    public static function getGrammar(): GrammarInterface
    {
        self::ensureInitialized();
        return self::$grammar;
    }

    /**
     * Eksekusi array of SQL statements
     *
     * @param array $statements
     * @throws \RuntimeException
     */
    private static function executeStatements(array $statements): void
    {
        foreach ($statements as $sql) {
            try {
                self::$pdo->exec($sql);
            } catch (\PDOException $e) {
                throw new \RuntimeException(
                    "SQL Error: " . $e->getMessage() . "\nQuery: " . $sql
                );
            }
        }
    }
}
