<div align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="200" alt="PHP Native Migrator" style="filter: hue-rotate(220deg);"/>
  <h1>🚀 PHP Native Migrator v2.1</h1>
  <p><b>Bring the power of Laravel's Database Migration & Seeding to your raw PHP projects!</b></p>
  
  [![PHP Version](https://img.shields.io/badge/PHP-%E2%89%A5%208.0-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
  [![MySQL](https://img.shields.io/badge/MySQL-%E2%89%A5%205.7-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
  [![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://sqlite.org)
  [![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)
  [![No Dependencies](https://img.shields.io/badge/Dependencies-0-success?style=for-the-badge)](#)
  [![Security](https://img.shields.io/badge/Security-Military_Grade-red?style=for-the-badge&logo=security&logoColor=white)](#)

  <p>
    <a href="README-id.md"><b>🇮🇩 Baca dalam Bahasa Indonesia</b></a> •
    <a href="#-table-of-contents"><b>Documentation</b></a> •
    <a href="#-quick-start"><b>Quick Start</b></a>
  </p>
</div>

---

Tired of exporting and importing `.sql` files manually? Are you working on a native PHP project (or a legacy system) but miss the elegant database automation from modern frameworks like Laravel?

**PHP Native Migrator** is a **100% dependency-free, framework-agnostic** database migration and seeding engine. It requires NO Composer, NO external libraries, and runs incredibly fast. Just drag and drop the files into your project, configure your database, and start managing your schema via terminal!

---

## 📑 Table of Contents

- [✨ Key Features](#-key-features)
- [❓ Why Choose This?](#-why-choose-this)
- [📥 Installation](#-installation-drag--drop)
- [🚀 Quick Start](#-quick-start)
- [🏗️ Schema Builder API](#️-schema-builder-api)
- [🌱 Database Seeding](#-database-seeding)
- [🛠️ Complete CLI Commands](#️-complete-cli-commands)
- [🛡️ Security Architecture](#️-security-architecture)
- [📂 Folder Structure](#-folder-structure)

---

## ✨ Key Features

*   **🏗️ Fluent Schema Builder**: Write database tables using beautiful, chainable PHP syntax (e.g., `$table->string('email')->unique();`). No more raw SQL syntax errors!
*   **🤖 Auto-Discovery Seeders**: Create your fake data effortlessly. Seeders are automatically discovered from the `seeders` directory and executed sequentially.
*   **🎨 Beautiful CLI Interface**: A colorized, interactive, and typo-tolerant command-line interface powered by custom ASCII rendering.
*   **🗄️ Multi-Driver Support**: Fully supports **MySQL / MariaDB** and **SQLite** under the hood with automatic grammar compilation.
*   **🔄 Rollback & Reset**: Make a mistake? Simply rollback your database state by batches or specific steps.
*   **🛡️ Military-Grade Security**: Built-in protections against SQL Injection (PDO Prepared Statements), Path Traversal (PathGuard), and Command/RCE Injection (Sanitizer).
*   **📦 Zero Dependencies**: Built entirely using native PHP. Drop it anywhere, and it just works.

---

## ❓ Why Choose This?

In modern web development, frameworks like Laravel or Symfony provide excellent ORMs and Migration systems. But what if you are:
1. Working on a legacy PHP application?
2. Building a lightweight API using pure PHP without the overhead of a massive framework?
3. Learning how database schema compilers work under the hood?

Importing Doctrine or Eloquent via Composer into a raw PHP project often brings dozens of dependencies. **PHP Native Migrator gives you the exact Laravel-like CLI and Schema Builder experience with literally 0 bytes of vendor overhead.**

---

## 📥 Installation (Drag & Drop)

1. **Clone or Download** this repository.
2. **Copy** the files and folders directly into your raw PHP project root.
3. **Rename** `.env.example` to `.env`.
4. **Configure** your database credentials in the `.env` file.

That's it! Open your terminal and type `php artisan list` to see the magic.

---

## 🚀 Quick Start

### 1. Setup Your `.env`
```env
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_awesome_app
DB_USERNAME=root
DB_PASSWORD=secret
```

### 2. Generate a Migration
```bash
php artisan make:migration create_users_table
```
This generates a smart timestamped file in `migrations/` (e.g. `m20260505_120000_create_users_table.php`).

### 3. Write Your Schema
Open the generated file. The system automatically detects you want to *create* a table named `users` and prepares the boilerplate!
```php
public function up(PDO $pdo): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();                                    // BIGINT PRIMARY KEY AUTO_INCREMENT
        $table->string('username', 50)->unique();        // VARCHAR(50) UNIQUE
        $table->string('email')->unique();               // VARCHAR(255) UNIQUE
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
Your table is now safely created. You can verify the status by running `php artisan migrate:status`.

---

## 🏗️ Schema Builder API

<details open>
<summary><b>🔥 Column Types</b></summary>

| Method | Database Type | Description |
|--------|--------------|-------------|
| `$table->id()` | `BIGINT` | Auto-incrementing primary key |
| `$table->string('name', 100)` | `VARCHAR(100)` | String with optional length (default 255) |
| `$table->text('desc')` | `TEXT` | Standard text field |
| `$table->longText('body')` | `LONGTEXT` | Massive text field |
| `$table->integer('qty')` | `INT` | Standard integer |
| `$table->bigInteger('views')`| `BIGINT` | Large integer |
| `$table->decimal('price', 8, 2)`| `DECIMAL(8,2)` | Decimal numbers (e.g. currency) |
| `$table->boolean('active')` | `TINYINT(1)` | Boolean value |
| `$table->date('birthday')` | `DATE` | Date without time |
| `$table->dateTime('login')` | `DATETIME` | Date and time |
| `$table->json('data')` | `JSON` | JSON field |
| `$table->enum('role', ['A','B'])`| `ENUM` | Enumerated list of values |
| `$table->uuid('uuid')` | `CHAR(36)` | UUID string |
</details>

<details open>
<summary><b>✨ Column Modifiers (Chainable)</b></summary>

Modifiers allow you to change how a column behaves.
```php
$table->string('phone')->nullable();           // Allow NULL values
$table->integer('age')->unsigned();            // Unsigned integers
$table->string('status')->default('pending');  // Set default value
$table->string('email')->unique();             // Add UNIQUE constraint
$table->string('bio')->after('email');         // Order placement (MySQL only)
$table->string('id')->first();                 // Place as first column
$table->string('title')->comment('App Title'); // Add column comment
```
</details>

<details open>
<summary><b>🔗 Foreign Keys & Relationships</b></summary>

Easily bind tables together with cascading deletes and updates.
```php
$table->foreignId('department_id'); // Automatically creates BIGINT UNSIGNED

$table->foreign('department_id')
      ->references('id')
      ->on('departments')
      ->cascadeOnDelete()           // ON DELETE CASCADE
      ->nullOnUpdate();             // ON UPDATE SET NULL
```
</details>

---

## 🌱 Database Seeding

Seeders let you populate your tables with test/dummy data. 

### 1. Generate Seeder
```bash
php artisan make:seeder ProductSeeder
```

### 2. Write Data using the Safe DB Helper
```php
class ProductSeeder
{
    public function run(PDO $pdo): void
    {
        // Insert a single record safely
        Database::insert('products', [
            'name'  => 'Premium Coffee',
            'price' => 15.00,
            'stock' => 100
        ]);

        // Insert multiple records at once (Batch Insert)
        Database::insertMany('products', ['name', 'price', 'stock'], [
            ['Green Tea', 10.00, 50],
            ['Matcha Latte', 18.50, 30]
        ]);
        
        // Wrap in a transaction for safety!
        Database::transaction(function ($pdo) {
             Database::insert('categories', ['name' => 'Beverages']);
        });
    }
}
```

### 3. Execute
Simply run:
```bash
php artisan db:seed
```
*Note: The `DatabaseSeeder.php` acts as the entry point and will auto-discover and run all other seeders alphabetically!*

---

## 🛠️ Complete CLI Commands

| Command | Action |
|---------|--------|
| `php artisan migrate` | Executes all pending migrations |
| `php artisan migrate:status` | Shows an ASCII table of migration statuses |
| `php artisan migrate:rollback` | Reverts the last "batch" of migrations |
| `php artisan migrate:rollback --step=N`| Reverts exactly `N` migrations |
| `php artisan migrate:reset` | Reverts ALL migrations completely |
| `php artisan migrate:refresh` | Resets and re-runs all migrations |
| `php artisan migrate:fresh` | **Drops all tables** and re-runs migrations |
| `php artisan migrate:fresh --seed` | Drops all tables, migrates, and seeds data |
| `php artisan make:migration Name` | Generates a new migration file |
| `php artisan make:seeder Name` | Generates a new seeder file |
| `php artisan db:seed` | Runs all auto-discovered seeders |
| `php artisan db:wipe` | Drops all tables without running migrations |

---

## 🛡️ Security Architecture

Building a dynamic CLI tool in PHP requires strict security to prevent server exploitation. This tool incorporates multiple defense layers:

1. **SQL Injection Protection**: You will notice the `Database::insert` and `Database::insertMany` helpers. Under the hood, these map directly to **PDO Prepared Statements**. User inputs never touch raw SQL syntax.
2. **Local File Inclusion (LFI) & Path Traversal Guard**: 
   - The `PathGuard` class intercepts every file inclusion (`require_once`).
   - It strips Null Bytes (`\0`).
   - It strictly validates that files are physically located inside the `migrations/` or `seeders/` directory using `realpath()` validation, preventing attacks like `../../../etc/passwd`.
3. **Remote Code Execution (RCE) Sanitizer**: 
   - When generating migrations, the terminal input is dynamically evaluated to create file names and classes.
   - The `Sanitizer` class runs strict Regex patterns, blocking attempts to inject `eval()`, backticks, or PHP tags through the CLI arguments.
4. **Environment Isolation**: Ensures system-level environment variables (like your OS `PATH`) cannot be overwritten by the `.env` parser.

---

## 📂 Folder Structure

What do the core folders do?

```text
├── core/
│   ├── Bootstrap.php          # Custom Class Autoloader
│   ├── Database.php           # PDO Singleton & Query Builders
│   ├── DotEnv.php             # Secure .env Parser
│   ├── Migrator.php           # Core Migration logic & Batching
│   ├── Seeder.php             # Core Seeding & Auto-Discovery
│   ├── Console/               # CLI Router & Colored Output formatter
│   ├── Schema/                # The Schema Builder & Grammars
│   └── Security/              # PathGuard and Input Sanitizers
```
*(You generally never need to touch the `core/` folder. It just powers the magic behind the scenes!)*

---

## 🤝 Contributing & License

Feel free to submit PRs, open issues, or fork this repository!
This project is open-sourced software licensed under the **[MIT license](https://opensource.org/licenses/MIT)**.

<div align="center">
  <b>Built with ❤️ using 100% Native PHP • No Frameworks Allowed</b>
</div>
