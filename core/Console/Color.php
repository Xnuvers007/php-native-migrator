<?php
// core/Console/Color.php
// ============================================================================
// Terminal Color Output
// Menampilkan teks berwarna di terminal/command prompt
// ============================================================================

class Color
{
    // ============================
    // ANSI Color Codes
    // ============================
    private const RESET     = "\033[0m";
    private const BOLD      = "\033[1m";
    private const DIM       = "\033[2m";
    private const ITALIC    = "\033[3m";
    private const UNDERLINE = "\033[4m";
    private const BLINK     = "\033[5m";
    private const REVERSE   = "\033[7m";
    private const HIDDEN    = "\033[8m";

    // Foreground Colors
    private const FG_BLACK   = "\033[30m";
    private const FG_RED     = "\033[31m";
    private const FG_GREEN   = "\033[32m";
    private const FG_YELLOW  = "\033[33m";
    private const FG_BLUE    = "\033[34m";
    private const FG_MAGENTA = "\033[35m";
    private const FG_CYAN    = "\033[36m";
    private const FG_WHITE   = "\033[37m";
    private const FG_GRAY    = "\033[90m";

    // Background Colors
    private const BG_BLACK   = "\033[40m";
    private const BG_RED     = "\033[41m";
    private const BG_GREEN   = "\033[42m";
    private const BG_YELLOW  = "\033[43m";
    private const BG_BLUE    = "\033[44m";
    private const BG_MAGENTA = "\033[45m";
    private const BG_CYAN    = "\033[46m";
    private const BG_WHITE   = "\033[47m";

    /** Deteksi apakah terminal mendukung warna */
    private static function supportsColor(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return (
                getenv('ANSICON') !== false ||
                getenv('ConEmuANSI') === 'ON' ||
                getenv('TERM') === 'xterm' ||
                getenv('WT_SESSION') !== false || // Windows Terminal
                str_contains(php_uname('v'), 'Windows 10') ||
                str_contains(php_uname('v'), 'Windows 11')
            );
        }
        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }

    /** Wrap teks dengan kode ANSI */
    private static function wrap(string $text, string $code): string
    {
        if (!self::supportsColor()) {
            return $text;
        }
        return $code . $text . self::RESET;
    }

    // ============================
    // Semantic Colors
    // ============================

    /** Teks hijau - untuk sukses */
    public static function success(string $text): string
    {
        return self::wrap($text, self::FG_GREEN);
    }

    /** Teks merah - untuk error */
    public static function error(string $text): string
    {
        return self::wrap($text, self::FG_RED);
    }

    /** Teks kuning - untuk warning */
    public static function warning(string $text): string
    {
        return self::wrap($text, self::FG_YELLOW);
    }

    /** Teks cyan - untuk info */
    public static function info(string $text): string
    {
        return self::wrap($text, self::FG_CYAN);
    }

    /** Teks abu-abu - untuk teks sekunder */
    public static function muted(string $text): string
    {
        return self::wrap($text, self::FG_GRAY);
    }

    /** Teks tebal */
    public static function bold(string $text): string
    {
        return self::wrap($text, self::BOLD);
    }

    /** Teks underline */
    public static function underline(string $text): string
    {
        return self::wrap($text, self::UNDERLINE);
    }

    /** Teks magenta - untuk highlight */
    public static function highlight(string $text): string
    {
        return self::wrap($text, self::FG_MAGENTA);
    }

    /** Teks biru */
    public static function blue(string $text): string
    {
        return self::wrap($text, self::FG_BLUE);
    }

    // ============================
    // Badge / Label
    // ============================

    /** Badge sukses [OK] */
    public static function badgeSuccess(string $text): string
    {
        return self::wrap(" $text ", self::BG_GREEN . self::FG_BLACK . self::BOLD);
    }

    /** Badge error [FAIL] */
    public static function badgeError(string $text): string
    {
        return self::wrap(" $text ", self::BG_RED . self::FG_WHITE . self::BOLD);
    }

    /** Badge warning [WARN] */
    public static function badgeWarning(string $text): string
    {
        return self::wrap(" $text ", self::BG_YELLOW . self::FG_BLACK . self::BOLD);
    }

    /** Badge info [INFO] */
    public static function badgeInfo(string $text): string
    {
        return self::wrap(" $text ", self::BG_CYAN . self::FG_BLACK . self::BOLD);
    }

    // ============================
    // Utility Methods
    // ============================

    /** Garis pembatas */
    public static function line(int $length = 60, string $char = '─'): string
    {
        return self::muted(str_repeat($char, $length));
    }

    /** Tampilkan newline */
    public static function newline(int $count = 1): void
    {
        echo str_repeat("\n", $count);
    }

    /** Header dengan border */
    public static function header(string $title, string $subtitle = ''): string
    {
        $output = "\n";
        $output .= self::bold(self::info($title)) . "\n";
        if ($subtitle) {
            $output .= self::muted($subtitle) . "\n";
        }
        $output .= self::line() . "\n";
        return $output;
    }

    /** Tampilkan banner ASCII art */
    public static function banner(): string
    {
        $art = <<<'ASCII'

  ╔══════════════════════════════════════════════════════════╗
  ║          PHP Native Migrator v2.1.0                      ║
  ║          Database Migration Tool                         ║
  ║          github.com/Xnuvers007/php-native-migrator       ║
  ╚══════════════════════════════════════════════════════════╝

ASCII;
        return self::info($art);
    }
}
