<?php
// seeders/DatabaseSeeder.php
// ============================================================================
// Main Database Seeder
// File ini adalah seeder utama yang dijalankan saat: php artisan db:seed
// Anda bisa memanggil seeder lain dari sini
// ============================================================================

class DatabaseSeeder
{
    /**
     * Jalankan semua seeder
     *
     * @param PDO $pdo Koneksi database
     */
    public function run(PDO $pdo): void
    {
        $basePath = Bootstrap::getBasePath();
        $seeder = new Seeder($basePath);
        
        // Auto-discover semua file seeder di folder seeders/
        $files = PathGuard::safeScanDir($basePath, 'seeders');
        $classesToRun = [];

        foreach ($files as $file) {
            // Abaikan DatabaseSeeder.php dan file non-PHP
            if ($file !== 'DatabaseSeeder.php' && str_ends_with($file, 'Seeder.php')) {
                $className = pathinfo($file, PATHINFO_FILENAME);
                $classesToRun[] = $className;
            }
        }

        if (empty($classesToRun)) {
            echo Color::muted("    (Belum ada file seeder. Buat dengan: php artisan make:seeder NamaSeeder)") . "\n";
            return;
        }

        // Jika butuh urutan eksekusi yang spesifik, Anda bisa memanggilnya secara manual:
        // $seeder->call(['UserSeeder', 'ProductSeeder']);
        
        // Jalankan semua seeder yang ditemukan (secara otomatis, urut abjad)
        $seeder->call($classesToRun);
    }
}
