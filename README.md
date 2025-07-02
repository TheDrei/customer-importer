# Customer Importer App (Laravel 12 + Doctrine ORM)

A simple Laravel 12 application that uses **Doctrine ORM** to manage `Customer` entities, demonstrating service-oriented architecture, clean code principles, RESTful APIs, and a modern UI with Bootstrap 5 and DataTables.

## ✨ Features

- ✅ Laravel 12 + Doctrine ORM
- ✅ `CustomerImporter` service with pluggable data provider
- ✅ Imports customers from [RandomUser API](https://randomuser.me/)
- ✅ Configurable API URL in `config/customer_importer.php`
- ✅ UI built with Blade, Bootstrap 5, and DataTables
- ✅ RESTful API (`/customers`, `/customers/{email}`)
- ✅ Fully tested with fake API responses and isolated DB
- ✅ Clear separation of concerns using SRP and dependency injection

## ⚙️ Setup Instructions

```bash
git clone <your-repo-url>
cd customer-importer

composer install
cp .env.example .env
php artisan key:generate

# Set your DB connection in .env, then run:
php artisan migrate

# 🔄 Import initial customer data
php artisan import:customers
