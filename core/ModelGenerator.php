<?php
// core/ModelGenerator.php
// ============================================================================
// Generator Entity/Model Sederhana
// ============================================================================

class ModelGenerator
{
    public static function generate(string $basePath, string $name): string
    {
        $name = Sanitizer::className($name);
        
        $modelsDir = PathGuard::ensureDirectory($basePath, 'models');
        
        $fileName = $name . '.php';
        $filePath = $modelsDir . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($filePath)) {
            throw new \RuntimeException("Model [$name] sudah ada.");
        }

        $stub = <<<PHP
<?php

class {$name}
{
    // Tambahkan properti dan method Anda di sini...
}

PHP;

        if (file_put_contents($filePath, $stub) === false) {
            throw new \RuntimeException("Gagal menulis file model: $fileName");
        }

        return $fileName;
    }
}
