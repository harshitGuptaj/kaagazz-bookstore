# KAAGAZZ - Online Book Store

A dynamic web platform for buying and selling books across various categories.

## Features

- **User Authentication** - Register, login, logout with secure password hashing
- **Browse Books** - View featured books, new arrivals, search by title/author
- **Shopping Cart** - Add/remove items, adjust quantities, persistent cart
- **Wishlist** - Save books for later
- **Order Management** - Place orders, view order history, track status
- **Admin Panel** - Manage books, users, orders, categories
- **Seller Dashboard** - List and manage book listings

## Tech Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript, jQuery
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Icons**: Font Awesome 6
- **Fonts**: Google Fonts (Poppins)

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or XAMPP/WAMP/MAMP

### Setup

1. **Clone/Download** the project to your web server directory

2. **Create Database**
   - Open phpMyAdmin or MySQL CLI
   - Run the SQL commands from `config/schema.sql`

3. **Update Database Configuration**
   - Edit `config/database.php`
   - Update DB_HOST, DB_USER, DB_PASS, DB_NAME if needed

4. **Start Server**
   - If using XAMPP/WAMP, start Apache and MySQL
   - Access via `http://localhost/book-store-app/`

5. **Default Admin Login**
   - Email: admin@kaagazz.com
   - Note: Run this SQL to set proper password:
   ```sql
   UPDATE users SET password = '$2y$10$8K1p/a0dR1xqM8K3hQv1aOQZQZQZQZQZQZQZQZQZQZQZQZQZQZQZQ' WHERE email = 'admin@kaagazz.com';
   ```
   - Or register a new admin and update role manually:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
   ```

## Project Structure

```
book-store-app/
├── index.php              # Main storefront
├── auth/
│   ├── login.php          # Login page
│   ├── register.php       # Registration page
│   └── logout.php         # Logout handler
├── admin/
│   ├── index.php          # Admin dashboard
│   ├── books.php          # Manage books
│   ├── orders.php         # Manage orders
│   └── users.php          # Manage users
├── api/
│   ├── cart.php           # Cart API
│   ├── books.php          # Books API
│   ├── orders.php         # Orders API
│   └── wishlist.php       # Wishlist API
├── config/
│   ├── database.php       # Database connection
│   └── schema.sql         # Database schema
├── seller/
│   └── index.php          # Seller dashboard
├── image/                 # Book images
├── css/                   # Stylesheets
└── js/                    # JavaScript files
```

## Usage

### For Customers
1. Register an account (or login)
2. Browse books by category or use search
3. Add books to cart or wishlist
4. Proceed to checkout
5. Track orders in your profile

### For Sellers
1. Register with "Seller" account type
2. Access seller dashboard
3. Add new book listings
4. Track sales and inventory

### For Admins
1. Login to admin panel
2. Manage all books, users, orders
3. Update order statuses
4. Add/edit categories

## Security

- Passwords hashed with `password_hash()` (Bcrypt)
- Prepared statements prevent SQL injection
- Session-based authentication
- Role-based access control

## License

This project is for educational purposes.
