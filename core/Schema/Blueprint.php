<?php
// core/Schema/Blueprint.php
// ============================================================================
// Blueprint - Fluent API untuk membangun struktur tabel
// Ini jantung dari Schema Builder, mirip seperti Laravel Blueprint
//
// Contoh penggunaan:
//   Schema::create('users', function(Blueprint $table) {
//       $table->id();
//       $table->string('name', 100);
//       $table->string('email')->unique();
//       $table->text('bio')->nullable();
//       $table->timestamps();
//   });
// ============================================================================

class Blueprint
{
    private string $table;
    private array $columns = [];
    private array $foreignKeys = [];
    private array $compositeUniques = [];
    private array $compositeIndexes = [];
    private array $dropColumns = [];
    private array $renameColumns = [];
    private array $dropIndexes = [];
    private array $dropForeignKeys = [];
    private ?string $engine = null;
    private ?string $charset = null;
    private ?string $collation = null;
    private bool $isCreate;

    public function __construct(string $table, bool $isCreate = true)
    {
        $this->table = Sanitizer::tableName($table);
        $this->isCreate = $isCreate;
    }

    // ========================================================================
    // COLUMN TYPES - Semua method return ColumnDefinition untuk chaining
    // ========================================================================

    // ─── Auto Increment / ID ────────────────────────────────────────────

    /** Primary key auto increment (BIGINT UNSIGNED) */
    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->addColumn($name, 'bigInteger', [
            'unsigned'      => true,
            'autoIncrement' => true,
            'isPrimary'     => true,
        ]);
    }

    /** BIGINT AUTO_INCREMENT PRIMARY KEY */
    public function bigIncrements(string $name): ColumnDefinition
    {
        return $this->id($name);
    }

    /** INT AUTO_INCREMENT PRIMARY KEY */
    public function increments(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'integer', [
            'unsigned'      => true,
            'autoIncrement' => true,
            'isPrimary'     => true,
        ]);
    }

    /** TINYINT AUTO_INCREMENT PRIMARY KEY */
    public function tinyIncrements(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'tinyInteger', [
            'unsigned'      => true,
            'autoIncrement' => true,
            'isPrimary'     => true,
        ]);
    }

    /** SMALLINT AUTO_INCREMENT PRIMARY KEY */
    public function smallIncrements(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'smallInteger', [
            'unsigned'      => true,
            'autoIncrement' => true,
            'isPrimary'     => true,
        ]);
    }

    /** MEDIUMINT AUTO_INCREMENT PRIMARY KEY */
    public function mediumIncrements(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'mediumInteger', [
            'unsigned'      => true,
            'autoIncrement' => true,
            'isPrimary'     => true,
        ]);
    }

    // ─── Integer Types ──────────────────────────────────────────────────

    /** BIGINT */
    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'bigInteger');
    }

    /** INT */
    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'integer');
    }

    /** TINYINT */
    public function tinyInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'tinyInteger');
    }

    /** SMALLINT */
    public function smallInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'smallInteger');
    }

    /** MEDIUMINT */
    public function mediumInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'mediumInteger');
    }

    /** BIGINT UNSIGNED */
    public function unsignedBigInteger(string $name): ColumnDefinition
    {
        return $this->bigInteger($name)->unsigned();
    }

    /** INT UNSIGNED */
    public function unsignedInteger(string $name): ColumnDefinition
    {
        return $this->integer($name)->unsigned();
    }

    /** TINYINT UNSIGNED */
    public function unsignedTinyInteger(string $name): ColumnDefinition
    {
        return $this->tinyInteger($name)->unsigned();
    }

    /** SMALLINT UNSIGNED */
    public function unsignedSmallInteger(string $name): ColumnDefinition
    {
        return $this->smallInteger($name)->unsigned();
    }

    /** MEDIUMINT UNSIGNED */
    public function unsignedMediumInteger(string $name): ColumnDefinition
    {
        return $this->mediumInteger($name)->unsigned();
    }

    // ─── String Types ───────────────────────────────────────────────────

    /** VARCHAR (default 255) */
    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, 'string', ['length' => $length]);
    }

    /** CHAR */
    public function char(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, 'char', ['length' => $length]);
    }

    /** TEXT */
    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'text');
    }

    /** TINYTEXT */
    public function tinyText(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'tinyText');
    }

    /** MEDIUMTEXT */
    public function mediumText(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'mediumText');
    }

    /** LONGTEXT */
    public function longText(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'longText');
    }

    // ─── Numeric Types ──────────────────────────────────────────────────

    /** FLOAT */
    public function float(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, 'float', ['precision' => $precision, 'scale' => $scale]);
    }

    /** DOUBLE */
    public function double(string $name, int $precision = 15, int $scale = 8): ColumnDefinition
    {
        return $this->addColumn($name, 'double', ['precision' => $precision, 'scale' => $scale]);
    }

    /** DECIMAL */
    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, 'decimal', ['precision' => $precision, 'scale' => $scale]);
    }

    // ─── Boolean ────────────────────────────────────────────────────────

    /** BOOLEAN (TINYINT(1)) */
    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'boolean');
    }

    // ─── Date & Time Types ──────────────────────────────────────────────

    /** DATE */
    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'date');
    }

    /** DATETIME */
    public function dateTime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'dateTime');
    }

    /** TIME */
    public function time(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'time');
    }

    /** TIMESTAMP */
    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'timestamp');
    }

    /** YEAR */
    public function year(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'year');
    }

    // ─── Binary Types ───────────────────────────────────────────────────

    /** BLOB */
    public function binary(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'binary');
    }

    /** MEDIUMBLOB */
    public function mediumBinary(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'mediumBinary');
    }

    /** LONGBLOB */
    public function longBinary(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'longBinary');
    }

    // ─── Special Types ──────────────────────────────────────────────────

    /** ENUM */
    public function enum(string $name, array $values): ColumnDefinition
    {
        return $this->addColumn($name, 'enum', ['values' => Sanitizer::enumValues($values)]);
    }

    /** SET */
    public function set(string $name, array $values): ColumnDefinition
    {
        return $this->addColumn($name, 'set', ['values' => Sanitizer::enumValues($values)]);
    }

    /** JSON */
    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'json');
    }

    /** UUID - CHAR(36) */
    public function uuid(string $name = 'uuid'): ColumnDefinition
    {
        return $this->addColumn($name, 'uuid');
    }

    /** IP Address - VARCHAR(45) */
    public function ipAddress(string $name = 'ip_address'): ColumnDefinition
    {
        return $this->addColumn($name, 'ipAddress');
    }

    /** MAC Address - VARCHAR(17) */
    public function macAddress(string $name = 'mac_address'): ColumnDefinition
    {
        return $this->addColumn($name, 'macAddress');
    }

    // ========================================================================
    // SHORTCUT METHODS - Kolom yang sering dipakai
    // ========================================================================

    /** Buat kolom created_at dan updated_at */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable()->useCurrent();
        $this->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
    }

    /** Buat kolom deleted_at untuk soft delete */
    public function softDeletes(string $column = 'deleted_at'): ColumnDefinition
    {
        return $this->timestamp($column)->nullable();
    }

    /** Buat kolom remember_token */
    public function rememberToken(): ColumnDefinition
    {
        return $this->string('remember_token', 100)->nullable();
    }

    /**
     * Buat kolom morphs ({name}_id dan {name}_type)
     * Dipakai untuk polymorphic relationship
     */
    public function morphs(string $name): void
    {
        $this->unsignedBigInteger("{$name}_id");
        $this->string("{$name}_type");
        $this->index(["{$name}_id", "{$name}_type"]);
    }

    /**
     * Buat kolom nullable morphs
     */
    public function nullableMorphs(string $name): void
    {
        $this->unsignedBigInteger("{$name}_id")->nullable();
        $this->string("{$name}_type")->nullable();
        $this->index(["{$name}_id", "{$name}_type"]);
    }

    /**
     * Kolom foreign key standar (unsignedBigInteger)
     * Contoh: $table->foreignId('user_id')->constrained()
     */
    public function foreignId(string $name): ColumnDefinition
    {
        return $this->unsignedBigInteger($name);
    }

    // ========================================================================
    // INDEX & CONSTRAINT METHODS
    // ========================================================================

    /**
     * Tambahkan composite PRIMARY KEY
     *
     * @param array $columns Nama kolom
     */
    public function primary(array $columns): void
    {
        foreach ($columns as $col) {
            Sanitizer::columnName($col);
        }
        // Set primary flag pada kolom yang sudah ada
        foreach ($this->columns as $column) {
            if (in_array($column->name, $columns, true)) {
                $column->isPrimary = true;
            }
        }
    }

    /**
     * Tambahkan composite UNIQUE constraint
     *
     * @param array|string $columns Nama kolom
     * @param string|null $name Nama constraint (opsional)
     */
    public function unique($columns, ?string $name = null): void
    {
        $columns = (array)$columns;
        foreach ($columns as $col) {
            Sanitizer::columnName($col);
        }

        if (count($columns) === 1) {
            // Single column unique - coba set di kolom langsung
            foreach ($this->columns as $column) {
                if ($column->name === $columns[0]) {
                    $column->isUnique = true;
                    return;
                }
            }
        }

        $this->compositeUniques[] = [
            'columns' => $columns,
            'name'    => $name,
        ];
    }

    /**
     * Tambahkan composite INDEX
     *
     * @param array|string $columns Nama kolom
     * @param string|null $name Nama index (opsional)
     */
    public function index($columns, ?string $name = null): void
    {
        $columns = (array)$columns;
        foreach ($columns as $col) {
            Sanitizer::columnName($col);
        }

        if (count($columns) === 1) {
            foreach ($this->columns as $column) {
                if ($column->name === $columns[0]) {
                    $column->isIndex = true;
                    return;
                }
            }
        }

        $this->compositeIndexes[] = [
            'columns' => $columns,
            'name'    => $name,
        ];
    }

    /**
     * Tambahkan Foreign Key
     *
     * @param string $column Nama kolom
     * @return ForeignKeyDefinition
     */
    public function foreign(string $column): ForeignKeyDefinition
    {
        $fk = new ForeignKeyDefinition($column);
        $this->foreignKeys[] = $fk;
        return $fk;
    }

    // ========================================================================
    // DROP / RENAME METHODS (untuk ALTER TABLE)
    // ========================================================================

    /** Hapus kolom */
    public function dropColumn($columns): void
    {
        $columns = (array)$columns;
        foreach ($columns as $col) {
            $this->dropColumns[] = Sanitizer::columnName($col);
        }
    }

    /** Rename kolom */
    public function renameColumn(string $from, string $to): void
    {
        $this->renameColumns[] = [
            'from' => Sanitizer::columnName($from),
            'to'   => Sanitizer::columnName($to),
        ];
    }

    /** Drop index by name */
    public function dropIndex(string $name): void
    {
        $this->dropIndexes[] = $name;
    }

    /** Drop unique constraint by name */
    public function dropUnique(string $name): void
    {
        $this->dropIndexes[] = $name;
    }

    /** Drop foreign key by name */
    public function dropForeign(string $name): void
    {
        $this->dropForeignKeys[] = $name;
    }

    /** Drop timestamps columns */
    public function dropTimestamps(): void
    {
        $this->dropColumn(['created_at', 'updated_at']);
    }

    /** Drop soft deletes column */
    public function dropSoftDeletes(string $column = 'deleted_at'): void
    {
        $this->dropColumn($column);
    }

    // ========================================================================
    // TABLE PROPERTIES
    // ========================================================================

    /** Set engine tabel (MySQL only) */
    public function engine(string $engine): self
    {
        $this->engine = preg_replace('/[^a-zA-Z0-9]/', '', $engine);
        return $this;
    }

    /** Set charset tabel */
    public function charset(string $charset): self
    {
        $this->charset = preg_replace('/[^a-zA-Z0-9_]/', '', $charset);
        return $this;
    }

    /** Set collation tabel */
    public function collation(string $collation): self
    {
        $this->collation = preg_replace('/[^a-zA-Z0-9_]/', '', $collation);
        return $this;
    }

    // ========================================================================
    // GETTER METHODS (dipakai oleh Grammar)
    // ========================================================================

    public function getTable(): string { return $this->table; }
    public function getColumns(): array { return $this->columns; }
    public function getForeignKeys(): array { return $this->foreignKeys; }
    public function getCompositeUniques(): array { return $this->compositeUniques; }
    public function getCompositeIndexes(): array { return $this->compositeIndexes; }
    public function getDropColumns(): array { return $this->dropColumns; }
    public function getRenameColumns(): array { return $this->renameColumns; }
    public function getDropIndexes(): array { return $this->dropIndexes; }
    public function getDropForeignKeys(): array { return $this->dropForeignKeys; }
    public function getEngine(): ?string { return $this->engine; }
    public function getCharset(): ?string { return $this->charset; }
    public function getCollation(): ?string { return $this->collation; }
    public function isCreate(): bool { return $this->isCreate; }

    /**
     * Ambil kolom yang di-set sebagai primary key
     *
     * @return array
     */
    public function getPrimaryColumns(): array
    {
        $primaries = [];
        foreach ($this->columns as $column) {
            if ($column->isPrimary) {
                $primaries[] = $column->name;
            }
        }
        return $primaries;
    }

    // ========================================================================
    // INTERNAL METHODS
    // ========================================================================

    /**
     * Tambahkan kolom ke blueprint
     *
     * @param string $name Nama kolom
     * @param string $type Tipe internal
     * @param array $parameters Parameter tambahan
     * @return ColumnDefinition
     */
    private function addColumn(string $name, string $type, array $parameters = []): ColumnDefinition
    {
        $column = new ColumnDefinition($name, $type, $parameters);
        $this->columns[] = $column;
        return $column;
    }
}
