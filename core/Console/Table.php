<?php
// core/Console/Table.php
// ============================================================================
// ASCII Table Formatter
// Menampilkan data dalam format tabel ASCII yang rapi di terminal
// ============================================================================

class ConsoleTable
{
    private array $headers = [];
    private array $rows = [];
    private array $columnWidths = [];
    private array $columnAligns = []; // 'left', 'center', 'right'

    /**
     * Set header tabel
     *
     * @param array $headers Array nama kolom
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        // Inisialisasi lebar kolom berdasarkan header
        foreach ($headers as $i => $header) {
            $this->columnWidths[$i] = mb_strlen(strip_tags($header));
        }

        return $this;
    }

    /**
     * Tambah satu baris data
     *
     * @param array $row Data baris
     * @return self
     */
    public function addRow(array $row): self
    {
        $this->rows[] = $row;

        // Update lebar kolom jika data lebih panjang
        foreach ($row as $i => $cell) {
            $plainCell = $this->stripAnsi((string)$cell);
            $len = mb_strlen($plainCell);
            if (!isset($this->columnWidths[$i]) || $len > $this->columnWidths[$i]) {
                $this->columnWidths[$i] = $len;
            }
        }

        return $this;
    }

    /**
     * Tambah beberapa baris sekaligus
     *
     * @param array $rows Array of arrays
     * @return self
     */
    public function addRows(array $rows): self
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
        return $this;
    }

    /**
     * Set alignment untuk kolom tertentu
     *
     * @param int $columnIndex Index kolom (0-based)
     * @param string $align 'left', 'center', atau 'right'
     * @return self
     */
    public function setColumnAlign(int $columnIndex, string $align): self
    {
        $this->columnAligns[$columnIndex] = $align;
        return $this;
    }

    /**
     * Render tabel dan return sebagai string
     *
     * @return string
     */
    public function render(): string
    {
        if (empty($this->headers) && empty($this->rows)) {
            return '';
        }

        $output = '';

        // Top border
        $output .= $this->renderBorder('top') . "\n";

        // Headers
        if (!empty($this->headers)) {
            $output .= $this->renderRow($this->headers, true) . "\n";
            $output .= $this->renderBorder('middle') . "\n";
        }

        // Rows
        foreach ($this->rows as $index => $row) {
            $output .= $this->renderRow($row) . "\n";
        }

        // Bottom border
        $output .= $this->renderBorder('bottom') . "\n";

        return $output;
    }

    /**
     * Render dan langsung tampilkan tabel
     */
    public function display(): void
    {
        echo $this->render();
    }

    /**
     * Render baris pembatas
     */
    private function renderBorder(string $position): string
    {
        $chars = match ($position) {
            'top'    => ['┌', '┬', '┐', '─'],
            'middle' => ['├', '┼', '┤', '─'],
            'bottom' => ['└', '┴', '┘', '─'],
        };

        $parts = [];
        foreach ($this->columnWidths as $width) {
            $parts[] = str_repeat($chars[3], $width + 2);
        }

        return Color::muted($chars[0] . implode($chars[1], $parts) . $chars[2]);
    }

    /**
     * Render satu baris data
     */
    private function renderRow(array $row, bool $isHeader = false): string
    {
        $cells = [];
        foreach ($this->columnWidths as $i => $width) {
            $cell = (string)($row[$i] ?? '');
            $plainCell = $this->stripAnsi($cell);
            $plainLen = mb_strlen($plainCell);
            $padding = $width - $plainLen;

            $align = $this->columnAligns[$i] ?? 'left';

            if ($isHeader) {
                // Headers selalu bold
                $cell = Color::bold($plainCell);
                $plainLen = mb_strlen($plainCell);
                $padding = $width - $plainLen;
            }

            $paddedCell = match ($align) {
                'right'  => str_repeat(' ', $padding) . $cell,
                'center' => str_repeat(' ', (int)floor($padding / 2)) . $cell . str_repeat(' ', (int)ceil($padding / 2)),
                default  => $cell . str_repeat(' ', max(0, $padding)),
            };

            $cells[] = ' ' . $paddedCell . ' ';
        }

        return Color::muted('│') . implode(Color::muted('│'), $cells) . Color::muted('│');
    }

    /**
     * Strip ANSI escape codes dari string
     */
    private function stripAnsi(string $text): string
    {
        return preg_replace('/\033\[[0-9;]*m/', '', $text);
    }

    /**
     * Buat tabel cepat (static helper)
     *
     * @param array $headers
     * @param array $rows
     * @return string
     */
    public static function quick(array $headers, array $rows): string
    {
        $table = new self();
        $table->setHeaders($headers);
        $table->addRows($rows);
        return $table->render();
    }
}
