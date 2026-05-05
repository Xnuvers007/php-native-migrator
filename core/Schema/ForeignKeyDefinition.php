<?php
// core/Schema/ForeignKeyDefinition.php
// ============================================================================
// Foreign Key Definition - Fluent API untuk mendefinisikan foreign key
// Contoh: $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')
// ============================================================================

class ForeignKeyDefinition
{
    public string $column;
    public ?string $referencesColumn = null;
    public ?string $onTable = null;
    public string $onDeleteAction = 'RESTRICT';
    public string $onUpdateAction = 'RESTRICT';
    public ?string $name = null;

    /**
     * Constructor
     *
     * @param string $column Nama kolom yang menjadi foreign key
     */
    public function __construct(string $column)
    {
        $this->column = Sanitizer::columnName($column);
    }

    /**
     * Kolom yang di-reference di tabel lain
     * Contoh: ->references('id')
     *
     * @param string $column
     * @return self
     */
    public function references(string $column): self
    {
        $this->referencesColumn = Sanitizer::columnName($column);
        return $this;
    }

    /**
     * Tabel yang di-reference
     * Contoh: ->on('users')
     *
     * @param string $table
     * @return self
     */
    public function on(string $table): self
    {
        $this->onTable = Sanitizer::tableName($table);
        return $this;
    }

    /**
     * Aksi ketika parent record dihapus
     * Contoh: ->onDelete('cascade')
     *
     * @param string $action CASCADE|RESTRICT|SET NULL|NO ACTION
     * @return self
     */
    public function onDelete(string $action): self
    {
        $this->onDeleteAction = $this->validateAction($action);
        return $this;
    }

    /**
     * Aksi ketika parent record di-update
     * Contoh: ->onUpdate('cascade')
     *
     * @param string $action CASCADE|RESTRICT|SET NULL|NO ACTION
     * @return self
     */
    public function onUpdate(string $action): self
    {
        $this->onUpdateAction = $this->validateAction($action);
        return $this;
    }

    /** Shortcut: ON DELETE CASCADE */
    public function cascadeOnDelete(): self
    {
        return $this->onDelete('CASCADE');
    }

    /** Shortcut: ON UPDATE CASCADE */
    public function cascadeOnUpdate(): self
    {
        return $this->onUpdate('CASCADE');
    }

    /** Shortcut: ON DELETE SET NULL */
    public function nullOnDelete(): self
    {
        return $this->onDelete('SET NULL');
    }

    /** Shortcut: ON UPDATE SET NULL */
    public function nullOnUpdate(): self
    {
        return $this->onUpdate('SET NULL');
    }

    /** Set nama constraint secara manual */
    public function constrained(string $name = ''): self
    {
        if (!empty($name)) {
            $this->name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        }
        return $this;
    }

    /**
     * Validasi aksi referential
     *
     * @param string $action
     * @return string
     * @throws \InvalidArgumentException
     */
    private function validateAction(string $action): string
    {
        $action = strtoupper(trim($action));
        $allowed = ['CASCADE', 'RESTRICT', 'SET NULL', 'NO ACTION', 'SET DEFAULT'];

        if (!in_array($action, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Aksi foreign key '$action' tidak valid. Gunakan: " . implode(', ', $allowed)
            );
        }

        return $action;
    }

    /**
     * Generate nama constraint otomatis jika belum di-set
     *
     * @param string $table Nama tabel
     * @return string
     */
    public function getConstraintName(string $table): string
    {
        if ($this->name) {
            return $this->name;
        }
        return "fk_{$table}_{$this->column}";
    }
}
