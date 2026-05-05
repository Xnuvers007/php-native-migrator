<div align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="200" alt="PHP Native Migrator" style="filter: hue-rotate(220deg);"/>
  <h1>🚀 PHP Native Migrator v2.1</h1>
  <p><b>Bawa kecanggihan Migration & Seeding ala Laravel ke proyek PHP murni Anda!</b></p>
  
  [![PHP Version](https://img.shields.io/badge/PHP-%E2%89%A5%208.0-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
  [![MySQL](https://img.shields.io/badge/MySQL-%E2%89%A5%205.7-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
  [![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://sqlite.org)
  [![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)
  [![No Dependencies](https://img.shields.io/badge/Dependencies-0-success?style=for-the-badge)](#)
  [![Security](https://img.shields.io/badge/Security-Military_Grade-red?style=for-the-badge&logo=security&logoColor=white)](#)

  <p>
    <a href="README.md"><b>🇺🇸 Read in English</b></a> •
    <a href="#-daftar-isi"><b>Dokumentasi</b></a> •
    <a href="#-panduan-cepat-quick-start"><b>Mulai Cepat</b></a>
  </p>
</div>

---

Pusing harus bolak-balik buka phpMyAdmin untuk *export/import* file `.sql` secara manual? Sedang mengerjakan proyek PHP *Native* (atau sistem *legacy* lama) tapi merindukan otomasi *database* yang rapi dan elegan seperti di Laravel?

**PHP Native Migrator** adalah *engine* migrasi dan seeder database yang **100% mandiri dan tanpa Framework**. Proyek ini tidak butuh Composer, tidak butuh *library* dari luar, dan berjalan sangat cepat. Cukup *drag and drop* ke dalam proyek Anda, atur koneksi, dan mulailah mengelola *database* lewat terminal!

---

## 📑 Daftar Isi

- [✨ Fitur Unggulan](#-fitur-unggulan)
- [❓ Kenapa Pilih Alat Ini?](#-kenapa-pilih-alat-ini)
- [📥 Instalasi](#-instalasi-cukup-drag--drop)
- [🚀 Panduan Cepat](#-panduan-cepat-quick-start)
- [🏗️ Referensi Schema Builder](#️-referensi-schema-builder-api)
- [🌱 Sistem Seeding](#-sistem-database-seeding)
- [🛠️ Daftar Lengkap Perintah](#️-daftar-lengkap-perintah-cli-commands)
- [🛡️ Arsitektur Keamanan](#️-arsitektur-keamanan-lapis-baja)
- [📂 Struktur Folder](#-struktur-folder)

---

## ✨ Fitur Unggulan

*   **🏗️ Fluent Schema Builder**: Tulis tabel database menggunakan sintaks PHP yang cantik dan berantai (contoh: `$table->string('email')->unique();`). Selamat tinggal error _syntax_ SQL mentah!
*   **🤖 Auto-Discovery Seeders**: Buat data palsu/awal tanpa ribet. Semua Seeder otomatis terdeteksi dari folder `seeders` dan dieksekusi sesuai urutan abjad.
*   **🎨 CLI yang Cantik**: Antarmuka terminal interaktif, penuh warna, dan punya deteksi salah ketik (*typo*) layaknya alat profesional.
*   **🗄️ Multi-Driver**: Mendukung penuh **MySQL / MariaDB** dan **SQLite** dengan kompilasi otomatis di belakang layar.
*   **🔄 Rollback & Reset**: Lakukan kesalahan saat membuat tabel? Cukup lakukan *rollback* state database Anda per *batch* atau per langkah.
*   **🛡️ Keamanan Kelas Militer**: Proteksi bawaan terhadap serangan SQL Injection (PDO Prepared Statements), Path Traversal (PathGuard), dan Command/RCE Injection (Sanitizer).
*   **📦 Nol Dependensi**: Dibangun murni menggunakan PHP murni. Taruh di mana saja, dan pasti langsung jalan.

---

## ❓ Kenapa Pilih Alat Ini?

Di dunia *web development* modern, Framework seperti Laravel atau Symfony menyediakan ORM dan sistem migrasi yang luar biasa. Namun, bagaimana jika Anda:
1. Sedang me- *maintain* aplikasi PHP kuno (*legacy*)?
2. Sedang membuat API super ringan menggunakan PHP murni tanpa mau dibebani Framework raksasa?
3. Sedang belajar bagaimana mesin kompilasi *database* (Schema Builder) bekerja di belakang layar?

Meng- *install* Doctrine atau Eloquent via Composer ke proyek PHP murni seringkali membawa puluhan *dependency* ekstra yang memberatkan. **PHP Native Migrator memberikan Anda pengalaman Migrasi dan CLI persis seperti Laravel dengan ukuran ekstra 0 bytes dari pihak ketiga!**

---

## 📥 Instalasi (Cukup Drag & Drop)

1. **Download** repositori ini dari Github.
2. **Copy** semua file dan folder langsung ke folder *root* proyek PHP Anda.
3. **Ubah nama** `.env.example` menjadi `.env`.
4. **Konfigurasikan** akses database Anda di file `.env`.

Selesai! Buka Terminal/CMD Anda dan ketik `php artisan list` untuk melihat keajaibannya.

---

## 🚀 Panduan Cepat (Quick Start)

### 1. Atur `.env` Anda
```env
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aplikasi_keren_saya
DB_USERNAME=root
DB_PASSWORD=rahasia
```

### 2. Buat File Migrasi
```bash
php artisan make:migration create_users_table
```
Perintah ini akan membuat file pintar bernama *timestamp* di dalam folder `migrations/` (cth: `m20260505_120000_create_users_table.php`).

### 3. Tulis Kolom Anda (Schema)
Buka file yang baru dibuat tadi. Sistem akan otomatis mendeteksi bahwa Anda ingin membuat tabel `users` dan menyiapkan kerangkanya!
```php
public function up(PDO $pdo): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();                                    // BIGINT PRIMARY KEY AUTO_INCREMENT
        $table->string('username', 50)->unique();        // VARCHAR(50) UNIK
        $table->string('email')->unique();               // VARCHAR(255) UNIK
        $table->enum('role', ['admin', 'user']);         // ENUM
        $table->boolean('is_active')->default(true);     // TINYINT(1) DEFAULT 1
        $table->timestamps();                            // created_at & updated_at
    });
}

public function down(PDO $pdo): void
{
    Schema::dropIfExists('users');
}
```

### 4. Migrate!
```bash
php artisan migrate
```
Tabel Anda kini sudah berhasil terbuat dengan aman di *database*. Anda dapat melihat status antreannya menggunakan `php artisan migrate:status`.

---

## 🏗️ Referensi Schema Builder API

Objek `$table` menyediakan banyak sekali tipe data untuk keperluan Anda.

<details open>
<summary><b>🔥 Tipe Kolom Populer</b></summary>

| Perintah | Tipe Database | Deskripsi |
|--------|--------------|-------------|
| `$table->id()` | `BIGINT` | Primary key yang berurut otomatis |
| `$table->string('name', 100)` | `VARCHAR(100)` | Teks dengan batasan (default 255) |
| `$table->text('desc')` | `TEXT` | Teks standar panjang |
| `$table->longText('body')` | `LONGTEXT` | Teks super panjang (artikel) |
| `$table->integer('qty')` | `INT` | Angka bulat standar |
| `$table->bigInteger('views')`| `BIGINT` | Angka bulat yang sangat besar |
| `$table->decimal('price', 8, 2)`| `DECIMAL(8,2)` | Angka desimal (biasanya untuk uang) |
| `$table->boolean('active')` | `TINYINT(1)` | Nilai benar/salah (True/False) |
| `$table->date('birthday')` | `DATE` | Tanggal tanpa jam |
| `$table->dateTime('login')` | `DATETIME` | Tanggal beserta jam |
| `$table->json('data')` | `JSON` | Format teks JSON |
| `$table->enum('role', ['A','B'])`| `ENUM` | Nilai pilihan terbatas |
| `$table->uuid('uuid')` | `CHAR(36)` | String acak UUID |
</details>

<details open>
<summary><b>✨ Sifat Kolom (Modifiers)</b></summary>

Modifiers ini bisa disambung di belakang tipe data.
```php
$table->string('phone')->nullable();           // Boleh dikosongkan (NULL)
$table->integer('age')->unsigned();            // Angka harus positif
$table->string('status')->default('pending');  // Beri nilai otomatis
$table->string('email')->unique();             // Data tidak boleh sama (Unik)
$table->string('bio')->after('email');         // Taruh di sebelah kolom (Khusus MySQL)
$table->string('id')->first();                 // Taruh di paling awal tabel
$table->string('title')->comment('App Title'); // Tambahkan komentar di database
```
</details>

<details open>
<summary><b>🔗 Relasi Antar Tabel (Foreign Keys)</b></summary>

Kaitkan antar tabel dengan efek hapus/ubah otomatis.
```php
$table->foreignId('department_id'); // Otomatis membuat BIGINT UNSIGNED

$table->foreign('department_id')
      ->references('id')
      ->on('departments')
      ->cascadeOnDelete()           // Hapus ini jika department dihapus
      ->nullOnUpdate();             // Kosongkan ini jika department diubah
```
</details>

---

## 🌱 Sistem Database Seeding

Seeder memudahkan Anda mengisi data palsu atau admin tanpa *insert* manual.

### 1. Buat File Seeder
```bash
php artisan make:seeder ProductSeeder
```

### 2. Tulis Data (Gunakan asisten super aman)
```php
class ProductSeeder
{
    public function run(PDO $pdo): void
    {
        // Masukkan 1 baris
        Database::insert('products', [
            'nama'  => 'Kopi Susu',
            'harga' => 15000,
            'stok'  => 100
        ]);

        // Masukkan banyak baris sekaligus
        Database::insertMany('products', ['nama', 'harga', 'stok'], [
            ['Teh Hijau', 10000, 50],
            ['Susu Coklat', 18500, 30]
        ]);
        
        // Bungkus pakai transaksi agar sangat aman!
        Database::transaction(function ($pdo) {
             Database::insert('categories', ['nama' => 'Minuman']);
        });
    }
}
```

### 3. Eksekusi
Jalankan saja:
```bash
php artisan db:seed
```
*Catatan: Sistem akan mendeteksi file Seeder Anda dan menjalankannya secara berurutan!*

---

## 🛠️ Daftar Lengkap Perintah (CLI Commands)

| Perintah | Deskripsi |
|---------|--------|
| `php artisan migrate` | Mengeksekusi seluruh migrasi yang tertunda |
| `php artisan migrate:status` | Menampilkan tabel ASCII status tiap file |
| `php artisan migrate:rollback` | Membatalkan *batch* terakhir yang dikerjakan |
| `php artisan migrate:rollback --step=N`| Membatalkan spesifik tepat `N` file |
| `php artisan migrate:reset` | Membatalkan/menghapus semua migrasi |
| `php artisan migrate:refresh` | Mereset dan menjalankan migrasi ulang |
| `php artisan migrate:fresh` | **Hapus semua tabel** dan kerjakan migrasi ulang |
| `php artisan migrate:fresh --seed` | Hapus tabel, migrate ulang, dan jalankan seeder |
| `php artisan make:migration Name` | Buat file cetakan migrasi baru |
| `php artisan make:seeder Name` | Buat file data seeder baru |
| `php artisan db:seed` | Eksekusi seluruh seeder yang ditemukan otomatis |
| `php artisan db:wipe` | Menghapus semua tabel (Tanpa menjalankan migrasi lagi) |

---

## 🛡️ Arsitektur Keamanan Lapis Baja

Membangun alat berbasis terminal dengan PHP berisiko tinggi terhadap eksploitasi server. Alat ini dirancang dengan pengamanan lapis ganda:

1. **Proteksi SQL Injection**: Sistem asisten penambah data (`Database::insert` & `insertMany`) tidak menempelkan data mentah. Secara otomatis semuanya dialihkan ke **PDO Prepared Statements**. 
2. **Penangkal Path Traversal & LFI**: 
   - Class `PathGuard` mencegah pemuatan file asing.
   - Menggugurkan injeksi Karakter Kosong / Null Byte (`\0`).
   - Secara fisik memvalidasi alamat path memakai `realpath()`, memastikan tak ada *Hacker* yang memanggil file `../../../etc/passwd` alih-alih `migrations/`.
3. **Penangkal Remote Code Execution (RCE)**: 
   - Karena generator membuat nama *class* dan menyimpannya ke file, maka *input* dari pengguna dibersihkan secara ekstrem oleh `Sanitizer`. Pemblokiran dilakukan jika terdeteksi penggunaan `eval()`, *backticks* (\`), atau tag eksekusi PHP pada *parameter command*.
4. **Isolasi Variabel (*Environment*)**: Menjamin isi file `.env` sistem operasi komputer Anda tidak tertimpa oleh *parser* `.env` aplikasi.

---

## 📂 Struktur Folder

Untuk apa saja file aslinya digunakan?

```text
├── core/
│   ├── Bootstrap.php          # Pemuat Class (Autoloader) tanpa Composer
│   ├── Database.php           # Konektor PDO & Generator Query yang aman
│   ├── DotEnv.php             # Pembaca .env yang anti jebol
│   ├── Migrator.php           # Mesin utama Migrasi & Batching
│   ├── Seeder.php             # Mesin utama Seeder & Pencarian File Otomatis
│   ├── Console/               # Router Terminal & Penampil Tabel/Warna
│   ├── Schema/                # Jantung Schema Builder & Bahasa MySQL/SQLite
│   └── Security/              # Pengawal PathGuard dan Sanitizer (Anti Hack)
```
*(Anda hampir tidak akan pernah perlu menyentuh file di dalam folder `core/`. Biarkan mereka yang mengurusi sihir ajaibnya di belakang layar!)*

---

## 🤝 Berkontribusi & Lisensi

Silakan sumbangkan kode, buka permasalahan (*issue*), atau salin repositori ini secara bebas!
Perangkat lunak ini dikembangkan terbuka di bawah payung lisensi **[MIT license](https://opensource.org/licenses/MIT)**.

<div align="center">
  <b>Dibuat dengan ❤️ menggunakan 100% PHP Native • Dilarang Keras Pakai Framework</b>
</div>
