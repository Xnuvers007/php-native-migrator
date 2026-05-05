<?php
// core/Schema/Grammar/PostgresGrammar.php
// ============================================================================
// PostgreSQL Grammar - Compiler SQL khusus untuk PostgreSQL
// ============================================================================

class PostgresGrammar implements GrammarInterface
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

        // Add columns
        foreach ($blueprint->getColumns() as $column) {
            if (!$column->change) {
                $statements[] = sprintf('ALTER TABLE %s ADD COLUMN %s', $table, $this->compileColumn($column));
            } else {
                // Alter column type
                $statements[] = sprintf(
                    'ALTER TABLE %s ALTER COLUMN %s TYPE %s', 
                    $table, 
                    $this->wrapIdentifier($column->name), 
                    $this->getColumnType($column)
                );
            }
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

        return $statements;
    }

    public function compileColumn(ColumnDefinition $column): string
    {
        $parts = [];
        $parts[] = $this->wrapIdentifier($column->name);

        if ($column->autoIncrement && $column->isPrimary) {
            $parts[] = $column->type === 'bigInteger' ? 'BIGSERIAL' : 'SERIAL';
            $parts[] = 'PRIMARY KEY';
            return implode(' ', $parts);
        }

        $parts[] = $this->getColumnType($column);

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
            'bigInteger'            => 'BIGINT',
            'integer'               => 'INTEGER',
            'tinyInteger', 'smallInteger' => 'SMALLINT',
            'mediumInteger'         => 'INTEGER',
            'string', 'char'        => sprintf('VARCHAR(%d)', $column->length ?: 255),
            'text', 'tinyText', 'mediumText', 'longText' => 'TEXT',
            'float', 'double'       => 'DOUBLE PRECISION',
            'decimal'               => sprintf('DECIMAL(%d,%d)', $column->precision ?: 8, $column->scale ?: 2),
            'boolean'               => 'BOOLEAN',
            'date'                  => 'DATE',
            'dateTime', 'timestamp' => 'TIMESTAMP',
            'time'                  => 'TIME',
            'year'                  => 'INTEGER',
            'binary', 'mediumBinary', 'longBinary' => 'BYTEA',
            'enum'                  => sprintf('VARCHAR(255) CHECK (%s IN (\'%s\'))', $this->wrapIdentifier($column->name), implode("','", $column->values)),
            'set'                   => 'TEXT',
            'json'                  => 'JSONB',
            'uuid'                  => 'UUID',
            'ipAddress'             => 'INET',
            'macAddress'            => 'MACADDR',
            default                 => 'VARCHAR(255)',
        };
    }

    private function compileDefaultValue(ColumnDefinition $column): string
    {
        if ($column->useCurrent) {
            return "CURRENT_TIMESTAMP";
        }

        $value = $column->defaultValue;

        if ($value === null) return 'NULL';
        if (is_bool($value)) return $value ? 'TRUE' : 'FALSE';
        if (is_int($value) || is_float($value)) return (string)$value;

        return sprintf("'%s'", str_replace("'", "''", (string)$value));
    }

    public function compileDrop(string $table): string
    {
        return sprintf('DROP TABLE %s CASCADE', $this->wrapIdentifier(Sanitizer::tableName($table)));
    }

    public function compileDropIfExists(string $table): string
    {
        return sprintf('DROP TABLE IF EXISTS %s CASCADE', $this->wrapIdentifier(Sanitizer::tableName($table)));
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
            "SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '%s'",
            str_replace("'", "''", Sanitizer::tableName($table))
        );
    }

    public function compileHasColumn(string $table, string $column): string
    {
        return sprintf(
            "SELECT COUNT(*) as cnt FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '%s' AND column_name = '%s'",
            str_replace("'", "''", Sanitizer::tableName($table)),
            str_replace("'", "''", Sanitizer::columnName($column))
        );
    }

    public function compileGetColumnListing(string $table): string
    {
        return sprintf(
            "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '%s'",
            str_replace("'", "''", Sanitizer::tableName($table))
        );
    }

    public function compileDropAllTables(): string
    {
        // For PostgreSQL, we drop all tables in public schema
        return "SELECT 'DROP TABLE IF EXISTS \"' || tablename || '\" CASCADE;' as query FROM pg_tables WHERE schemaname = 'public'";
    }

    public function compileTruncate(string $table): string
    {
        return sprintf('TRUNCATE TABLE %s RESTART IDENTITY CASCADE', $this->wrapIdentifier(Sanitizer::tableName($table)));
    }

    public function wrapIdentifier(string $identifier): string
    {
        if (str_starts_with($identifier, '"')) {
            return $identifier;
        }
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
