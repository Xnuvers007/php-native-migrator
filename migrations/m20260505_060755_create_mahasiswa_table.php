<?php

class m20260505_060755_create_mahasiswa_table
{
    /**
     * Jalankan migrasi - Buat tabel mahasiswa
     */
    public function up(PDO $pdo): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->comment('Nama lengkap mahasiswa');
            $table->string('nim', 15)->unique()->comment('Nomor Induk Mahasiswa');
            $table->string('jurusan', 100);
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            $table->string('email')->nullable()->unique();
            $table->string('telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->decimal('ipk', 3, 2)->default(0.00);
            $table->integer('semester')->unsigned()->default(1);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Batalkan migrasi - Hapus tabel mahasiswa
     */
    public function down(PDO $pdo): void
    {
        Schema::dropIfExists('mahasiswa');
    }
}
