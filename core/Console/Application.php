<?php
// core/Console/Application.php
// ============================================================================
// Console Application - CLI Router & Command Handler
// Menangani parsing argument, routing command, dan tampilan help
// ============================================================================

class ConsoleApplication
{
    private string $basePath;
    private array $options = [];

    /**
     * Definisi semua command yang tersedia
     */
    private array $commands = [
        'migrate'            => ['desc' => 'Jalankan semua migrasi yang belum dieksekusi', 'group' => 'Migration'],
        'migrate:rollback'   => ['desc' => 'Rollback migrasi terakhir (satu batch)', 'group' => 'Migration'],
        'migrate:reset'      => ['desc' => 'Rollback semua migrasi', 'group' => 'Migration'],
        'migrate:refresh'    => ['desc' => 'Reset + migrate ulang semua migrasi', 'group' => 'Migration'],
        'migrate:fresh'      => ['desc' => 'Hapus semua tabel + migrate dari awal', 'group' => 'Migration'],
        'migrate:status'     => ['desc' => 'Tampilkan status semua migrasi', 'group' => 'Migration'],
        'make:migration'     => ['desc' => 'Buat file migrasi baru', 'group' => 'Generator'],
        'make:seeder'        => ['desc' => 'Buat file seeder baru', 'group' => 'Generator'],
        'make:model'         => ['desc' => 'Buat file model/entity baru', 'group' => 'Generator'],
        'db:seed'            => ['desc' => 'Jalankan database seeder', 'group' => 'Database'],
        'db:wipe'            => ['desc' => 'Hapus semua tabel dari database', 'group' => 'Database'],
        'list'               => ['desc' => 'Tampilkan daftar semua command', 'group' => 'Help'],
        'help'               => ['desc' => 'Tampilkan bantuan', 'group' => 'Help'],
        'version'            => ['desc' => 'Tampilkan versi aplikasi', 'group' => 'Help'],
    ];

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Jalankan aplikasi CLI
     *
     * @param array $argv Argument dari command line
     */
    public function run(array $argv): void
    {
        // Parse command dan options
        $command = $argv[1] ?? null;
        $argument = $argv[2] ?? null;

        // Parse options (--key=value atau --flag)
        $this->parseOptions(array_slice($argv, 2));

        // Sanitasi command
        if ($command !== null) {
            $command = Sanitizer::cliArgument($command);
        }

        // Route ke handler yang sesuai
        try {
            match ($command) {
                'migrate'            => $this->handleMigrate(),
                'migrate:rollback'   => $this->handleRollback(),
                'migrate:reset'      => $this->handleReset(),
                'migrate:refresh'    => $this->handleRefresh(),
                'migrate:fresh'      => $this->handleFresh(),
                'migrate:status'     => $this->handleStatus(),
                'make:migration'     => $this->handleMakeMigration($argument),
                'make:seeder'        => $this->handleMakeSeeder($argument),
                'make:model'         => $this->handleMakeModel($argument),
                'db:seed'            => $this->handleDbSeed(),
                'db:wipe'            => $this->handleDbWipe(),
                'list'               => $this->handleList(),
                'help', '--help', '-h' => $this->handleHelp(),
                'version', '--version', '-v' => $this->handleVersion(),
                null                 => $this->handleNoCommand(),
                default              => $this->handleUnknown($command),
            };
        } catch (\InvalidArgumentException $e) {
            echo "\n" . Color::badgeError('ERROR') . ' ' . Color::error($e->getMessage()) . "\n\n";
            exit(1);
        } catch (\RuntimeException $e) {
            echo "\n" . Color::badgeError('ERROR') . ' ' . Color::error($e->getMessage()) . "\n\n";
            exit(1);
        } catch (\PDOException $e) {
            echo "\n" . Color::badgeError('DB ERROR') . ' ' . Color::error($e->getMessage()) . "\n\n";
            exit(1);
        }
    }

    /**
     * Parse options dari arguments
     */
    private function parseOptions(array $args): void
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $arg = substr($arg, 2);
                if (str_contains($arg, '=')) {
                    [$key, $value] = explode('=', $arg, 2);
                    $this->options[$key] = $value;
                } else {
                    $this->options[$arg] = true;
                }
            }
        }
    }

    /**
     * Ambil nilai option
     */
    private function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    // ========================================================================
    // COMMAND HANDLERS
    // ========================================================================

    private function handleMigrate(): void
    {
        $migrator = new Migrator($this->basePath);

        if ($this->getOption('seed')) {
            $migrator->migrate();
            $seeder = new Seeder($this->basePath);
            $seeder->run();
        } else {
            $migrator->migrate();
        }
    }

    private function handleRollback(): void
    {
        $migrator = new Migrator($this->basePath);
        $steps = (int)$this->getOption('step', 0);
        $file = $this->getOption('file', null);
        $migrator->rollback($steps, $file);
    }

    private function handleReset(): void
    {
        $migrator = new Migrator($this->basePath);
        $migrator->reset();
    }

    private function handleRefresh(): void
    {
        $migrator = new Migrator($this->basePath);
        $migrator->refresh();

        if ($this->getOption('seed')) {
            $seeder = new Seeder($this->basePath);
            $seeder->run();
        }
    }

    private function handleFresh(): void
    {
        $migrator = new Migrator($this->basePath);
        $migrator->fresh();

        if ($this->getOption('seed')) {
            $seeder = new Seeder($this->basePath);
            $seeder->run();
        }
    }

    private function handleStatus(): void
    {
        $migrator = new Migrator($this->basePath);
        $migrator->status();
    }

    private function handleMakeMigration(?string $name): void
    {
        if (!$name) {
            echo Color::error("\n  ✖ Nama migrasi harus diisi!") . "\n";
            echo Color::muted("  Contoh: php artisan make:migration create_users_table") . "\n\n";
            echo Color::muted("  Format nama:") . "\n";
            echo Color::muted("    create_xxx_table    → Template CREATE TABLE") . "\n";
            echo Color::muted("    add_xxx_to_yyy      → Template ALTER TABLE") . "\n";
            echo Color::muted("    nama_bebas          → Template kosong") . "\n\n";
            return;
        }

        // Generate file migrasi tanpa koneksi database
        $fileName = Migrator::generateMigrationFile($this->basePath, $name);

        echo "\n" . Color::success("  ✓ File migrasi berhasil dibuat: ") .
             Color::info("migrations/$fileName") . "\n\n";
    }

    private function handleMakeSeeder(?string $name = null): void
    {
        $name = $name ?? $this->getOption('name');

        if (!$name) {
            echo Color::error("\n  ✖ Nama seeder harus diisi!") . "\n";
            echo Color::muted("  Contoh: php artisan make:seeder UserSeeder") . "\n\n";
            return;
        }

        // Generate file seeder tanpa koneksi database
        $fileName = Seeder::generateSeederFile($this->basePath, $name);

        echo "\n" . Color::success("  ✓ File seeder berhasil dibuat: ") .
             Color::info("seeders/$fileName") . "\n\n";
    }

    private function handleMakeModel(?string $name = null): void
    {
        $name = $name ?? $this->getOption('name');

        if (!$name) {
            echo Color::error("\n  ✖ Nama model harus diisi!") . "\n";
            echo Color::muted("  Contoh: php artisan make:model User") . "\n\n";
            return;
        }

        $fileName = ModelGenerator::generate($this->basePath, $name);

        echo "\n" . Color::success("  ✓ File model berhasil dibuat: ") .
             Color::info("models/$fileName") . "\n\n";
    }

    private function handleDbSeed(): void
    {
        $seeder = new Seeder($this->basePath);
        $class = $this->getOption('class');
        $seeder->run($class);
    }

    private function handleDbWipe(): void
    {
        echo Color::badgeError('WIPE') . " Menghapus semua tabel...\n\n";

        $pdo = Database::getConnection();
        $driver = getenv('DB_DRIVER') ?: 'mysql';
        Schema::init($pdo, $driver);
        Schema::dropAllTables();

        echo Color::success("  ✓ Semua tabel berhasil dihapus.") . "\n\n";
    }

    private function handleList(): void
    {
        echo Color::banner();

        // Group commands
        $groups = [];
        foreach ($this->commands as $name => $info) {
            $groups[$info['group']][$name] = $info['desc'];
        }

        foreach ($groups as $group => $commands) {
            echo Color::bold(Color::warning("  $group")) . "\n";
            foreach ($commands as $name => $desc) {
                echo '    ' . Color::success(str_pad($name, 22)) . Color::muted($desc) . "\n";
            }
            echo "\n";
        }
    }

    private function handleHelp(): void
    {
        echo Color::banner();
        echo Color::bold("  Penggunaan:") . "\n";
        echo Color::muted("    php artisan <command> [arguments] [options]") . "\n\n";

        echo Color::bold("  Contoh:") . "\n";
        echo Color::muted("    php artisan make:migration create_users_table") . "\n";
        echo Color::muted("    php artisan migrate") . "\n";
        echo Color::muted("    php artisan migrate:rollback --step=3") . "\n";
        echo Color::muted("    php artisan migrate:refresh --seed") . "\n";
        echo Color::muted("    php artisan make:seeder UserSeeder") . "\n";
        echo Color::muted("    php artisan db:seed --class=UserSeeder") . "\n\n";

        echo Color::bold("  Options:") . "\n";
        echo '    ' . Color::success(str_pad('--seed', 22)) . Color::muted('Jalankan seeder setelah migrate/refresh/fresh') . "\n";
        echo '    ' . Color::success(str_pad('--step=N', 22)) . Color::muted('Jumlah step untuk rollback') . "\n";
        echo '    ' . Color::success(str_pad('--class=ClassName', 22)) . Color::muted('Seeder class yang akan dijalankan') . "\n\n";

        echo Color::muted("  Gunakan 'php artisan list' untuk melihat semua command.") . "\n\n";
    }

    private function handleVersion(): void
    {
        echo "\n  " . Color::bold(Bootstrap::name()) . ' ' .
             Color::success('v' . Bootstrap::version()) . "\n";
        echo Color::muted("  PHP " . PHP_VERSION . " | " . PHP_OS) . "\n\n";
    }

    private function handleNoCommand(): void
    {
        $this->handleHelp();
    }

    private function handleUnknown(string $command): void
    {
        echo "\n" . Color::badgeError('ERROR') . ' ' .
             Color::error("Command '$command' tidak dikenali.") . "\n\n";

        // Cari command yang mirip (typo correction)
        $suggestions = $this->findSimilarCommands($command);
        if (!empty($suggestions)) {
            echo Color::muted("  Mungkin maksud Anda:") . "\n";
            foreach ($suggestions as $suggestion) {
                echo "    " . Color::success($suggestion) . "\n";
            }
            echo "\n";
        }

        echo Color::muted("  Gunakan 'php artisan list' untuk melihat semua command.") . "\n\n";
    }

    /**
     * Cari command yang mirip (untuk typo correction)
     */
    private function findSimilarCommands(string $input): array
    {
        $suggestions = [];
        foreach (array_keys($this->commands) as $command) {
            $distance = levenshtein($input, $command);
            if ($distance <= 3) {
                $suggestions[$command] = $distance;
            }
        }

        asort($suggestions);
        return array_keys(array_slice($suggestions, 0, 3));
    }
}
