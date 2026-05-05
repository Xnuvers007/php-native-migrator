<?php
// core/Schema/Grammar/GrammarInterface.php
// ============================================================================
// Grammar Interface - Kontrak untuk SQL compiler berbagai database engine
// ============================================================================

interface GrammarInterface
{
    /**
     * Compile CREATE TABLE statement
     *
     * @param Blueprint $blueprint
     * @return array Array of SQL statements
     */
    public function compileCreate(Blueprint $blueprint): array;

    /**
     * Compile ALTER TABLE statements
     *
     * @param Blueprint $blueprint
     * @return array Array of SQL statements
     */
    public function compileAlter(Blueprint $blueprint): array;

    /**
     * Compile DROP TABLE statement
     *
     * @param string $table
     * @return string
     */
    public function compileDrop(string $table): string;

    /**
     * Compile DROP TABLE IF EXISTS statement
     *
     * @param string $table
     * @return string
     */
    public function compileDropIfExists(string $table): string;

    /**
     * Compile RENAME TABLE statement
     *
     * @param string $from
     * @param string $to
     * @return string
     */
    public function compileRename(string $from, string $to): string;

    /**
     * Compile query cek apakah tabel ada
     *
     * @param string $table
     * @return string
     */
    public function compileHasTable(string $table): string;

    /**
     * Compile query cek apakah kolom ada
     *
     * @param string $table
     * @param string $column
     * @return string
     */
    public function compileHasColumn(string $table, string $column): string;

    /**
     * Compile query ambil daftar kolom
     *
     * @param string $table
     * @return string
     */
    public function compileGetColumnListing(string $table): string;

    /**
     * Compile DROP ALL TABLES statement
     *
     * @return string
     */
    public function compileDropAllTables(): string;

    /**
     * Compile definisi satu kolom
     *
     * @param ColumnDefinition $column
     * @return string
     */
    public function compileColumn(ColumnDefinition $column): string;

    /**
     * Compile TRUNCATE TABLE statement
     *
     * @param string $table
     * @return string
     */
    public function compileTruncate(string $table): string;

    /**
     * Wrap identifier (nama tabel/kolom) dengan backtick/quotes
     *
     * @param string $identifier
     * @return string
     */
    public function wrapIdentifier(string $identifier): string;
}
