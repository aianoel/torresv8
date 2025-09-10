# Torres Hotel Management System

A comprehensive hotel management system built with PHP and MySQL, featuring multiple modules for different hotel operations.

## Features

### Core Modules
- **Admin Dashboard** - System administration and user management
- **Front Desk** - Guest check-in/check-out and reservations
- **Housekeeping** - Room status and cleaning management
- **Accounting** - Financial management and reporting
- **HR** - Employee management, attendance, and payroll
- **POS System** - Point of sale for hotel services

### Key Capabilities
- Multi-role user authentication
- Room management and availability tracking
- Guest registration and booking system
- Payment processing and invoicing
- Employee attendance and leave management
- RFID card integration for POS
- QR code generation for various purposes
- Comprehensive reporting system

## Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache/Nginx
- **PHP Extensions**: mysqli, PDO, GD (for QR codes)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/torres-hotel-management.git
cd torres-hotel-management
```

### 2. Database Setup

1. Create a new MySQL database
2. Import the database schema:
   ```sql
   mysql -u your_username -p your_database_name < db/schema.sql
   ```

### 3. Configuration

1. Copy the sample configuration file:
   ```bash
   cp includes/config.sample.php includes/config.php
   ```

2. Edit `includes/config.php` and update the database credentials:
   ```php
   define('DB_HOST', 'your_database_host');
   define('DB_USER', 'your_database_username');
   define('DB_PASS', 'your_database_password');
   define('DB_NAME', 'your_database_name');
   ```

### 4. Create Initial Users

1. Copy and customize the user creation scripts:
   ```bash
   cp create_pos_admin_user.sql.sample create_pos_admin_user.sql
   cp create_accounting_user.sql.sample create_accounting_user.sql
   ```

2. **IMPORTANT**: Edit these files to change default passwords before running!

3. Run the scripts to create initial users:
   ```sql
   mysql -u your_username -p your_database_name < create_pos_admin_user.sql
   mysql -u your_username -p your_database_name < create_accounting_user.sql
   ```

### 5. Set Permissions

Ensure the web server has write permissions to:
- `logs/` directory (will be created automatically)
- Any upload directories

## Deployment

### Production Deployment

For production deployment, see the detailed [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md).

**Quick production checklist:**
1. Use `includes/config_production.php` as a template
2. Disable error display in production
3. Set up proper error logging
4. Use HTTPS
5. Change all default passwords
6. Set proper file permissions

### Environment Variables

For enhanced security, consider using environment variables for sensitive configuration:

```php
// In config.php
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'hotel_management');
```

## Usage

### Default Login Credentials

**⚠️ CHANGE THESE IMMEDIATELY AFTER INSTALLATION!**

- **POS Admin**: `posadmin` / `admin123`
- **Accounting**: `accounting` / `password`

### Module Access

- **Admin Panel**: `/admin/dashboard.php`
- **POS Admin**: `/pos_admin/dashboard.php`
- **POS Cashier**: `/pos_cashier/dashboard.php`
- **Front Desk**: `/frontdesk/dashboard.php`
- **Housekeeping**: `/housekeeping/dashboard.php`
- **Accounting**: `/accounting/dashboard.php`
- **HR**: `/hr/dashboard.php`

## Security Notes

1. **Change Default Passwords**: Always change default passwords before deployment
2. **Database Security**: Use strong database passwords and limit user privileges
3. **File Permissions**: Set appropriate file permissions (644 for files, 755 for directories)
4. **HTTPS**: Always use HTTPS in production
5. **Error Logging**: Enable error logging but disable error display in production
6. **Regular Updates**: Keep PHP and MySQL updated

## Troubleshooting

### Common Issues

1. **HTTP 500 Error**: Usually database connection issues
   - Check database credentials in `config.php`
   - Verify database exists and is accessible
   - Check error logs in `logs/error.log`

2. **Database Connection Failed**: 
   - Verify MySQL service is running
   - Check database credentials
   - Ensure database exists

3. **Permission Denied**:
   - Check file permissions
   - Ensure web server can write to logs directory

### Testing Database Connection

Visit `/db/test_connection.php` to test your database connection.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Check the [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for deployment issues
- Review the troubleshooting section above
- Check the error logs for specific error messages

## Project Structure

```
torresv8/
├── admin/              # Admin panel modules
├── accounting/         # Accounting module
├── api/               # API endpoints
├── assets/            # CSS, JS, images
├── database/          # Database migrations
├── db/                # Database schema and utilities
├── frontdesk/         # Front desk operations
├── housekeeping/      # Housekeeping module
├── hr/                # Human resources module
├── includes/          # Configuration and common files
├── pos_admin/         # POS administration
├── pos_cashier/       # POS cashier interface
├── public/            # Public-facing pages
└── logs/              # Application logs (auto-created)
```