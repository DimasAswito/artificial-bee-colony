# Artificial Bee Colony Scheduler

This project is a web-based application designed for managing academic resources and scheduling, featuring a modern dashboard interface built with **Laravel 12** and **Tailwind CSS**.

## 🌐 Live Demo

Access the latest version of the application here:
[https://artificial-bee-colony-aswito.vercel.app](https://artificial-bee-colony-aswito.vercel.app)

## ✨ Key Features

The application includes comprehensive Master Data management modules:

-   **👨‍🏫 Data Dosen** - Manage lecturer information with validatiion and history logs.
-   **📚 Data Mata Kuliah** - Manage course data including SKS and assigned lecturers.
-   **🏫 Data Ruangan** - detailed room management with status tracking.
-   **📅 Data Hari** - Configure active and inactive days for scheduling.
-   **⏰ Data Jam** - Manage time slots for course scheduling with 24h format support.

## 🛠 Tech Stack

-   **Framework:** Laravel 12
-   **Frontend:** Blade Templates + Tailwind CSS v4
-   **Interactivity:** Alpine.js
-   **Database:** Supabase

## 🚀 Installation & Setup

Follow these steps to set up the project locally:

### 1. Clone the Repository

```bash
git clone https://github.com/DimasAswito/artificial-bee-colony.git
cd artificial-bee-colony
```

### 2. Install Dependencies

Install PHP and Node.js dependencies:

```bash
composer install
npm install
```

### 3. Environment Configuration

Copy the example environment file and configure your database settings:

```bash
cp .env.example .env
```

Update `.env` with your database credentials:

```env
DB_CONNECTION=pgsql
DB_URL=postgresql://.....@aws-0-ap-southeast-1.pooler.supabase.com:6543/postgres
DB_PASSWORD=...

VITE_SUPABASE_URL="..."
VITE_SUPABASE_ANON_KEY="..."
SUPABASE_SERVICE_KEY="..."

```

### 4. Generate App Key & Migrate

```bash
php artisan key:generate
php artisan migrate
```

### 5. Run the Application

Start the development server:

```bash
composer run dev
```

The application will be available at `http://localhost:8000`.

## 📄 License

This project is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
