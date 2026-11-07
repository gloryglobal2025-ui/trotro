# Quick Deployment Steps - trotro.digitalsecuritymarket.com

## Immediate Fix for Your App

Your Flutter app was built with the wrong API endpoint (`test2.digitalsecuritymarket.com`). I've rebuilt it with the correct endpoint (`trotro.digitalsecuritymarket.com`).

### Files Ready for Upload

The following files are ready in `D:\XAMPP\htdocs\trotro\release\`:

1. **backend-trotro/** - Laravel backend (already configured for trotro subdomain)
2. **web-app/** - Flutter web app (built with correct API endpoint)
3. **trotro-database.sql** - Database dump from your local MySQL

## Step-by-Step Deployment

### 1. Create MySQL Database in cPanel

1. Log in to cPanel
2. Go to **MySQL® Database Wizard**
3. Create database: `trotro` (cPanel may prefix it: `username_trotro`)
4. Create user: `trotro` with a strong password
5. Grant ALL PRIVILEGES to user on database
6. **Save the credentials** - you'll need them in step 3

### 2. Upload Files via cPanel File Manager

1. In cPanel, open **File Manager**
2. Navigate to `public_html/`
3. Create folder: `trotro.digitalsecuritymarket.com`
4. Upload the contents of `backend-trotro/` to this folder
5. Inside `trotro.digitalsecuritymarket.com/`, create folder: `public/web`
6. Upload the contents of `web-app/` to `public/web/`

**Final structure should look like:**
```
public_html/trotro.digitalsecuritymarket.com/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── index.php
│   ├── web/          ← Flutter web app files here
│   │   ├── index.html
│   │   ├── main.dart.js
│   │   └── assets/
│   └── .htaccess
├── routes/
├── storage/
├── artisan
├── composer.json
└── .env.example
```

### 3. Configure .env

1. In File Manager, copy `.env.example` to `.env`
2. Edit `.env` and set these values:

```bash
APP_URL=https://trotro.digitalsecuritymarket.com

# Database (use credentials from step 1)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trotro                    # or username_trotro
DB_USERNAME=trotro
DB_PASSWORD=YOUR_PASSWORD_HERE

# Security
SESSION_SECURE_COOKIE=true
CORS_ALLOWED_ORIGINS=https://trotro.digitalsecuritymarket.com
SANCTUM_STATEFUL_DOMAINS=trotro.digitalsecuritymarket.com
FORCE_HTTPS=true

# Admin (CHANGE THESE!)
ADMIN_PASSWORD=CHANGE_THIS_PASSWORD
SMS_WEBHOOK_SECRET=CHANGE_THIS_SECRET
```

### 4. Install Dependencies (via cPanel Terminal)

1. In cPanel, open **Terminal**
2. Navigate to the folder:
   ```bash
   cd public_html/trotro.digitalsecuritymarket.com
   ```
3. Install composer dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

### 5. Generate App Key

```bash
php artisan key:generate
```

### 6. Import Database

**Option A: Via phpMyAdmin**
1. In cPanel, go to **phpMyAdmin**
2. Select the `trotro` database
3. Click **Import**
4. Upload `trotro-database.sql` (it's in the backend folder)
5. Click **Go**

**Option B: Via Terminal**
```bash
mysql -u trotro -p trotro < trotro-database.sql
```

### 7. Run Migrations

```bash
php artisan migrate --force
```

### 8. Set Permissions

In Terminal:
```bash
chmod -R 775 storage bootstrap/cache
```

Or in File Manager:
- Right-click `storage/` → Change Permissions → 755
- Right-click `bootstrap/cache/` → Change Permissions → 755

### 9. Create Storage Symlink

```bash
php artisan storage:link
```

### 10. Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 11. Test the Deployment

1. Visit `https://trotro.digitalsecuritymarket.com/`
   - Should redirect to `/web` and show the Flutter app
2. Try to register in the app
   - Should connect to the API successfully (no more DNS error)
3. Visit `https://trotro.digitalsecuritymarket.com/api/bus-stops`
   - Should return JSON data
4. Visit `https://trotro.digitalsecuritymarket.com/admin`
   - Should show admin login page

## Troubleshooting

### "Site can't be reached" or DNS errors

- Wait 24-48 hours for DNS propagation if the subdomain is new
- Check that the subdomain `trotro.digitalsecuritymarket.com` is configured in cPanel
- Verify the document root points to `public_html/trotro.digitalsecuritymarket.com`

### 500 Internal Server Error

- Check `storage/logs/laravel.log` for error details
- Verify `.env` exists and has correct permissions
- Ensure `storage/` and `bootstrap/cache/` are writable

### Database connection failed

- Verify database credentials in `.env`
- Check that the database name includes cPanel prefix (e.g., `username_trotro`)
- Ensure the user has ALL PRIVILEGES on the database

### Web app not loading

- Verify `public/web/index.html` exists
- Check that routes are serving it: `php artisan route:list`
- Clear browser cache

### Images not loading (/media/*)

- Run `php artisan storage:link` to create the symlink
- Check that `public/storage` exists

## What Was Fixed

1. **Flutter app rebuilt** with correct API endpoint (`trotro.digitalsecuritymarket.com/api` instead of `test2.digitalsecuritymarket.com/api`)
2. **Laravel routes updated** to serve the web app at `/web`
3. **Environment file configured** for production use
4. **Database dump included** from your local development data

## Security Checklist

- [ ] Change `APP_KEY` (run `php artisan key:generate` again with a unique value)
- [ ] Set strong database password
- [ ] Change default admin password in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Enable SSL/HTTPS
- [ ] Change `SMS_WEBHOOK_SECRET`

## Next Steps After Deployment

1. Test all features (registration, login, booking, payments)
2. Configure real SMS gateway (currently set to `log` mode)
3. Set up cron jobs for scheduled tasks if needed
4. Configure email settings in `.env` for password resets
5. Set up queue workers if using background jobs

For detailed information, see `DEPLOYMENT_GUIDE.md`.
