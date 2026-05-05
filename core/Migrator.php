<?php
// core/Migrator.php
// ============================================================================
// Migration Engine - Inti dari sistem migrasi
// Fitur: batch tracking, rollback, reset, refresh, fresh, status
// Keamanan: Safe file loading, class validation, prepared statements
// ============================================================================

class Migrator
{
    private PDO $pdo;
    private string $basePath;
    private string $migrationsDir = 'migrations';

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->pdo = Database::getConnection();
        $this->createMigrationsTable();

        // Inisialisasi Schema
        $driver = getenv('DB_DRIVER') ?: 'mysql';
        Schema::init($this->pdo, $driver);
    }

    // ========================================================================
    // MIGRATIONS TABLE MANAGEMENT
    // ========================================================================

    /**
     * Buat tabel tracking migrations (dengan batch support)
     */
    private function createMigrationsTable(): void
    {
        $driver = getenv('DB_DRIVER') ?: 'mysql';

        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) UNIQUE NOT NULL,
                batch INTEGER NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) UNIQUE NOT NULL,
                batch INT NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }

        $this->pdo->exec($sql);
    }

    /**
     * Ambil daftar migrasi yang sudah dieksekusi
     *
     * @return array
     */
    private function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Ambil batch number terakhir
     *
     * @return int
     */
    private function getLastBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT COALESCE(MAX(batch), 0) as max_batch FROM migrations");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['max_batch'];
    }

    /**
     * Ambil migrasi berdasarkan batch
     *
     * @param int $batch
     * @return array
     */
    private function getMigrationsByBatch(int $batch): array
    {
        $stmt = $this->pdo->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Ambil semua migrasi (urut dari terakhir)
     *
     * @return array
     */
    private function getAllMigrationsReverse(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // ========================================================================
    // MIGRATION COMMANDS
    // ========================================================================

    /**
     * Jalankan semua migrasi yang belum dieksekusi
     * Command: php artisan migrate
     */
    public function migrate(): void
    {
        $appliedMigrations = $this->getAppliedMigrations();
        $files = PathGuard::safeScanDir($this->basePath, $this->migrationsDir);

        // Filter: hanya file yang belum dieksekusi
        $toApply = [];
        foreach ($files as $file) {
            if (!in_array($file, $appliedMigrations, true)) {
                $toApply[] = $file;
            }
        }

        if (empty($toApply)) {
            echo Color::info("  ✓ Database sudah up-to-date. Tidak ada migrasi baru.") . "\n";
            return;
        }

        $batch = $this->getLastBatchNumber() + 1;
        $successCount = 0;
        $startTime = microtime(true);

        echo Color::header('Menjalankan Migrasi', "Batch #{$batch}");

        foreach ($toApply as $file) {
            $this->runMigrationFile($file, 'up', $batch);
            $successCount++;
        }

        $duration = round(microtime(true) - $startTime, 2);
        echo "\n" . Color::success("  ✓ $successCount migrasi berhasil dieksekusi.") .
             Color::muted(" ({$duration}s)") . "\n\n";
    }

    /**
     * Rollback migrasi terakhir (per batch)
     * Command: php artisan migrate:rollback
     *
     * @param int $steps Jumlah step yang akan di-rollback (0 = satu batch)
     */
    public function rollback(int $steps = 0): void
    {
        if ($steps > 0) {
            // Rollback berdasarkan jumlah file
            $migrations = $this->getAllMigrationsReverse();
            $toRollback = array_slice($migrations, 0, $steps);
        } else {
            // Rollback satu batch terakhir
            $lastBatch = $this->getLastBatchNumber();
            if ($lastBatch === 0) {
                echo Color::info("  ✓ Tidak ada migrasi yang bisa di-rollback.") . "\n";
                return;
            }
            $toRollback = $this->getMigrationsByBatch($lastBatch);
        }

        if (empty($toRollback)) {
            echo Color::info("  ✓ Tidak ada migrasi yang bisa di-rollback.") . "\n";
            return;
        }

        echo Color::header('Rollback Migrasi', count($toRollback) . ' file');

        $successCount = 0;
        $startTime = microtime(true);

        foreach ($toRollback as $file) {
            $this->runMigrationFile($file, 'down');
            $this->removeMigrationRecord($file);
            $successCount++;
        }

        $duration = round(microtime(true) - $startTime, 2);
        echo "\n" . Color::success("  ✓ $successCount migrasi berhasil di-rollback.") .
             Color::muted(" ({$duration}s)") . "\n\n";
    }

    /**
     * Rollback SEMUA migrasi
     * Command: php artisan migrate:reset
     */
    public function reset(): void
    {
        $migrations = $this->getAllMigrationsReverse();

        if (empty($migrations)) {
            echo Color::info("  ✓ Tidak ada migrasi yang bisa di-reset.") . "\n";
            return;
        }

        echo Color::header('Reset Semua Migrasi', count($migrations) . ' file');

        $successCount = 0;
        $startTime = microtime(true);

        foreach ($migrations as $file) {
            $this->runMigrationFile($file, 'down');
            $this->removeMigrationRecord($file);
            $successCount++;
        }

        $duration = round(microtime(true) - $startTime, 2);
        echo "\n" . Color::success("  ✓ $successCount migrasi berhasil di-reset.") .
             Color::muted(" ({$duration}s)") . "\n\n";
    }

    /**
     * Reset lalu migrate ulang
     * Command: php artisan migrate:refresh
     */
    public function refresh(): void
    {
        echo Color::badgeWarning('REFRESH') . " Mereset dan menjalankan ulang semua migrasi...\n";
        $this->reset();
        $this->migrate();
    }

    /**
     * Drop SEMUA tabel lalu migrate dari awal
     * Command: php artisan migrate:fresh
     * HATI-HATI: Menghapus semua tabel termasuk yang tidak dari migrasi!
     */
    public function fresh(): void
    {
        echo Color::badgeError('FRESH') . " Menghapus semua tabel dan migrasi dari awal...\n\n";

        Schema::dropAllTables();
        $this->createMigrationsTable();

        echo Color::success("  ✓ Semua tabel berhasil dihapus.") . "\n\n";
        $this->migrate();
    }

    /**
     * Tampilkan status semua migrasi
     * Command: php artisan migrate:status
     */
    public function status(): void
    {
        $appliedMigrations = $this->getAppliedMigrations();
        $files = PathGuard::safeScanDir($this->basePath, $this->migrationsDir);

        if (empty($files) && empty($appliedMigrations)) {
            echo Color::info("  Belum ada file migrasi.") . "\n";
            return;
        }

        echo Color::header('Status Migrasi');

        // Ambil detail migrasi dari database
        $stmt = $this->pdo->query("SELECT migration, batch, executed_at FROM migrations ORDER BY id ASC");
        $migrationDetails = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $migrationDetails[$row['migration']] = $row;
        }

        $table = new ConsoleTable();
        $table->setHeaders(['#', 'File Migrasi', 'Status', 'Batch', 'Waktu Eksekusi']);
        $table->setColumnAlign(0, 'right');
        $table->setColumnAlign(3, 'center');

        $no = 1;

        // Tampilkan semua file
        $allFiles = array_unique(array_merge($files, $appliedMigrations));
        sort($allFiles, SORT_NATURAL);

        foreach ($allFiles as $file) {
            $isApplied = in_array($file, $appliedMigrations, true);
            $status = $isApplied
                ? Color::success('✓ Ran')
                : Color::warning('○ Pending');

            $batch = isset($migrationDetails[$file]) ? $migrationDetails[$file]['batch'] : '-';
            $time = isset($migrationDetails[$file]) ? $migrationDetails[$file]['executed_at'] : '-';

            $table->addRow([$no++, $file, $status, $batch, $time]);
        }

        $table->display();

        // Ringkasan
        $ran = count($appliedMigrations);
        $pending = count(array_diff($files, $appliedMigrations));
        echo Color::muted("  Total: " . ($ran + $pending) . " | ") .
             Color::success("Ran: $ran") . Color::muted(" | ") .
             Color::warning("Pending: $pending") . "\n\n";
    }

    // ========================================================================
    // INTERNAL MIGRATION EXECUTION
    // ========================================================================

    /**
     * Jalankan satu file migrasi (up atau down)
     *
     * @param string $file Nama file migrasi
     * @param string $method 'up' atau 'down'
     * @param int|null $batch Batch number (untuk up)
     */
    private function runMigrationFile(string $file, string $method, ?int $batch = null): void
    {
        // Keamanan: Validasi nama file
        if (!Sanitizer::isValidMigrationFile($file)) {
            echo Color::error("  ✖ File migrasi tidak valid: $file") . "\n";
            return;
        }

        // Keamanan: Safe file loading
        $filePath = PathGuard::safeRequire($file, $this->basePath, $this->migrationsDir);

        if (!file_exists($filePath)) {
            echo Color::error("  ✖ File tidak ditemukan: $file") . "\n";
            return;
        }

        require_once $filePath;

        // Keamanan: Validasi nama class
        $className = pathinfo($file, PATHINFO_FILENAME);
        if (!Sanitizer::isValidClassName($className)) {
            echo Color::error("  ✖ Nama class tidak valid: $className") . "\n";
            return;
        }

        if (!class_exists($className)) {
            echo Color::error("  ✖ Class '$className' tidak ditemukan di file: $file") . "\n";
            return;
        }

        $instance = new $className();

        // Cek apakah method ada
        if (!method_exists($instance, $method)) {
            echo Color::error("  ✖ Method '$method()' tidak ditemukan di class: $className") . "\n";
            return;
        }

        $arrow = $method === 'up' ? '▲' : '▼';
        $label = $method === 'up' ? 'Migrating' : 'Rolling back';
        echo Color::muted("  $arrow $label: ") . Color::info($file) . " ... ";

        $start = microtime(true);

        try {
            $instance->$method($this->pdo);

            if ($method === 'up' && $batch !== null) {
                $this->recordMigration($file, $batch);
            }

            $duration = round((microtime(true) - $start) * 1000, 1);
            echo Color::success("DONE") . Color::muted(" ({$duration}ms)") . "\n";
        } catch (\Exception $e) {
            echo Color::error("FAIL") . "\n";
            echo Color::error("    Error: " . $e->getMessage()) . "\n";
            throw $e; // Re-throw agar proses berhenti
        }
    }

    /**
     * Catat migrasi yang berhasil ke database
     */
    private function recordMigration(string $file, int $batch): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
        $stmt->execute([$file, $batch]);
    }

    /**
     * Hapus record migrasi dari database
     */
    private function removeMigrationRecord(string $file): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$file]);
    }

    // ========================================================================
    // MAKE MIGRATION (File Generator)
    // ========================================================================

    /**
     * Buat file migrasi baru (static - TANPA koneksi database)
     * Dipanggil oleh command make:migration
     *
     * @param string $basePath Path root project
     * @param string $name Nama migrasi (contoh: create_users_table)
     * @return string Nama file yang dibuat
     */
    public static function generateMigrationFile(string $basePath, string $name): string
    {
        $name = Sanitizer::migrationName($name);

        $timestamp = date('Ymd_His');
        $className = "m{$timestamp}_{$name}";
        $fileName = $className . '.php';

        $dirPath = PathGuard::ensureDirectory($basePath, 'migrations');
        $filePath = $dirPath . DIRECTORY_SEPARATOR . $fileName;

        $template = self::buildMigrationTemplate($basePath, $className, $name);
        file_put_contents($filePath, $template);

        return $fileName;
    }

    /**
     * Buat file migrasi baru (instance method - untuk backward compat)
     *
     * @param string $name Nama migrasi (contoh: create_users_table)
     * @return string Path file yang dibuat
     */
    public function makeMigration(string $name): string
    {
        return self::generateMigrationFile($this->basePath, $name);
    }

    /**
     * Tentukan template migrasi berdasarkan nama
     */
    private static function buildMigrationTemplate(string $basePath, string $className, string $name): string
    {
        $stubDir = $basePath . DIRECTORY_SEPARATOR . 'stubs';

        // Deteksi jenis migrasi dari nama
        if (str_starts_with($name, 'create_') && str_ends_with($name, '_table')) {
            // Create table migration
            $tableName = substr($name, 7, -6); // Hapus 'create_' dan '_table'
            return self::loadStub($basePath, 'migration.create.stub', $className, $tableName);
        } elseif (str_starts_with($name, 'add_') || str_starts_with($name, 'update_') || str_starts_with($name, 'modify_')) {
            // Update table migration
            // Coba extract nama tabel
            $parts = explode('_to_', $name);
            $tableName = end($parts);
            if (str_ends_with($tableName, '_table')) {
                $tableName = substr($tableName, 0, -6);
            }
            return self::loadStub($basePath, 'migration.update.stub', $className, $tableName);
        } else {
            // Blank migration
            return self::loadStub($basePath, 'migration.blank.stub', $className, '');
        }
    }

    /**
     * Load stub template
     */
    private static function loadStub(string $basePath, string $stubName, string $className, string $tableName): string
    {
        $stubPath = $basePath . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . $stubName;

        if (file_exists($stubPath)) {
            $template = file_get_contents($stubPath);
            $template = str_replace(['{{CLASS_NAME}}', '{{TABLE_NAME}}'], [$className, $tableName], $template);
            return $template;
        }

        // Fallback: built-in template
        return self::getBuiltinTemplate($stubName, $className, $tableName);
    }

    /**
     * Template bawaan jika stub file tidak ada
     */
    private static function getBuiltinTemplate(string $type, string $className, string $tableName): string
    {
        if ($type === 'migration.create.stub') {
            return <<<PHP
<?php

class {$className}
{
    /**
     * Jalankan migrasi - Buat tabel baru
     */
    public function up(PDO \$pdo): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();

            // Tambahkan kolom di sini
            // \$table->string('name', 100);
            // \$table->string('email')->unique();
            // \$table->text('description')->nullable();
            // \$table->integer('age')->unsigned();
            // \$table->decimal('price', 10, 2)->default(0);
            // \$table->boolean('is_active')->default(true);
            // \$table->enum('status', ['active', 'inactive'])->default('active');
            // \$table->foreignId('category_id');

            \$table->timestamps();

            // Foreign Key:
            // \$table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });
    }

    /**
     * Batalkan migrasi - Hapus tabel
     */
    public function down(PDO \$pdo): void
    {
        Schema::dropIfExists('{$tableName}');
    }
}

PHP;
        }

        if ($type === 'migration.update.stub') {
            return <<<PHP
<?php

class {$className}
{
    /**
     * Jalankan migrasi - Modifikasi tabel
     */
    public function up(PDO \$pdo): void
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            // Tambah kolom baru:
            // \$table->string('phone', 20)->nullable()->after('email');

            // Tambah index:
            // \$table->index('name');
            // \$table->unique(['email', 'phone']);

            // Tambah foreign key:
            // \$table->unsignedBigInteger('role_id')->nullable();
            // \$table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    /**
     * Batalkan migrasi
     */
    public function down(PDO \$pdo): void
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            // Hapus yang ditambahkan di up():
            // \$table->dropColumn('phone');
            // \$table->dropForeign('fk_{$tableName}_role_id');
            // \$table->dropColumn('role_id');
        });
    }
}

PHP;
        }

        // Blank template
        return <<<PHP
<?php

class {$className}
{
    /**
     * Jalankan migrasi
     */
    public function up(PDO \$pdo): void
    {
        // Tulis kode migrasi di sini
        // Bisa pakai Schema Builder:
        //   Schema::create('table', function(Blueprint \$table) { ... });
        //
        // Atau raw SQL:
        //   \$pdo->exec("...");
    }

    /**
     * Batalkan migrasi
     */
    public function down(PDO \$pdo): void
    {
        // Tulis kode untuk membatalkan migrasi
    }
}

PHP;
    }
}
