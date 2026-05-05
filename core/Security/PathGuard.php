<?php
// core/Security/PathGuard.php
// ============================================================================
// Path Traversal Guard
// Melindungi dari serangan: LFI (Local File Inclusion), Path Traversal
// ============================================================================

class PathGuard
{
    /**
     * Direktori yang diizinkan (relatif dari basePath)
     */
    private const ALLOWED_DIRECTORIES = [
        'migrations',
        'seeders',
        'stubs',
        'core',
        'config',
    ];

    /**
     * Ekstensi file yang diizinkan untuk dimuat
     */
    private const ALLOWED_EXTENSIONS = ['php'];

    /**
     * Ekstensi file yang diizinkan untuk dibuat
     */
    private const CREATABLE_EXTENSIONS = ['php'];

    /**
     * Validasi dan amankan path file
     * Mencegah: Path Traversal, LFI, Null Byte Injection
     *
     * @param string $filePath Path yang akan divalidasi
     * @param string $basePath Base path project
     * @param string $allowedDir Direktori yang diizinkan (relatif)
     * @return string Path yang sudah diamankan
     * @throws \InvalidArgumentException Jika path berbahaya
     */
    public static function validatePath(string $filePath, string $basePath, string $allowedDir): string
    {
        // 1. Hapus null byte (Null Byte Injection)
        $filePath = str_replace(["\0", '%00', '\x00'], '', $filePath);
        $basePath = str_replace(["\0", '%00', '\x00'], '', $basePath);

        // 2. Normalize path separators
        $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
        $basePath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $basePath), DIRECTORY_SEPARATOR);

        // 3. Cek path traversal langsung
        if (str_contains($filePath, '..')) {
            throw new \InvalidArgumentException(
                "Path traversal terdeteksi! Path tidak boleh mengandung '..'. " .
                'Operasi dibatalkan untuk keamanan.'
            );
        }

        // 4. Bangun full path
        $allowedBase = $basePath . DIRECTORY_SEPARATOR . $allowedDir;
        $fullPath = $allowedBase . DIRECTORY_SEPARATOR . basename($filePath);

        // 5. Resolve ke real path (jika file sudah ada)
        $resolvedAllowedBase = realpath($allowedBase);
        if ($resolvedAllowedBase === false) {
            // Direktori belum ada, buat dulu
            if (!mkdir($allowedBase, 0755, true) && !is_dir($allowedBase)) {
                throw new \RuntimeException("Gagal membuat direktori: $allowedBase");
            }
            $resolvedAllowedBase = realpath($allowedBase);
        }

        // 6. Jika file sudah ada, cek real path
        if (file_exists($fullPath)) {
            $resolvedFullPath = realpath($fullPath);
            if ($resolvedFullPath === false || !str_starts_with($resolvedFullPath, $resolvedAllowedBase)) {
                throw new \InvalidArgumentException(
                    'Akses file di luar direktori yang diizinkan terdeteksi! ' .
                    'Operasi dibatalkan untuk keamanan.'
                );
            }
            return $resolvedFullPath;
        }

        return $fullPath;
    }

    /**
     * Validasi ekstensi file yang akan di-load
     * Mencegah: Loading file berbahaya
     *
     * @param string $filename Nama file
     * @return bool
     */
    public static function isAllowedExtension(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, self::ALLOWED_EXTENSIONS, true);
    }

    /**
     * Validasi bahwa direktori termasuk yang diizinkan
     *
     * @param string $directory Nama direktori
     * @return bool
     */
    public static function isAllowedDirectory(string $directory): bool
    {
        return in_array($directory, self::ALLOWED_DIRECTORIES, true);
    }

    /**
     * Amankan require/include file
     * Mencegah: LFI, RFI (Remote File Inclusion)
     *
     * @param string $filePath Path file yang akan di-require
     * @param string $basePath Base path project
     * @param string $allowedDir Direktori yang diizinkan
     * @return string Path yang aman untuk di-require
     * @throws \InvalidArgumentException
     */
    public static function safeRequire(string $filePath, string $basePath, string $allowedDir): string
    {
        // Cek RFI (Remote File Inclusion)
        $lower = strtolower($filePath);
        if (
            str_starts_with($lower, 'http://') ||
            str_starts_with($lower, 'https://') ||
            str_starts_with($lower, 'ftp://') ||
            str_starts_with($lower, 'php://') ||
            str_starts_with($lower, 'data://') ||
            str_starts_with($lower, 'expect://') ||
            str_starts_with($lower, 'phar://')
        ) {
            throw new \InvalidArgumentException(
                'Remote File Inclusion terdeteksi! Hanya file lokal yang diizinkan. ' .
                'Operasi dibatalkan untuk keamanan.'
            );
        }

        // Cek ekstensi
        if (!self::isAllowedExtension($filePath)) {
            throw new \InvalidArgumentException(
                "Ekstensi file tidak diizinkan. Hanya file .php yang boleh di-load."
            );
        }

        // Validasi path
        return self::validatePath($filePath, $basePath, $allowedDir);
    }

    /**
     * Pastikan direktori aman ada
     *
     * @param string $basePath Base path
     * @param string $directory Nama direktori
     * @return string Full path direktori
     */
    public static function ensureDirectory(string $basePath, string $directory): string
    {
        $fullPath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $directory;

        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true)) {
                throw new \RuntimeException("Gagal membuat direktori: $fullPath");
            }
        }

        return $fullPath;
    }

    /**
     * Scan direktori dengan aman (hanya file yang diizinkan)
     *
     * @param string $basePath Base path
     * @param string $directory Nama direktori
     * @return array Daftar nama file yang valid
     */
    public static function safeScanDir(string $basePath, string $directory): array
    {
        $dirPath = self::ensureDirectory($basePath, $directory);
        $files = scandir($dirPath);

        if ($files === false) {
            return [];
        }

        $safeFiles = [];
        foreach ($files as $file) {
            // Skip . dan ..
            if ($file === '.' || $file === '..' || $file === '.gitkeep') {
                continue;
            }

            // Hanya izinkan file dengan ekstensi yang valid
            if (self::isAllowedExtension($file)) {
                $safeFiles[] = $file;
            }
        }

        // Sort secara natural
        sort($safeFiles, SORT_NATURAL);

        return $safeFiles;
    }
}
