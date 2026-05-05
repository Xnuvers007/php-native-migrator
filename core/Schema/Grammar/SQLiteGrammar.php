<?php
// core/Schema/Grammar/SQLiteGrammar.php
// ============================================================================
// SQLite Grammar - Compiler SQL khusus untuk SQLite
// ============================================================================

class SQLiteGrammar implements GrammarInterface
{
    public function compileCreate(Blueprint $blueprint): array
    {
        $table = $this->wrapIdentifier($blueprint->getTable());
        $statements = [];
        $columnDefs = [];

        foreach ($blueprint->getColumns() as $column) {
            $columnDefs[] = $this->compileColumn($column);
        }

        // Composite primary key
        $primaryColumns = $blueprint->getPrimaryColumns();
        if (!empty($primaryColumns) && count($primaryColumns) > 1) {
            $wrapped = array_map([$this, 'wrapIdentifier'], $primaryColumns);
            $columnDefs[] = 'PRIMARY KEY (' . implode(', ', $wrapped) . ')';
        }

        // Composite unique
        foreach ($blueprint->getCompositeUniques() as $unique) {
            $wrapped = array_map([$this, 'wrapIdentifier'], $unique['columns']);
            $columnDefs[] = sprintf('UNIQUE (%s)', implode(', ', $wrapped));
        }

        // Foreign keys inline
        foreach ($blueprint->getForeignKeys() as $foreign) {
            $columnDefs[] = sprintf(
                'FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s ON UPDATE %s',
                $this->wrapIdentifier($foreign->column),
                $this->wrapIdentifier($foreign->onTable),
                $this->wrapIdentifier($foreign->referencesColumn),
                $foreign->onDeleteAction,
                $foreign->onUpdateAction
            );
        }

        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS %s (\n  %s\n)",
            $table,
            implode(",\n  ", $columnDefs)
        );

        $statements[] = $sql;

        // Create indexes
        foreach ($blueprint->getCompositeIndexes() as $index) {
            $name = $index['name'] ?? $blueprint->getTable() . '_' . implode('_', $index['columns']) . '_index';
            $wrapped = array_map([$this, 'wrapIdentifier'], $index['columns']);
            $statements[] = sprintf(
                'CREATE INDEX IF NOT EXISTS %s ON %s (%s)',
                $this->wrapIdentifier($name),
                $table,
                implode(', ', $wrapped)
            );
        }

        return $statements;
    }

    public function compileAlter(Blueprint $blueprint): array
    {
        $table = $this->wrapIdentifier($blueprint->getTable());
        $statements = [];

        // SQLite hanya mendukung ADD COLUMN
        foreach ($blueprint->getColumns() as $column) {
            if (!$column->change) {
                $statements[] = sprintf('ALTER TABLE %s ADD COLUMN %s', $table, $this->compileColumn($column));
            }
        }

        // SQLite tidak mendukung DROP COLUMN sebelum versi 3.35.0
        foreach ($blueprint->getDropColumns() as $colName) {
            $statements[] = sprintf('ALTER TABLE %s DROP COLUMN %s', $table, $this->wrapIdentifier($colName));
        }

        foreach ($blueprint->getRenameColumns() as $rename) {
            $statements[] = sprintf(
                'ALTER TABLE %s RENAME COLUMN %s TO %s',
                $table,
                $this->wrapIdentifier($rename['from']),
                $this->wrapIdentifier($rename['to'])
            );
        }

        return $statements;
    }

    public function compileColumn(ColumnDefinition $column): string
    {
        $parts = [];
        $parts[] = $this->wrapIdentifier($column->name);
        $parts[] = $this->getColumnType($column);

        if ($column->isPrimary && $column->autoIncrement) {
            // SQLite khusus: INTEGER PRIMARY KEY = auto increment
            return $this->wrapIdentifier($column->name) . ' INTEGER PRIMARY KEY AUTOINCREMENT';
        }

        if (!$column->nullable) {
            $parts[] = 'NOT NULL';
        }

        if ($column->hasDefault) {
            $parts[] = 'DEFAULT ' . $this->compileDefaultValue($column);
        }

        if ($column->isPrimary) {
            $parts[] = 'PRIMARY KEY';
        }

        if ($column->isUnique && !$column->isPrimary) {
            $parts[] = 'UNIQUE';
        }

        return implode(' ', $parts);
    }

    private function getColumnType(ColumnDefinition $column): string
    {
        return match ($column->type) {
            'bigInteger', 'integer', 'tinyInteger', 'smallInteger', 'mediumInteger' => 'INTEGER',
            'string', 'char'        => 'TEXT',
            'text', 'tinyText', 'mediumText', 'longText' => 'TEXT',
            'float', 'double', 'decimal' => 'REAL',
            'boolean'               => 'INTEGER',
            'date', 'dateTime', 'time', 'timestamp', 'year' => 'TEXT',
            'binary', 'mediumBinary', 'longBinary' => 'BLOB',
            'enum', 'set'           => 'TEXT',
            'json'                  => 'TEXT',
            'uuid'                  => 'TEXT',
            'ipAddress'             => 'TEXT',
            'macAddress'            => 'TEXT',
            default                 => 'TEXT',
        };
    }

    private function compileDefaultValue(ColumnDefinition $column): string
    {
        if ($column->useCurrent) {
            return "CURRENT_TIMESTAMP";
        }

        $value = $column->defaultValue;

        if ($value === null) return 'NULL';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_int($value) || is_float($value)) return (string)$value;

        return sprintf("'%s'", str_replace("'", "''", (string)$value));
    }

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
        return sprintf('ALTER TABLE %s RENAME TO %s',
            $this->wrapIdentifier(Sanitizer::tableName($from)),
            $this->wrapIdentifier(Sanitizer::tableName($to))
        );
    }

    public function compileHasTable(string $table): string
    {
        return sprintf(
            "SELECT COUNT(*) as cnt FROM sqlite_master WHERE type='table' AND name='%s'",
            str_replace("'", "''", Sanitizer::tableName($table))
        );
    }

    public function compileHasColumn(string $table, string $column): string
    {
        return sprintf("PRAGMA table_info('%s')", str_replace("'", "''", Sanitizer::tableName($table)));
    }

    public function compileGetColumnListing(string $table): string
    {
        return sprintf("PRAGMA table_info('%s')", str_replace("'", "''", Sanitizer::tableName($table)));
    }

    public function compileDropAllTables(): string
    {
        return "SELECT name, 'DROP TABLE IF EXISTS \"' || name || '\";' as query FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'";
    }

    public function compileTruncate(string $table): string
    {
        return sprintf('DELETE FROM %s', $this->wrapIdentifier(Sanitizer::tableName($table)));
    }

    public function wrapIdentifier(string $identifier): string
    {
        if (str_starts_with($identifier, '"')) {
            return $identifier;
        }
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
