# GED Backend - Laravel Application

A Laravel 9 backend application with WebSocket support, JWT authentication, and Redis caching.

## 📋 Prerequisites

Before you begin, ensure you have the following installed on your system:

- **PHP** >= 8.0.2
- **Composer** (PHP dependency manager)
- **MySQL** >= 5.7 or **MariaDB** >= 10.3
- **Redis** (for caching and queue management)
- **Node.js** and **npm** (for asset compilation)
- **Git** (for version control)

## 🚀 Getting Started

Follow these steps to get the backend application running locally:

### 1. Clone the Repository

```bash
git clone <repository-url>
cd backend
```

### 2. Install PHP Dependencies

```bash
composer install
```

This will install all the required PHP packages defined in `composer.json`, including:

- Laravel Framework 9.x
- JWT Authentication
- Laravel WebSockets
- Pusher PHP Server
- Redis (Predis)
- And other dependencies

### 3. Environment Configuration

#### Copy the environment file:

```bash
cp .env.example .env
```

#### Configure the `.env` file with your local settings:

**Application Settings:**

```env
APP_NAME=GED
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

**Database Configuration:**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```

**Redis Configuration:**

```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**WebSocket/Pusher Configuration:**

```env
BROADCAST_DRIVER=pusher
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
```

**Mail Configuration (Optional):**

```env
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

This generates a unique application key required for encryption.

### 5. Generate JWT Secret

```bash
php artisan jwt:secret
```

This generates a secret key for JWT token authentication.

### 6. Create Database

Create a MySQL database that matches your `DB_DATABASE` value in `.env`:

```sql
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Run Database Migrations

```bash
php artisan migrate
```

This will create all the necessary database tables.

### 8. (Optional) Seed the Database

If seeders are available, you can populate the database with sample data:

```bash
php artisan db:seed
```

### 9. Create Storage Symlink

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public`.

### 10. Set Directory Permissions

Ensure the following directories are writable:

```bash
chmod -R 775 storage bootstrap/cache
```

On Windows, ensure your web server has write permissions to these directories.

## 🏃 Running the Application

### Start the Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

### Start the WebSocket Server

In a separate terminal, run:

```bash
php artisan websockets:serve
```

This starts the Laravel WebSocket server on port 6001 (configurable in `.env`).

### Start the Queue Worker (Optional)

If your application uses queues, start the queue worker:

```bash
php artisan queue:work
```

Or use Redis for queue processing:

```bash
php artisan queue:work redis
```

## 🔧 Additional Commands

### Clear Application Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Run Tests

```bash
php artisan test
```

Or using PHPUnit directly:

```bash
./vendor/bin/phpunit
```

### Code Formatting

```bash
./vendor/bin/pint
```

## 📦 Key Features

- **JWT Authentication** - Secure token-based authentication
- **WebSocket Support** - Real-time communication via Laravel WebSockets
- **Redis Caching** - Fast caching and session management
- **Queue Management** - Background job processing
- **CORS Support** - Cross-origin resource sharing configured
- **API Routes** - RESTful API endpoints

## 🗂️ Project Structure

```
backend/
├── app/                # Application core code
│   ├── Http/          # Controllers, Middleware, Requests
│   ├── Models/        # Eloquent models
│   └── ...
├── config/            # Configuration files
├── database/          # Migrations, seeders, factories
├── public/            # Public assets and index.php
├── resources/         # Views, language files
├── routes/            # Route definitions
│   ├── api.php       # API routes
│   └── web.php       # Web routes
├── storage/           # Logs, cache, uploads
├── tests/             # Test files
└── vendor/            # Composer dependencies
```

## 🐛 Troubleshooting

### Common Issues

**Issue: "Class not found" errors**

```bash
composer dump-autoload
```

**Issue: Permission denied errors**

```bash
# On Linux/Mac
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache

# On Windows, ensure IIS/Apache has write permissions
```

**Issue: Database connection errors**

- Verify MySQL is running
- Check database credentials in `.env`
- Ensure the database exists

**Issue: Redis connection errors**

- Verify Redis is running: `redis-cli ping` (should return "PONG")
- Check Redis configuration in `.env`

**Issue: WebSocket connection errors**

- Ensure the WebSocket server is running: `php artisan websockets:serve`
- Check firewall settings for port 6001
- Verify `PUSHER_*` settings in `.env`

## 📚 Documentation

- [Laravel Documentation](https://laravel.com/docs/9.x)
- [JWT Auth Documentation](https://jwt-auth.readthedocs.io/)
- [Laravel WebSockets](https://beyondco.de/docs/laravel-websockets/)

## 🔐 Security

- Never commit `.env` file to version control
- Keep your `APP_KEY` and `JWT_SECRET` secure
- Use strong database passwords
- Enable HTTPS in production
- Keep dependencies updated: `composer update`

## 📝 License

This project is licensed under the MIT License.
