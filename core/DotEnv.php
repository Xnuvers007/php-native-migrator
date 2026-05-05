<?php
// core/DotEnv.php
// ============================================================================
// Enhanced DotEnv Parser
// Mendukung: quoted values, komentar inline, interpolasi variabel
// Keamanan: Mencegah overwrite variabel sistem yang kritis
// ============================================================================

class DotEnv
{
    protected string $path;

    /**
     * Variabel sistem yang TIDAK BOLEH di-overwrite
     * Mencegah: Environment Variable Injection
     */
    private const PROTECTED_VARS = [
        'PATH', 'HOME', 'USER', 'SHELL', 'TERM',
        'COMSPEC', 'SYSTEMROOT', 'WINDIR', 'PROGRAMFILES',
    ];

    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(
                sprintf('File %s tidak ditemukan. Silakan buat file .env terlebih dahulu.', $path)
            );
        }
        $this->path = $path;
    }

    /**
     * Load dan parse file .env
     */
    public function load(): void
    {
        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new \RuntimeException("Gagal membaca file: {$this->path}");
        }

        foreach ($lines as $line) {
            // Trim whitespace
            $line = trim($line);

            // Skip baris kosong dan komentar
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Pastikan ada tanda '='
            if (!str_contains($line, '=')) {
                continue;
            }

            // Pecah key=value
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Validasi nama variabel
            if (!preg_match('/^[A-Z_][A-Z0-9_]*$/i', $name)) {
                continue;
            }

            // Cek apakah variabel dilindungi
            if (in_array(strtoupper($name), self::PROTECTED_VARS, true)) {
                continue;
            }

            // Proses nilai
            $value = $this->parseValue($value);

            // Interpolasi variabel: ${VAR_NAME}
            $value = $this->interpolate($value);

            // Set ke environment jika belum ada
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Parse nilai dari .env
     * Mendukung: double quotes, single quotes, unquoted
     */
    private function parseValue(string $value): string
    {
        // Hapus komentar inline (hanya jika unquoted)
        if (!str_starts_with($value, '"') && !str_starts_with($value, "'")) {
            // Cari # yang bukan bagian dari value
            $commentPos = strpos($value, ' #');
            if ($commentPos !== false) {
                $value = trim(substr($value, 0, $commentPos));
            }
        }

        // Proses quoted strings
        $len = strlen($value);
        if ($len >= 2) {
            $first = $value[0];
            $last = $value[$len - 1];

            // Double-quoted: mendukung escape sequences
            if ($first === '"' && $last === '"') {
                $value = substr($value, 1, -1);
                $value = str_replace(
                    ['\\n', '\\r', '\\t', '\\"', '\\\\'],
                    ["\n", "\r", "\t", '"', '\\'],
                    $value
                );
                return $value;
            }

            // Single-quoted: literal (tanpa escape)
            if ($first === "'" && $last === "'") {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }

    /**
     * Interpolasi variabel: ${VAR_NAME} atau $VAR_NAME
     */
    private function interpolate(string $value): string
    {
        // Pattern: ${VAR_NAME}
        $value = preg_replace_callback('/\$\{([A-Z_][A-Z0-9_]*)\}/i', function ($matches) {
            return getenv($matches[1]) ?: '';
        }, $value);

        return $value;
    }

    /**
     * Static helper untuk load .env dengan satu baris
     */
    public static function loadFrom(string $path): void
    {
        (new self($path))->load();
    }
}
