<?php
// stubs/migration.create.stub
// Template untuk migrasi CREATE TABLE

class m20260505_113104_create_users_table
{
    /**
     * Jalankan migrasi - Buat tabel baru
     */
    public function up(PDO $pdo): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Tambahkan kolom di sini...

            $table->timestamps();
        });
    }

    /**
     * Batalkan migrasi - Hapus tabel
     */
    public function down(PDO $pdo): void
    {
        Schema::dropIfExists('users');
    }
}
