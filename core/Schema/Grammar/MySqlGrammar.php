<?php
// core/Schema/Grammar/MySqlGrammar.php
// ============================================================================
// MySQL Grammar - Compiler SQL khusus untuk MySQL/MariaDB
// Menghasilkan query DDL yang aman dan optimal untuk MySQL
// ============================================================================

class MySqlGrammar implements GrammarInterface
{
    /**
     * Compile CREATE TABLE statement
     */
    public function compileCreate(Blueprint $blueprint): array
    {
        $table = $this->wrapIdentifier($blueprint->getTable());
        $statements = [];

        // Kumpulkan definisi kolom
        $columnDefs = [];
        foreach ($blueprint->getColumns() as $column) {
            $columnDefs[] = $this->compileColumn($column);
        }

        // Kumpulkan PRIMARY KEY constraint (untuk composite PK)
        $primaryColumns = $blueprint->getPrimaryColumns();
        if (!empty($primaryColumns) && count($primaryColumns) > 1) {
            $wrapped = array_map([$this, 'wrapIdentifier'], $primaryColumns);
            $columnDefs[] = 'PRIMARY KEY (' . implode(', ', $wrapped) . ')';
        }

        // Kumpulkan UNIQUE constraints (composite)
        foreach ($blueprint->getCompositeUniques() as $unique) {
            $name = $unique['name'] ?? $this->generateIndexName($blueprint->getTable(), $unique['columns'], 'unique');
            $wrapped = array_map([$this, 'wrapIdentifier'], $unique['columns']);
            $columnDefs[] = sprintf('UNIQUE KEY %s (%s)', $this->wrapIdentifier($name), implode(', ', $wrapped));
        }

        // Kumpulkan INDEX (composite)
        foreach ($blueprint->getCompositeIndexes() as $index) {
            $name = $index['name'] ?? $this->generateIndexName($blueprint->getTable(), $index['columns'], 'index');
            $wrapped = array_map([$this, 'wrapIdentifier'], $index['columns']);
            $columnDefs[] = sprintf('INDEX %s (%s)', $this->wrapIdentifier($name), implode(', ', $wrapped));
        }

        // Bangun CREATE TABLE
        $engine = $blueprint->getEngine() ?: 'InnoDB';
        $charset = $blueprint->getCharset() ?: 'utf8mb4';
        $collation = $blueprint->getCollation() ?: 'utf8mb4_unicode_ci';

        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS %s (\n  %s\n) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s",
            $table,
            implode(",\n  ", $columnDefs),
            $engine,
            $charset,
            $collation
        );

        $statements[] = $sql;

        // Foreign key statements (terpisah agar lebih aman)
        foreach ($blueprint->getForeignKeys() as $foreign) {
            $statements[] = $this->compileForeignKey($blueprint->getTable(), $foreign);
        }

        return $statements;
    }

    /**
     * Compile ALTER TABLE statements
     */
    public function compileAlter(Blueprint $blueprint): array
    {
        $table = $this->wrapIdentifier($blueprint->getTable());
        $statements = [];

        // Kolom baru (ADD COLUMN)
        foreach ($blueprint->getColumns() as $column) {
            if ($column->change) {
                // MODIFY COLUMN
                $sql = sprintf('ALTER TABLE %s MODIFY COLUMN %s', $table, $this->compileColumn($column));
            } else {
                // ADD COLUMN
                $sql = sprintf('ALTER TABLE %s ADD COLUMN %s', $table, $this->compileColumn($column));
            }

            if ($column->after) {
                $sql .= ' AFTER ' . $this->wrapIdentifier($column->after);
            } elseif ($column->first) {
                $sql .= ' FIRST';
            }

            $statements[] = $sql;
        }

        // Drop columns
        foreach ($blueprint->getDropColumns() as $colName) {
            $statements[] = sprintf('ALTER TABLE %s DROP COLUMN %s', $table, $this->wrapIdentifier($colName));
        }

        // Rename columns
        foreach ($blueprint->getRenameColumns() as $rename) {
            $statements[] = sprintf(
                'ALTER TABLE %s RENAME COLUMN %s TO %s',
                $table,
                $this->wrapIdentifier($rename['from']),
                $this->wrapIdentifier($rename['to'])
            );
        }

        // Add indexes
        foreach ($blueprint->getCompositeIndexes() as $index) {
            $name = $index['name'] ?? $this->generateIndexName($blueprint->getTable(), $index['columns'], 'index');
            $wrapped = array_map([$this, 'wrapIdentifier'], $index['columns']);
            $statements[] = sprintf(
                'ALTER TABLE %s ADD INDEX %s (%s)',
                $table,
                $this->wrapIdentifier($name),
                implode(', ', $wrapped)
            );
        }

        // Add unique indexes
        foreach ($blueprint->getCompositeUniques() as $unique) {
            $name = $unique['name'] ?? $this->generateIndexName($blueprint->getTable(), $unique['columns'], 'unique');
            $wrapped = array_map([$this, 'wrapIdentifier'], $unique['columns']);
            $statements[] = sprintf(
                'ALTER TABLE %s ADD UNIQUE %s (%s)',
                $table,
                $this->wrapIdentifier($name),
                implode(', ', $wrapped)
            );
        }

        // Drop indexes
        foreach ($blueprint->getDropIndexes() as $indexName) {
            $statements[] = sprintf('ALTER TABLE %s DROP INDEX %s', $table, $this->wrapIdentifier($indexName));
        }

        // Drop foreign keys
        foreach ($blueprint->getDropForeignKeys() as $fkName) {
            $statements[] = sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $table, $this->wrapIdentifier($fkName));
        }

        // Add foreign keys
        foreach ($blueprint->getForeignKeys() as $foreign) {
            $statements[] = $this->compileForeignKey($blueprint->getTable(), $foreign);
        }

        return $statements;
    }

    /**
     * Compile definisi satu kolom
     */
    public function compileColumn(ColumnDefinition $column): string
    {
        $parts = [];

        // Nama kolom
        $parts[] = $this->wrapIdentifier($column->name);

        // Tipe data
        $parts[] = $this->getColumnType($column);

        // UNSIGNED
        if ($column->unsigned && $this->isNumericType($column->type)) {
            $parts[] = 'UNSIGNED';
        }

        // CHARACTER SET
        if ($column->charset) {
            $parts[] = 'CHARACTER SET ' . $column->charset;
        }

        // COLLATE
        if ($column->collation) {
            $parts[] = 'COLLATE ' . $column->collation;
        }

        // NULL / NOT NULL
        if ($column->nullable) {
            $parts[] = 'NULL';
        } else {
            $parts[] = 'NOT NULL';
        }

        // AUTO_INCREMENT
        if ($column->autoIncrement) {
            $parts[] = 'AUTO_INCREMENT';
        }

        // DEFAULT
        if ($column->hasDefault) {
            $parts[] = 'DEFAULT ' . $this->compileDefaultValue($column);
        }

        // ON UPDATE CURRENT_TIMESTAMP
        if ($column->useCurrentOnUpdate) {
            $parts[] = 'ON UPDATE CURRENT_TIMESTAMP';
        }

        // PRIMARY KEY (single column)
        if ($column->isPrimary) {
            $parts[] = 'PRIMARY KEY';
        }

        // UNIQUE (single column)
        if ($column->isUnique && !$column->isPrimary) {
            $parts[] = 'UNIQUE';
        }

        // COMMENT
        if ($column->comment) {
            $parts[] = sprintf("COMMENT '%s'", addslashes($column->comment));
        }

        return implode(' ', $parts);
    }

    /**
     * Get tipe data MySQL dari tipe internal
     */
    private function getColumnType(ColumnDefinition $column): string
    {
        return match ($column->type) {
            'bigInteger'       => 'BIGINT',
            'integer'          => 'INT',
            'tinyInteger'      => 'TINYINT',
            'smallInteger'     => 'SMALLINT',
            'mediumInteger'    => 'MEDIUMINT',
            'string'           => sprintf('VARCHAR(%d)', $column->length ?: 255),
            'char'             => sprintf('CHAR(%d)', $column->length ?: 255),
            'text'             => 'TEXT',
            'tinyText'         => 'TINYTEXT',
            'mediumText'       => 'MEDIUMTEXT',
            'longText'         => 'LONGTEXT',
            'float'            => sprintf('FLOAT(%d,%d)', $column->precision ?: 8, $column->scale ?: 2),
            'double'           => sprintf('DOUBLE(%d,%d)', $column->precision ?: 15, $column->scale ?: 8),
            'decimal'          => sprintf('DECIMAL(%d,%d)', $column->precision ?: 8, $column->scale ?: 2),
            'boolean'          => 'TINYINT(1)',
            'date'             => 'DATE',
            'dateTime'         => 'DATETIME',
            'time'             => 'TIME',
            'timestamp'        => 'TIMESTAMP',
            'year'             => 'YEAR',
            'binary'           => 'BLOB',
            'mediumBinary'     => 'MEDIUMBLOB',
            'longBinary'       => 'LONGBLOB',
            'enum'             => sprintf("ENUM('%s')", implode("','", $column->values)),
            'set'              => sprintf("SET('%s')", implode("','", $column->values)),
            'json'             => 'JSON',
            'uuid'             => 'CHAR(36)',
            'ipAddress'        => 'VARCHAR(45)',
            'macAddress'       => 'VARCHAR(17)',
            default            => 'VARCHAR(255)',
        };
    }

    /**
     * Compile nilai default
     */
    private function compileDefaultValue(ColumnDefinition $column): string
    {
        if ($column->useCurrent) {
            return 'CURRENT_TIMESTAMP';
        }

        $value = $column->defaultValue;

        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        // String value - escape untuk keamanan
        return sprintf("'%s'", addslashes((string)$value));
    }

    /**
     * Cek apakah tipe termasuk numerik
     */
    private function isNumericType(string $type): bool
    {
        return in_array($type, [
            'bigInteger', 'integer', 'tinyInteger', 'smallInteger',
            'mediumInteger', 'float', 'double', 'decimal',
        ], true);
    }

    /**
     * Compile foreign key constraint
     */
    private function compileForeignKey(string $table, ForeignKeyDefinition $foreign): string
    {
        $constraintName = $foreign->getConstraintName($table);

        return sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s ON UPDATE %s',
            $this->wrapIdentifier($table),
            $this->wrapIdentifier($constraintName),
            $this->wrapIdentifier($foreign->column),
            $this->wrapIdentifier($foreign->onTable),
            $this->wrapIdentifier($foreign->referencesColumn),
            $foreign->onDeleteAction,
            $foreign->onUpdateAction
        );
    }

    /**
     * Generate nama index otomatis
     */
    private function generateIndexName(string $table, array $columns, string $type): string
    {
        return $table . '_' . implode('_', $columns) . '_' . $type;
    }

    // ============================
    // Interface Methods
    // ============================

    public function compileDrop(string $table): string
    {
        return sprintf('DROP TABLE %s', $this->wrapIdentifier(Sanitizer::tableName($table)));
    }

    public function compileDropIfExists(string $table): string
    {
        return sprintf('DROP TABLE IF EXISTS %s', $this->wrapIdentifier(Sanitizer::tableName($table)));
    }

    public function compileRename(string $from, string $to): string
    {
        return sprintf(
            'RENAME TABLE %s TO %s',
            $this->wrapIdentifier(Sanitizer::tableName($from)),
            $this->wrapIdentifier(Sanitizer::tableName($to))
        );
    }

    public function compileHasTable(string $table): string
    {
        return sprintf(
            "SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s'",
            addslashes(Sanitizer::tableName($table))
        );
    }

    public function compileHasColumn(string $table, string $column): string
    {
        return sprintf(
            "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s'",
            addslashes(Sanitizer::tableName($table)),
            addslashes(Sanitizer::columnName($column))
        );
    }

    public function compileGetColumnListing(string $table): string
    {
        return sprintf(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s' ORDER BY ORDINAL_POSITION",
            addslashes(Sanitizer::tableName($table))
        );
    }

    public function compileDropAllTables(): string
    {
        return "SELECT CONCAT('DROP TABLE IF EXISTS `', TABLE_NAME, '`;') as query FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()";
    }

    public function compileTruncate(string $table): string
    {
        return sprintf('TRUNCATE TABLE %s', $this->wrapIdentifier(Sanitizer::tableName($table)));
    }

    public function wrapIdentifier(string $identifier): string
    {
        // Jangan wrap jika sudah di-wrap
        if (str_starts_with($identifier, '`')) {
            return $identifier;
        }
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
