# Trotro System Installation Guide

## Prerequisites
- Windows 10/11
- Internet connection
- Administrator privileges (for some installations)

## 1. Install PHP

### Step 1: Download PHP
1. Visit https://windows.php.net/download/
2. Download the latest PHP 8.x version (VS17 x64 Non Thread Safe for CLI usage)
3. Extract the zip file to `C:\PHP`

### Step 2: Configure PHP
1. Copy `php.ini-development` to `php.ini`
2. Edit `php.ini` and uncomment these lines:
   ```ini
   extension_dir = "ext"
   extension=curl
   extension=fileinfo
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   extension=sqlite3
   ```

### Step 3: Add PHP to PATH
1. Search for "Environment Variables" in Windows
2. Click "Edit the system environment variables"
3. Click "Environment Variables"
4. Under "System variables", find "Path" and click "Edit"
5. Add `C:\PHP` to the list
6. Click OK on all dialogs
7. Restart your terminal

### Step 4: Verify Installation
```powershell
php --version
```

## 2. Install Composer

### Step 1: Download Composer
1. Visit https://getcomposer.org/download/
2. Download Composer-Setup.exe
3. Run the installer (it will automatically detect PHP)
4. Follow the installation wizard

### Step 2: Verify Installation
```powershell
composer --version
```

## 3. Install Laravel

### Step 1: Install Laravel via Composer
```powershell
composer global require laravel/installer
```

### Step 2: Add Laravel to PATH
1. Add `%APPDATA%\Composer\vendor\bin` to your system PATH
2. Restart your terminal

### Step 3: Verify Installation
```powershell
laravel --version
```

## 4. Install Flutter

### Step 1: Download Flutter SDK
1. Visit https://docs.flutter.dev/get-started/install/windows
2. Download the Flutter SDK zip file
3. Extract to `C:\flutter`

### Step 2: Add Flutter to PATH
1. Add `C:\flutter\bin` to your system PATH
2. Restart your terminal

### Step 3: Verify Installation
```powershell
flutter --version
flutter doctor
```

### Step 4: Install Additional Dependencies
Run `flutter doctor` and install any missing dependencies (Android Studio, VS Code, etc.)

## 5. Install Database (Optional but Recommended)

### Option A: MySQL
1. Download MySQL from https://dev.mysql.com/downloads/mysql/
2. Install MySQL Server
3. Create a database for the project

### Option B: SQLite (Simpler)
Laravel can use SQLite by default - no additional installation needed

## 6. Create the Project

Once all tools are installed, run these commands in the `D:\trotro` directory:

### Backend (Laravel)
```powershell
cd D:\trotro
laravel new backend
cd backend
composer require laravel/sanctum
composer require laravel/cors
php artisan migrate
```

### Frontend (Flutter)
```powershell
cd D:\trotro
flutter create trotro_app
cd trotro_app
flutter pub get
```

## Next Steps
After installation, follow the project setup instructions in SETUP.md
