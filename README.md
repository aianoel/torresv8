# Torres Hotel Management System

A comprehensive hotel management system built with PHP and MySQL, designed to streamline hotel operations including reservations, room management, POS system, HR, accounting, and more.

## Features

### Core Modules
- **Admin Dashboard** - Complete system administration and oversight
- **Front Desk** - Reservation management and guest services
- **Housekeeping** - Room status and cleaning management
- **HR Management** - Employee management, attendance, payroll, and leave requests
- **Accounting** - Financial management, invoices, payments, and reports
- **POS System** - Point of sale with RFID card integration

### Key Capabilities
- Multi-role user management (Admin, Front Desk, Housekeeping, HR, Accounting, POS)
- Room availability and status tracking
- Guest reservation system
- RFID card-based POS transactions
- Employee attendance and payroll management
- Financial reporting and invoice generation
- QR code generation for various purposes
- Content management for public landing page
- Responsive web design

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Icons**: Font Awesome
- **Additional**: PDO for database operations, RFID integration

## Project Structure

```
torresv8/
├── admin/              # Admin panel modules
├── accounting/         # Accounting and financial management
├── api/               # API endpoints
├── assets/            # Static assets (CSS, JS, images)
├── database/          # Database migrations and schema
├── frontdesk/         # Front desk operations
├── housekeeping/      # Housekeeping management
├── hr/                # Human resources management
├── includes/          # Shared PHP includes and configuration
├── pos_admin/         # POS system administration
├── pos_cashier/       # POS cashier interface
├── public/            # Public-facing pages
└── db/                # Database utilities and schema
```

## Installation

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser with JavaScript enabled

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/torres-hotel-management.git
   cd torres-hotel-management
   ```

2. **Database Setup**
   - Create a new MySQL database
   - Import the database schema from `db/schema.sql`
   - Run migration files from `database/migrations/` if needed

3. **Configuration**
   - Copy `includes/config.sample.php` to `includes/config.php`
   - Update database credentials in `includes/config.php`:
     ```php
     define('DB_HOST', 'your_host');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'your_database');
     ```

4. **Web Server Configuration**
   - Point your web server document root to the project directory
   - Ensure PHP has write permissions for logs directory
   - Enable PHP extensions: mysqli, pdo_mysql

5. **Initial Setup**
   - Access the application through your web browser
   - Use the default admin credentials (update immediately after first login)
   - Configure system settings through the admin panel

## Default Users

After running the database schema, you can create initial users using the provided SQL files:

- `create_pos_admin_user.sql` - Creates POS admin user
- `create_accounting_user.sql` - Creates accounting user

**Important**: Change all default passwords immediately after installation.

## Usage

### Admin Panel
- Access: `/admin/dashboard.php`
- Manage users, rooms, content, and system settings
- Generate QR codes and manage leave requests

### POS System
- Admin: `/pos_admin/dashboard.php`
- Cashier: `/pos_cashier/dashboard.php`
- Supports RFID card transactions and inventory management

### Other Modules
- Front Desk: `/frontdesk/dashboard.php`
- Housekeeping: `/housekeeping/dashboard.php`
- HR: `/hr/dashboard.php`
- Accounting: `/accounting/dashboard.php`

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Session-based authentication
- Role-based access control
- Input validation and sanitization

## Development

### File Structure Guidelines
- Place shared functions in `includes/functions.php`
- Database configuration in `includes/config.php` (not committed)
- Use `includes/auth.php` for authentication checks
- Follow existing code patterns and naming conventions

### Database Migrations
- Place new migrations in `database/migrations/`
- Use descriptive filenames with timestamps
- Include rollback instructions in comments

## Deployment

### Production Deployment
1. Use `includes/config_production.php` as template for production config
2. Enable error logging and disable display_errors
3. Set up SSL/HTTPS
4. Configure regular database backups
5. Update all default passwords
6. Review and configure security settings

See `DEPLOYMENT_GUIDE.md` for detailed deployment instructions.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Create an issue in the GitHub repository
- Check the documentation in the `docs/` directory
- Review the deployment guide for common setup issues

## Changelog

See `CHANGELOG.md` for version history and updates.

---

**Note**: This system is designed for hotel management operations. Ensure proper security measures are in place before deploying to production environments.