<?php
// core/Bootstrap.php
// ============================================================================
// PHP Native Migrator - Bootstrap & Autoloader
// Mengelola autoloading class secara otomatis tanpa Composer
// ============================================================================

class Bootstrap
{
    private static $registered = false;
    private static $basePath;

    /**
     * Class map untuk autoloading
     * Key: nama class, Value: path relatif dari basePath
     */
    private static $classMap = [
        // Core
        'DotEnv'                => 'core/DotEnv.php',
        'Database'              => 'core/Database.php',
        'Migrator'              => 'core/Migrator.php',
        'Seeder'                => 'core/Seeder.php',

        // Schema
        'Schema'                => 'core/Schema/Schema.php',
        'Blueprint'             => 'core/Schema/Blueprint.php',
        'ColumnDefinition'      => 'core/Schema/ColumnDefinition.php',
        'ForeignKeyDefinition'  => 'core/Schema/ForeignKeyDefinition.php',
        'GrammarInterface'      => 'core/Schema/Grammar/GrammarInterface.php',
        'MySqlGrammar'          => 'core/Schema/Grammar/MySqlGrammar.php',
        'SQLiteGrammar'         => 'core/Schema/Grammar/SQLiteGrammar.php',

        // Console
        'ConsoleApplication'    => 'core/Console/Application.php',
        'Color'                 => 'core/Console/Color.php',
        'ConsoleTable'          => 'core/Console/Table.php',

        // Security
        'Sanitizer'             => 'core/Security/Sanitizer.php',
        'PathGuard'             => 'core/Security/PathGuard.php',
    ];

    /**
     * Inisialisasi Bootstrap
     *
     * @param string $basePath Path root project
     */
    public static function init(string $basePath): void
    {
        self::$basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        if (!self::$registered) {
            spl_autoload_register([self::class, 'autoload']);
            self::$registered = true;
        }

        // Set error reporting untuk development
        error_reporting(E_ALL);
        set_error_handler([self::class, 'errorHandler']);
        set_exception_handler([self::class, 'exceptionHandler']);

        // Set timezone default
        date_default_timezone_set('Asia/Jakarta');

        // Set memory limit yang cukup
        ini_set('memory_limit', '256M');
    }

    /**
     * Autoload class berdasarkan class map
     *
     * @param string $className Nama class yang akan di-load
     */
    public static function autoload(string $className): void
    {
        if (isset(self::$classMap[$className])) {
            $file = self::$basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::$classMap[$className]);
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Mendapatkan base path project
     *
     * @return string
     */
    public static function getBasePath(): string
    {
        return self::$basePath;
    }

    /**
     * Mendapatkan path relatif dari base path
     *
     * @param string $relativePath
     * @return string
     */
    public static function path(string $relativePath = ''): string
    {
        if (empty($relativePath)) {
            return self::$basePath;
        }
        return self::$basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * Custom error handler
     */
    public static function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Jangan tampilkan notice/warning di production
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $type = match ($errno) {
            E_WARNING         => 'Warning',
            E_NOTICE          => 'Notice',
            E_STRICT          => 'Strict',
            E_DEPRECATED      => 'Deprecated',
            E_USER_ERROR      => 'User Error',
            E_USER_WARNING    => 'User Warning',
            E_USER_NOTICE     => 'User Notice',
            E_USER_DEPRECATED => 'User Deprecated',
            default           => 'Unknown Error',
        };

        fwrite(STDERR, Color::error("[$type] $errstr") . "\n");
        fwrite(STDERR, Color::muted("  File: $errfile:$errline") . "\n");

        // Jangan lanjutkan error handler PHP bawaan
        return true;
    }

    /**
     * Custom exception handler
     */
    public static function exceptionHandler(\Throwable $e): void
    {
        fwrite(STDERR, "\n" . Color::error('  ✖ EXCEPTION: ' . $e->getMessage()) . "\n");
        fwrite(STDERR, Color::muted('  File: ' . $e->getFile() . ':' . $e->getLine()) . "\n");

        if (getenv('APP_DEBUG') === 'true') {
            fwrite(STDERR, Color::muted("\n  Stack Trace:") . "\n");
            foreach (explode("\n", $e->getTraceAsString()) as $line) {
                fwrite(STDERR, Color::muted("  $line") . "\n");
            }
        }

        fwrite(STDERR, "\n");
        exit(1);
    }

    /**
     * Mendapatkan versi aplikasi
     *
     * @return string
     */
    public static function version(): string
    {
        return '2.0.0';
    }

    /**
     * Mendapatkan nama aplikasi
     *
     * @return string
     */
    public static function name(): string
    {
        return 'PHP Native Migrator';
    }
}
