
# XAMPP Local Development Setup

## Prerequisites
- XAMPP with PHP 8.0+ and MySQL
- Composer installed globally

## Installation Steps

### 1. Prepare XAMPP
- Start XAMPP Control Panel
- Start **Apache** and **MySQL** services
- Ensure ports 80 (Apache) and 3306 (MySQL) are available

### 2. Copy Project Files
```bash
# Copy the entire project to XAMPP htdocs
cp -r /path/to/shelter_management C:\xampp\htdocs\shelter_management
# Or manually copy the folder to: C:\xampp\htdocs\shelter_management\
```

### 3. Install Dependencies
```bash
cd C:\xampp\htdocs\shelter_management
composer install
```

### 4. Configure Database
- Open phpMyAdmin: http://localhost/phpmyadmin/
- Create database: `refugios_db`
- Import: `storage/schema.sql`

### 5. Verify Configuration
- Ensure `.env` has `DB_HOST=localhost`
- Run setup check: `php setup_local.php`

### 6. Test Application
- Main app: http://localhost/shelter_management/
- Admin panel: http://localhost/shelter_management/panel
- API test: http://localhost/shelter_management/public/statistics

## Troubleshooting

### Common Issues
1. **Port 80 in use**: Change Apache port in `httpd.conf`
2. **MySQL not starting**: Check port 3306 availability
3. **Composer not found**: Install Composer globally
4. **Headers already sent**: Clear any BOM characters from PHP files

### File Permissions
Ensure XAMPP has read/write access to:
- `/assets/` (for static files)
- `/storage/` (for logs)
- `.env` (for configuration)

## Development URLs
- **App**: http://localhost/shelter_management/
- **phpMyAdmin**: http://localhost/phpmyadmin/
- **XAMPP Control**: http://localhost/xampp/

## Security Notes
- Default MySQL user: `root` (no password)
- Change JWT_SECRET in `.env` for production
- Enable HTTPS for production deployment
