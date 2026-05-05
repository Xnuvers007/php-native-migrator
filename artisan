<?php
if (php_sapi_name() !== 'cli') {
    die('Script ini hanya bisa dijalankan dari command line (CLI).');
}

// Minimum PHP version check
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    die("Error: PHP 8.0 atau lebih baru diperlukan. Versi Anda: " . PHP_VERSION . "\n");
}

// ─── Bootstrap ──────────────────────────────────────────────────────────────
$basePath = __DIR__;

// Load Bootstrap (autoloader)
require_once $basePath . '/core/Bootstrap.php';
Bootstrap::init($basePath);

// ─── Load Environment (.env) ────────────────────────────────────────────────
$envFile = $basePath . '/.env';

if (file_exists($envFile)) {
    try {
        DotEnv::loadFrom($envFile);
    } catch (\Exception $e) {
        echo Color::badgeError('ENV ERROR') . ' ' . Color::error($e->getMessage()) . "\n";
        exit(1);
    }
} else {
    // Cek apakah command memerlukan database
    $command = $argv[1] ?? null;
    $nonDbCommands = ['help', '--help', '-h', 'list', 'version', '--version', '-v', 'make:migration', 'make:seeder', 'make:model'];

    if (!in_array($command, $nonDbCommands, true)) {
        echo Color::badgeError('ERROR') . ' ' . Color::error("File .env tidak ditemukan!") . "\n";
        echo Color::muted("  Salin .env.example ke .env dan sesuaikan konfigurasinya:") . "\n";

        if (PHP_OS_FAMILY === 'Windows') {
            echo Color::info("  copy .env.example .env") . "\n\n";
        } else {
            echo Color::info("  cp .env.example .env") . "\n\n";
        }
        exit(1);
    }
}

// ─── Jalankan Console Application ───────────────────────────────────────────
$app = new ConsoleApplication($basePath);
$app->run($argv);