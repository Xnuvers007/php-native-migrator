<?php
// core/Schema/ColumnDefinition.php
// ============================================================================
// Column Definition - Fluent API untuk mendefinisikan kolom tabel
// Setiap method return $this agar bisa di-chain seperti Laravel
// ============================================================================

class ColumnDefinition
{
    // ============================
    // Properti Kolom
    // ============================
    public string $name;
    public string $type;
    public ?int $length = null;
    public ?int $precision = null;
    public ?int $scale = null;
    public array $values = [];       // Untuk ENUM/SET
    public bool $nullable = false;
    public $defaultValue = null;
    public bool $hasDefault = false;
    public bool $unsigned = false;
    public bool $autoIncrement = false;
    public bool $isUnique = false;
    public bool $isIndex = false;
    public bool $isPrimary = false;
    public ?string $after = null;
    public bool $first = false;
    public ?string $comment = null;
    public ?string $charset = null;
    public ?string $collation = null;
    public bool $change = false;      // Untuk ALTER TABLE MODIFY
    public bool $useCurrent = false;
    public bool $useCurrentOnUpdate = false;

    /**
     * Constructor
     *
     * @param string $name Nama kolom
     * @param string $type Tipe data internal
     * @param array $parameters Parameter tambahan
     */
    public function __construct(string $name, string $type, array $parameters = [])
    {
        $this->name = Sanitizer::columnName($name);
        $this->type = $type;

        foreach ($parameters as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    // ============================
    // Fluent Modifier Methods
    // Semua return $this untuk chaining
    // ============================

    /** Kolom boleh NULL */
    public function nullable(bool $value = true): self
    {
        $this->nullable = $value;
        return $this;
    }

    /** Set nilai default kolom */
    public function default($value): self
    {
        $this->defaultValue = Sanitizer::defaultValue($value);
        $this->hasDefault = true;
        return $this;
    }

    /** Kolom UNSIGNED (hanya untuk numerik) */
    public function unsigned(): self
    {
        $this->unsigned = true;
        return $this;
    }

    /** Tambahkan UNIQUE constraint */
    public function unique(): self
    {
        $this->isUnique = true;
        return $this;
    }

    /** Tambahkan INDEX */
    public function index(): self
    {
        $this->isIndex = true;
        return $this;
    }

    /** Set sebagai PRIMARY KEY */
    public function primary(): self
    {
        $this->isPrimary = true;
        return $this;
    }

    /** Posisikan setelah kolom tertentu (MySQL only) */
    public function after(string $column): self
    {
        $this->after = Sanitizer::columnName($column);
        return $this;
    }

    /** Posisikan sebagai kolom pertama (MySQL only) */
    public function first(): self
    {
        $this->first = true;
        return $this;
    }

    /** Tambahkan komentar pada kolom */
    public function comment(string $text): self
    {
        $this->comment = Sanitizer::comment($text);
        return $this;
    }

    /** Set AUTO_INCREMENT */
    public function autoIncrement(): self
    {
        $this->autoIncrement = true;
        return $this;
    }

    /** Set character set kolom */
    public function charset(string $charset): self
    {
        $this->charset = preg_replace('/[^a-zA-Z0-9_]/', '', $charset);
        return $this;
    }

    /** Set collation kolom */
    public function collation(string $collation): self
    {
        $this->collation = preg_replace('/[^a-zA-Z0-9_]/', '', $collation);
        return $this;
    }

    /** Tandai kolom ini untuk di-modify (ALTER TABLE) */
    public function change(): self
    {
        $this->change = true;
        return $this;
    }

    /** Set DEFAULT CURRENT_TIMESTAMP */
    public function useCurrent(): self
    {
        $this->useCurrent = true;
        $this->hasDefault = true;
        return $this;
    }

    /** Set ON UPDATE CURRENT_TIMESTAMP */
    public function useCurrentOnUpdate(): self
    {
        $this->useCurrentOnUpdate = true;
        return $this;
    }
}
