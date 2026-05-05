<?php
// core/Faker.php
// ============================================================================
// Native Faker
// Men-generate data acak (dummy) untuk kebutuhan Seeder
// ============================================================================

class Faker
{
    private static array $firstNames = [
        'Budi', 'Andi', 'Siti', 'Dewi', 'Joko', 'Rudi', 'Ahmad', 'Putri', 'Nina', 
        'Rina', 'Agus', 'Bagus', 'Citra', 'Dian', 'Eka', 'Fajar', 'Gita', 'Hadi'
    ];
    
    private static array $lastNames = [
        'Santoso', 'Wijaya', 'Kusuma', 'Pratama', 'Setiawan', 'Hidayat', 'Putra', 
        'Lestari', 'Nugroho', 'Saputra', 'Wahyuni', 'Sari', 'Siregar', 'Sinaga'
    ];
    
    private static array $domains = ['gmail.com', 'yahoo.com', 'example.com', 'mail.com', 'test.id'];
    private static array $words = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
        'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore'
    ];

    public static function name(): string
    {
        $first = self::$firstNames[array_rand(self::$firstNames)];
        $last = self::$lastNames[array_rand(self::$lastNames)];
        return "$first $last";
    }

    public static function email(?string $name = null): string
    {
        $name = $name ?: self::$firstNames[array_rand(self::$firstNames)];
        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $domain = self::$domains[array_rand(self::$domains)];
        return $cleanName . rand(1, 9999) . '@' . $domain;
    }

    public static function numberBetween(int $min = 0, int $max = 100): int
    {
        return rand($min, $max);
    }

    public static function boolean(): int
    {
        return rand(0, 1);
    }

    public static function word(): string
    {
        return self::$words[array_rand(self::$words)];
    }

    public static function sentence(int $wordCount = 6): string
    {
        $sentence = [];
        for ($i = 0; $i < $wordCount; $i++) {
            $sentence[] = self::word();
        }
        return ucfirst(implode(' ', $sentence)) . '.';
    }

    public static function date(string $format = 'Y-m-d'): string
    {
        $timestamp = rand(strtotime('2020-01-01'), time());
        return date($format, $timestamp);
    }
    
    public static function randomElement(array $elements)
    {
        return $elements[array_rand($elements)];
    }
}
