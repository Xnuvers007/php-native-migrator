<?php
// stubs/migration.update.stub
// Template untuk migrasi ALTER TABLE

class m20260505_113105_add_phone_to_users
{
    /**
     * Jalankan migrasi - Modifikasi tabel
     */
    public function up(PDO $pdo): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambah/modifikasi kolom di sini...
        });
    }

    /**
     * Batalkan migrasi
     */
    public function down(PDO $pdo): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kembalikan perubahan di sini...
        });
    }
}
