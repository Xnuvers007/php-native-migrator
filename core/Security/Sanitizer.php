<?php
// core/Security/Sanitizer.php
// ============================================================================
// Input Sanitizer
// Melindungi dari serangan: SQL Injection, XSS, RCE, Command Injection
// ============================================================================

class Sanitizer
{
    /**
     * Regex pattern untuk nama class yang valid
     * Hanya huruf, angka, underscore - mencegah Code Injection
     */
    private const CLASS_NAME_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    /**
     * Regex pattern untuk nama file migrasi yang valid
     * Format: m{timestamp}_{nama}.php
     */
    private const MIGRATION_FILE_PATTERN = '/^m\d{8}_\d{6}_[a-zA-Z0-9_]+\.php$/';

    /**
     * Regex pattern untuk nama tabel yang valid
     * Hanya huruf kecil, angka, underscore
     */
    private const TABLE_NAME_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    /**
     * Regex pattern untuk nama kolom yang valid
     */
    private const COLUMN_NAME_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    /**
     * Daftar kata-kata berbahaya yang tidak boleh ada di input CLI
     */
    private const DANGEROUS_PATTERNS = [
        '<?php',
        '<?=',
        '<%',
        '<script',
        'javascript:',
        'eval(',
        'exec(',
        'system(',
        'passthru(',
        'shell_exec(',
        'popen(',
        'proc_open(',
        '`',       // backtick execution
        '${',      // variable interpolation
        '../',     // path traversal
        '..\\',    // path traversal windows
        '\0',      // null byte
        '%00',     // null byte encoded
        '\x00',    // null byte hex
    ];

    /**
     * Sanitasi nama migrasi dari input CLI
     * Mencegah: RCE, Command Injection, Path Traversal
     *
     * @param string $name Input dari user
     * @return string Nama yang sudah disanitasi
     * @throws \InvalidArgumentException Jika input berbahaya
     */
    public static function migrationName(string $name): string
    {
        // Hapus whitespace di awal/akhir
        $name = trim($name);

        // Cek panjang
        if (empty($name)) {
            throw new \InvalidArgumentException('Nama migrasi tidak boleh kosong.');
        }

        if (strlen($name) > 100) {
            throw new \InvalidArgumentException('Nama migrasi terlalu panjang (maksimal 100 karakter).');
        }

        // Cek karakter berbahaya
        self::checkDangerousPatterns($name, 'nama migrasi');

        // Hanya izinkan huruf, angka, dan underscore
        $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);

        // Hapus underscore berulang
        $sanitized = preg_replace('/_+/', '_', $sanitized);

        // Hapus underscore di awal/akhir
        $sanitized = trim($sanitized, '_');

        if (empty($sanitized)) {
            throw new \InvalidArgumentException('Nama migrasi hanya mengandung karakter yang tidak valid.');
        }

        // Pastikan dimulai dengan huruf
        if (is_numeric($sanitized[0])) {
            $sanitized = 'm_' . $sanitized;
        }

        return strtolower($sanitized);
    }

    /**
     * Sanitasi nama seeder dari input CLI
     *
     * @param string $name Input dari user
     * @return string Nama class seeder (PascalCase)
     * @throws \InvalidArgumentException
     */
    public static function seederName(string $name): string
    {
        $name = trim($name);

        if (empty($name)) {
            throw new \InvalidArgumentException('Nama seeder tidak boleh kosong.');
        }

        if (strlen($name) > 100) {
            throw new \InvalidArgumentException('Nama seeder terlalu panjang (maksimal 100 karakter).');
        }

        self::checkDangerousPatterns($name, 'nama seeder');

        // Hanya izinkan huruf, angka, dan underscore
        $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', $name);

        if (empty($sanitized)) {
            throw new \InvalidArgumentException('Nama seeder hanya mengandung karakter yang tidak valid.');
        }

        // Pastikan diawali huruf besar (PascalCase)
        $sanitized = ucfirst($sanitized);

        // Tambahkan suffix Seeder jika belum ada
        if (!str_ends_with($sanitized, 'Seeder')) {
            $sanitized .= 'Seeder';
        }

        return $sanitized;
    }

    /**
     * Validasi nama file migrasi
     *
     * @param string $filename Nama file
     * @return bool
     */
    public static function isValidMigrationFile(string $filename): bool
    {
        return (bool)preg_match(self::MIGRATION_FILE_PATTERN, $filename);
    }

    /**
     * Validasi nama class
     * Mencegah: RCE via dynamic class instantiation
     *
     * @param string $className
     * @return bool
     */
    public static function isValidClassName(string $className): bool
    {
        return (bool)preg_match(self::CLASS_NAME_PATTERN, $className);
    }

    /**
     * Sanitasi nama tabel
     * Mencegah: SQL Injection via table name
     *
     * @param string $name
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function tableName(string $name): string
    {
        $name = trim($name);

        if (empty($name)) {
            throw new \InvalidArgumentException('Nama tabel tidak boleh kosong.');
        }

        self::checkDangerousPatterns($name, 'nama tabel');

        if (!preg_match(self::TABLE_NAME_PATTERN, $name)) {
            throw new \InvalidArgumentException(
                "Nama tabel '$name' mengandung karakter yang tidak valid. " .
                'Hanya huruf, angka, dan underscore yang diizinkan.'
            );
        }

        return $name;
    }

    /**
     * Sanitasi nama kolom
     * Mencegah: SQL Injection via column name
     *
     * @param string $name
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function columnName(string $name): string
    {
        $name = trim($name);

        if (empty($name)) {
            throw new \InvalidArgumentException('Nama kolom tidak boleh kosong.');
        }

        self::checkDangerousPatterns($name, 'nama kolom');

        if (!preg_match(self::COLUMN_NAME_PATTERN, $name)) {
            throw new \InvalidArgumentException(
                "Nama kolom '$name' mengandung karakter yang tidak valid. " .
                'Hanya huruf, angka, dan underscore yang diizinkan.'
            );
        }

        return $name;
    }

    /**
     * Sanitasi nilai default kolom
     * Mencegah: SQL Injection via default value
     *
     * @param mixed $value
     * @return mixed Nilai yang sudah disanitasi
     */
    public static function defaultValue($value)
    {
        if (is_null($value) || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            // Cek pattern berbahaya
            $lower = strtolower($value);
            $dangerous = ['drop', 'delete', 'truncate', 'alter', '--', '/*', '*/'];

            foreach ($dangerous as $pattern) {
                if (str_contains($lower, $pattern) && strlen($value) > 20) {
                    throw new \InvalidArgumentException('Nilai default mencurigakan terdeteksi.');
                }
            }

            return $value;
        }

        return (string)$value;
    }

    /**
     * Sanitasi nilai ENUM/SET
     *
     * @param array $values
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function enumValues(array $values): array
    {
        $sanitized = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                throw new \InvalidArgumentException('Nilai ENUM/SET harus berupa string.');
            }

            // Escape single quotes
            $sanitized[] = str_replace("'", "''", $value);
        }

        if (empty($sanitized)) {
            throw new \InvalidArgumentException('Nilai ENUM/SET tidak boleh kosong.');
        }

        return $sanitized;
    }

    /**
     * Sanitasi comment kolom
     *
     * @param string $comment
     * @return string
     */
    public static function comment(string $comment): string
    {
        // Hapus karakter berbahaya dari comment
        $comment = str_replace(["'", "\"", "\\", "\0", "\n", "\r"], '', $comment);
        return substr($comment, 0, 255);
    }

    /**
     * Sanitasi argument CLI
     * Mencegah: Command Injection
     *
     * @param string $arg
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function cliArgument(string $arg): string
    {
        $arg = trim($arg);

        self::checkDangerousPatterns($arg, 'argument CLI');

        // Hapus karakter yang bisa dipakai untuk command injection
        $arg = preg_replace('/[;&|`$(){}]/', '', $arg);

        return $arg;
    }

    /**
     * Cek apakah input mengandung pattern berbahaya
     *
     * @param string $input Input yang diperiksa
     * @param string $context Konteks untuk pesan error
     * @throws \InvalidArgumentException
     */
    private static function checkDangerousPatterns(string $input, string $context): void
    {
        $lower = strtolower($input);

        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (str_contains($lower, strtolower($pattern))) {
                throw new \InvalidArgumentException(
                    "Pattern berbahaya terdeteksi pada $context: input mengandung '$pattern'. " .
                    'Operasi dibatalkan untuk alasan keamanan.'
                );
            }
        }
    }
}
