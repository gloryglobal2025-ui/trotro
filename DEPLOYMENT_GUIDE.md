# Trotro Production Deployment Guide

This guide explains how to deploy the Trotro system to trotro.digitalsecuritymarket.com on Hostinger/cPanel with MySQL.

## Overview

- **Domain**: trotro.digitalsecuritymarket.com
- **Stack**: Laravel 13 (PHP 8.3+) + Flutter Web
- **Architecture**: Single domain serving both web app and API
  - `/` → Redirects to `/web`
  - `/web` → Flutter web app
  - `/api/*` → Laravel API
  - `/admin` → Laravel admin panel
  - `/media/*` → User/vehicle images

## Prerequisites

- Hostinger/cPanel account with SSH access (optional but recommended)
- MySQL database (will create in step 1)
- PHP 8.3+ on the server
- Composer on the server (or use locally before upload)

## Step 1: Set Up MySQL Database in cPanel

1. Log in to cPanel
2. Go to **MySQL® Database Wizard**
3. **Step 1: Create Database**
   - Database name: `trotro`
   - Click "Next Step"
4. **Step 2: Create Database User**
   - Username: `trotro`
   - Password: Generate a strong password (save it!)
   - Click "Create User"
5. **Step 3: Add User to Database**
   - Select user `trotro` and database `trotro`
   - Check **ALL PRIVILEGES**
   - Click "Make Changes"
6. **Step 4: Complete** (no action needed)

**Save these credentials:**
- Database: `trotro`
- Username: `trotro`
- Password: `YOUR_GENERATED_PASSWORD`
- Host: `localhost`

## Step 2: Upload Files to cPanel

### Option A: Using cPanel File Manager

1. Log in to cPanel
2. Open **File Manager**
3. Navigate to `/home/yourusername/public_html/trotro.digitalsecuritymarket.com/`
   - If the folder doesn't exist, create it
4. Upload the prepared files:
   - Upload `release/backend-trotro/` contents to `public_html/trotro.digitalsecuritymarket.com/`
   - Upload `release/web-app/` contents to `public_html/trotro.digitalsecuritymarket.com/public/web/`

### Option B: Using FTP/SFTP

```bash
# Using FileZilla or similar
# Upload backend-trotro/* to /trotro.digitalsecuritymarket.com/
# Upload web-app/* to /trotro.digitalsecuritymarket.com/public/web/
```

### Important File Structure After Upload

```
/home/yourusername/public_html/trotro.digitalsecuritymarket.com/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── index.php
│   ├── web/          ← Flutter web app
│   │   ├── index.html
│   │   ├── main.dart.js
│   │   └── assets/
│   ├── storage/      ← Symlink created later
│   └── .htaccess
├── routes/
├── storage/
├── vendor/           ← Install on server
├── .env.example
├── artisan
└── composer.json
```

## Step 3: Configure Environment Variables

1. In cPanel File Manager, navigate to `trotro.digitalsecuritymarket.com/`
2. Copy `.env.example` to `.env`
3. Edit `.env` and set the following values:

```bash
APP_NAME=Trotro
APP_ENV=production
APP_DEBUG=false
APP_URL=https://trotro.digitalsecuritymarket.com

# Database credentials from Step 1
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trotro
DB_USERNAME=trotro
DB_PASSWORD=YOUR_GENERATED_PASSWORD_FROM_STEP_1

# Session/Security
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_PATH=/
SESSION_DOMAIN=null

# CORS - allow both web app and direct API access
CORS_ALLOWED_ORIGINS=https://trotro.digitalsecuritymarket.com
SANCTUM_STATEFUL_DOMAINS=trotro.digitalsecuritymarket.com
SANCTUM_EXPIRATION=10080
FORCE_HTTPS=true

# Admin credentials (CHANGE THESE!)
ADMIN_NAME=Administrator
ADMIN_EMAIL=admin@digitalsecuritymarket.com
ADMIN_PASSWORD=CHANGE_THIS_ADMIN_PASSWORD
ADMIN_PHONE=+233000000000

# SMS gateway (set to "log" initially)
SMS_DRIVER=log
SMS_WEBHOOK_SECRET=CHANGE_THIS_SECRET_AFTER_DEPLOYMENT
```

## Step 4: Install Dependencies

### Via SSH (Recommended)

```bash
cd public_html/trotro.digitalsecuritymarket.com
composer install --no-dev --optimize-autoloader
```

### Via cPanel Terminal

1. In cPanel, go to **Terminal**
2. Navigate to the directory
3. Run: `composer install --no-dev --optimize-autoloader`

## Step 5: Generate Application Key

```bash
php artisan key:generate
```

## Step 6: Import Database

### Option A: Via cPanel phpMyAdmin

1. In cPanel, go to **phpMyAdmin**
2. Select the `trotro` database
3. Click **Import**
4. Choose the SQL file: `trotro-database.sql` (in the backend folder)
5. Click **Go**

### Option B: Via SSH

```bash
mysql -u trotro -p trotro < trotro-database.sql
```

## Step 7: Run Migrations

```bash
php artisan migrate --force
```

## Step 8: Set Permissions

```bash
# Set storage and cache directories writable
chmod -R 775 storage bootstrap/cache
chown -R yourusername:yourusername storage bootstrap/cache
```

Or via cPanel File Manager:
- Right-click `storage/` → Change Permissions → 755
- Right-click `bootstrap/cache/` → Change Permissions → 755

## Step 9: Create Storage Symlink

```bash
php artisan storage:link
```

This creates a symlink from `public/storage` to `storage/app/public` for serving user/vehicle images.

## Step 10: Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 11: Configure Web Server (Apache)

The deployment includes a `.htaccess` file for Apache. However, since we're serving the Flutter web app from `/` and the API from `/api`, we need to configure routing.

### Option A: Laravel Handles All Routes (Recommended)

Edit `public/.htaccess` (already present, verify it's correct):

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Serve Flutter web app from / (static files)
    RewriteCond %{REQUEST_URI} ^/web/ [OR]
    RewriteCond %{REQUEST_URI} ^/assets/ [OR]
    RewriteCond %{REQUEST_URI} ^/icons/ [OR]
    RewriteCond %{REQUEST_URI} ^/canvaskit/ [OR]
    RewriteCond %{REQUEST_URI} ^/main.dart.js [OR]
    RewriteCond %{REQUEST_URI} ^/flutter.js [OR]
    RewriteCond %{REQUEST_URI} ^/index.html [OR]
    RewriteCond %{REQUEST_URI} ^/favicon.png [OR]
    RewriteCond %{REQUEST_URI} ^/manifest.json
    RewriteRule ^(.*)$ public/web/$1 [L]

    # Send All Other Requests To Laravel
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### Option B: Serve Web App via subdirectory, API via Laravel

Move the Flutter app to a different approach if the above doesn't work well.

## Step 12: Add Route for Web App

Add this route to `routes/web.php` to serve the Flutter web app at the root:

```php
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// NEW: Serve Flutter web app at /web
Route::get('/web', function () {
    return response()->file(public_path('web/index.html'));
})->where('path', '.*');
```

**Or better approach** - serve web app directly from Apache by creating a route fallback.

## Step 13: Verify Laravel Routes

The routes are already configured in `routes/web.php`:

```php
// Serve Flutter web app at /web
Route::get('/web', function () {
    return response()->file(public_path('web/index.html'));
});

// Serve static assets for Flutter web app from /web/*
Route::get('/web/{path}', function ($path) {
    $file = public_path('web/' . $path);
    if (file_exists($file) && !str_starts_with($path, '.')) {
        return response()->file($file);
    }
    abort(404);
})->where('path', '.*');

// Root redirects to /web
Route::get('/', function () {
    return redirect('/web');
});
```

**Note**: This approach serves static files through Laravel, which is fine for low to medium traffic. For high traffic, consider using nginx/Apache to serve static files directly.

## Step 14: Test the Deployment

1. Visit `https://trotro.digitalsecuritymarket.com/`
   - Should see the Flutter web app
2. Visit `https://trotro.digitalsecuritymarket.com/api/bus-stops`
   - Should return JSON with bus stops
3. Visit `https://trotro.digitalsecuritymarket.com/admin`
   - Should redirect to login page
4. Test registration on the web app:
   - Open the web app
   - Try to register a new user
   - Should connect to the API successfully

## Step 15: Configure SSL (HTTPS)

Hostinger usually handles SSL automatically. If HTTPS is not enforced:

1. In cPanel, go to **SSL/TLS Status**
2. Enable **Force HTTPS Redirect** for trotro.digitalsecuritymarket.com

Or add to `.htaccess`:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## Troubleshooting

### 500 Internal Server Error

- Check `storage/logs/laravel.log` for errors
- Verify permissions on `storage/` and `bootstrap/cache/`
- Ensure `.env` exists and has correct permissions

### Database Connection Failed

- Verify database credentials in `.env`
- Check that MySQL user has privileges on the database
- Ensure database name matches cPanel (often includes prefix: `username_trotro`)

### Web App Not Loading

- Verify `public/web/` directory exists with `index.html`
- Check `.htaccess` routing rules
- Clear browser cache

### API Returns 404

- Ensure Laravel is pointing to `public/` as document root
- Check that `api.php` routes are registered: `php artisan route:list`
- Verify `.htaccess` is forwarding requests to `index.php`

### Images Not Loading (/media/*)

- Ensure storage symlink exists: `php artisan storage:link`
- Check that `public/storage` exists and is a symlink
- Verify `storage/app/public/` has the uploaded files

## File Locations for Updates

- **Backend code**: Upload modified files to `app/`, `routes/`, `config/`
- **Database changes**: Run `php artisan migrate` after uploading new migrations
- **Web app updates**: Rebuild Flutter locally, upload to `public/web/`
- **Admin panel views**: Upload to `resources/views/`, then run `php artisan view:cache`

## Security Checklist

- [ ] Change `APP_KEY` to a unique value
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong database password
- [ ] Change default admin password
- [ ] Set `SESSION_SECURE_COOKIE=true`
- [ ] Configure `FORCE_HTTPS=true`
- [ ] Set up SSL certificate
- [ ] Change `SMS_WEBHOOK_SECRET`
- [ ] Restrict directory permissions (755 for folders, 644 for files)
- [ ] Remove or protect `.env` from web access

## Additional Configuration

### Cron Jobs (if needed)

In cPanel, go to **Cron Jobs** and add:

```bash
* * * * * cd /home/yourusername/public_html/trotro.digitalsecuritymarket.com && php artisan schedule:run >> /dev/null 2>&1
```

### Queue Workers (if using background jobs)

```bash
php artisan queue:work --daemon --tries=3 --timeout=90
```

Add this to Supervisor or cPanel's background process manager.

## Support

For issues specific to this deployment:
- Check Laravel logs: `storage/logs/laravel.log`
- Check server error logs in cPanel
- Verify environment: `php artisan env`
- Check routes: `php artisan route:list`
