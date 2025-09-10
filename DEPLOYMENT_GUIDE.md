# Torres Hotel Management System - Deployment Guide

## HTTP 500 Error Troubleshooting

If you're seeing an HTTP 500 error after uploading the system, follow these steps:

### Step 1: Database Configuration

The most common cause of HTTP 500 errors is incorrect database configuration.

1. **Get your hosting database credentials:**
   - Database Host (usually `localhost` or provided by host)
   - Database Username
   - Database Password
   - Database Name

2. **Update configuration file:**
   - Rename `includes/config_production.php` to `includes/config.php`
   - OR edit `includes/config.php` directly
   - Replace the following values:
   ```php
   define('DB_HOST', 'your_actual_host');
   define('DB_USER', 'your_actual_username');
   define('DB_PASS', 'your_actual_password');
   define('DB_NAME', 'your_actual_database_name');
   ```

### Step 2: Create Database Tables

1. **Access your hosting control panel** (cPanel, Plesk, etc.)
2. **Open phpMyAdmin** or database management tool
3. **Import the database schema:**
   - Upload and run `db/schema.sql`
   - This creates all necessary tables

### Step 3: Set File Permissions

Ensure proper file permissions on your hosting server:
- Folders: 755
- PHP files: 644
- Make sure the `logs/` directory is writable (755 or 777)

### Step 4: Test Database Connection

1. Visit: `your-domain.com/db/test_connection.php`
2. This will show if the database connection is working
3. If successful, you should see "Database connection successful!"

### Step 5: Check Error Logs

1. Look for `logs/error.log` in your project directory
2. Check your hosting provider's error logs
3. Common issues:
   - Missing database
   - Wrong credentials
   - Missing PHP extensions (mysqli, pdo)
   - File permission issues

## Complete Deployment Steps

### 1. Prepare Your Hosting Environment

**Requirements:**
- PHP 7.4 or higher
- MySQL 5.7 or higher
- mysqli and PDO extensions enabled

### 2. Upload Files

1. Upload all project files to your hosting directory
2. Usually to `public_html/` or `www/` folder

### 3. Database Setup

1. **Create a new database** in your hosting control panel
2. **Create a database user** with full privileges
3. **Import the schema:**
   ```sql
   -- Run this in phpMyAdmin or similar tool
   SOURCE db/schema.sql;
   ```

### 4. Configuration

1. **Update database settings** in `includes/config.php`
2. **Create initial admin user** by running:
   ```sql
   -- Run this in your database
   SOURCE create_pos_admin_user.sql;
   ```

### 5. Security Setup

1. **Change default passwords** in SQL files before running them
2. **Set proper file permissions**
3. **Enable HTTPS** if available

### 6. Test the System

1. Visit your domain
2. Try logging in with the admin credentials
3. Test different modules (POS, Admin, etc.)

## Common Hosting Provider Instructions

### cPanel Hosting
1. Use File Manager to upload files
2. Use MySQL Databases to create database
3. Use phpMyAdmin to import schema

### Shared Hosting
- Database host is usually `localhost`
- Check with your provider for specific settings

### VPS/Dedicated Server
- You may need to install PHP extensions
- Configure Apache/Nginx virtual hosts
- Set up SSL certificates

## Troubleshooting Common Issues

### "Database connection failed"
- Check database credentials
- Ensure database exists
- Verify user has proper privileges

### "Table doesn't exist"
- Import the database schema
- Check table names are correct

### "Permission denied"
- Set proper file permissions
- Check directory ownership

### "Class not found"
- Ensure all files were uploaded
- Check PHP version compatibility

## Support

If you continue to experience issues:
1. Check the error logs first
2. Verify all deployment steps were followed
3. Contact your hosting provider for server-specific issues

## Security Notes

- Change default passwords immediately
- Keep the system updated
- Regular database backups
- Use HTTPS in production
- Restrict file permissions appropriately