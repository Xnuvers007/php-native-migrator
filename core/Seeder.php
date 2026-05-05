<?php
// core/Seeder.php
// ============================================================================
// Database Seeder Engine
// Menjalankan seeder untuk mengisi data awal ke database
// Keamanan: Semua insert menggunakan prepared statements
// ============================================================================

class Seeder
{
    private PDO $pdo;
    private string $basePath;
    private string $seedersDir = 'seeders';

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->pdo = Database::getConnection();

        // Inisialisasi Schema
        $driver = getenv('DB_DRIVER') ?: 'mysql';
        Schema::init($this->pdo, $driver);
    }

    /**
     * Jalankan seeder
     *
     * @param string|null $className Nama class seeder (null = DatabaseSeeder)
     */
    public function run(?string $className = null): void
    {
        $className = $className ?? 'DatabaseSeeder';

        echo Color::header('Menjalankan Seeder');

        $startTime = microtime(true);

        $this->runSeeder($className);

        $duration = round(microtime(true) - $startTime, 2);
        echo "\n" . Color::success("  ✓ Seeding selesai.") .
             Color::muted(" ({$duration}s)") . "\n\n";
    }

    /**
     * Jalankan satu class seeder
     *
     * @param string $className
     */
    private function runSeeder(string $className): void
    {
        // Keamanan: Validasi nama class
        if (!Sanitizer::isValidClassName($className)) {
            throw new \InvalidArgumentException("Nama class seeder tidak valid: $className");
        }

        // Cari file seeder
        $fileName = $className . '.php';
        $filePath = PathGuard::safeRequire($fileName, $this->basePath, $this->seedersDir);

        if (!file_exists($filePath)) {
            throw new \RuntimeException("File seeder tidak ditemukan: $fileName");
        }

        require_once $filePath;

        if (!class_exists($className)) {
            throw new \RuntimeException("Class '$className' tidak ditemukan di file: $fileName");
        }

        $instance = new $className();

        if (!method_exists($instance, 'run')) {
            throw new \RuntimeException("Method 'run()' tidak ditemukan di class: $className");
        }

        echo Color::muted("  ● Seeding: ") . Color::info($className) . " ... ";

        try {
            $instance->run($this->pdo);
            echo Color::success("DONE") . "\n";
        } catch (\Exception $e) {
            echo Color::error("FAIL") . "\n";
            echo Color::error("    Error: " . $e->getMessage()) . "\n";
            throw $e;
        }
    }

    /**
     * Dipanggil dari dalam seeder untuk menjalankan seeder lain
     * Mirip $this->call() di Laravel
     *
     * @param string|array $classes Nama class seeder
     */
    public function call($classes): void
    {
        $classes = (array)$classes;
        foreach ($classes as $className) {
            $this->runSeeder($className);
        }
    }

    /**
     * Buat file seeder baru (static - TANPA koneksi database)
     * Dipanggil oleh command make:seeder
     *
     * @param string $basePath Path root project
     * @param string $name Nama seeder
     * @return string Nama file yang dibuat
     */
    public static function generateSeederFile(string $basePath, string $name): string
    {
        $className = Sanitizer::seederName($name);
        $fileName = $className . '.php';

        $dirPath = PathGuard::ensureDirectory($basePath, 'seeders');
        $filePath = $dirPath . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($filePath)) {
            throw new \RuntimeException("File seeder sudah ada: $fileName");
        }

        $template = self::getSeederTemplate($basePath, $className);
        file_put_contents($filePath, $template);

        return $fileName;
    }

    /**
     * Buat file seeder baru (instance method - untuk backward compat)
     *
     * @param string $name Nama seeder
     * @return string Nama file yang dibuat
     */
    public function makeSeeder(string $name): string
    {
        return self::generateSeederFile($this->basePath, $name);
    }

    /**
     * Template seeder
     */
    private static function getSeederTemplate(string $basePath, string $className): string
    {
        // Cek stub file
        $stubPath = $basePath . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'seeder.stub';

        if (file_exists($stubPath)) {
            $template = file_get_contents($stubPath);
            return str_replace('{{CLASS_NAME}}', $className, $template);
        }

        return <<<PHP
<?php

class {$className}
{
    /**
     * Jalankan seeder
     *
     * Contoh penggunaan:
     *   - Database::insert() untuk insert satu baris
     *   - Database::insertMany() untuk insert banyak baris
     *   - Database::transaction() untuk wrap dalam transaction
     */
    public function run(PDO \$pdo): void
    {
        // ─── Contoh 1: Insert satu baris ────────────────────────
        // Database::insert('users', [
        //     'name'  => 'Admin',
        //     'email' => 'admin@example.com',
        // ]);

        // ─── Contoh 2: Insert banyak baris sekaligus ───────────
        // Database::insertMany('users', ['name', 'email'], [
        //     ['John Doe', 'john@example.com'],
        //     ['Jane Doe', 'jane@example.com'],
        // ]);

        // ─── Contoh 3: Dengan transaction ───────────────────────
        // Database::transaction(function (\$pdo) {
        //     Database::insert('categories', ['name' => 'Technology']);
        //     Database::insert('categories', ['name' => 'Science']);
        // });
    }
}

PHP;
    }
}
