# KAAGAZZ Book Store - Project Specification

## 1. Concept & Vision

KAAGAZZ is a dynamic online book marketplace where users can buy and sell books across various categories. The platform provides a seamless, trustworthy experience for book enthusiasts to discover, list, and purchase books with full user authentication and admin management capabilities.

## 2. Design Language

### Aesthetic Direction
- Clean, professional bookshop aesthetic with warm tones
- Bootstrap 5 based responsive design
- Font Awesome icons for UI elements

### Color Palette
- Primary: `#2E8BC0` (Book blue)
- Dark: `#0C2D48` (Deep navy)
- Black: `#444` (Text)
- Light: `#666` (Secondary text)
- White: `#fff` (Backgrounds)
- Accent: `#ff4444` (Wishlist hearts)

### Typography
- Primary: 'Poppins', sans-serif
- Weights: 100, 300, 400, 500, 600

### Layout
- Navbar with user authentication controls
- Hero banner with featured books carousel
- Category-based book grid
- Shopping cart sidebar
- Footer with site links

## 3. Features & Interactions

### User Authentication
- User registration with name, email, password
- Login/logout functionality
- Session-based authentication
- User profile with order history

### Book Management
- Browse all books with pagination
- Search books by title/author
- Filter by category
- Book detail view

### Shopping Cart
- Add/remove items
- Quantity adjustment
- Persistent cart (database-backed)
- Checkout process

### Admin Panel
- Add/edit/delete books
- Manage categories
- View all users and orders

### Seller Features
- List books for sale
- View own listings
- Edit/delete own listings

## 4. Technical Architecture

### Frontend
- HTML5, CSS3 (Bootstrap 5)
- JavaScript + jQuery
- AJAX for dynamic content loading

### Backend
- PHP 7.4+
- MySQL Database
- Session-based auth

### Database Schema

**users**
- id (INT, PK, AUTO_INCREMENT)
- name (VARCHAR 255)
- email (VARCHAR 255, UNIQUE)
- password (VARCHAR 255, hashed)
- role (ENUM: 'user', 'seller', 'admin')
- created_at (TIMESTAMP)

**categories**
- id (INT, PK, AUTO_INCREMENT)
- name (VARCHAR 255)
- slug (VARCHAR 255)

**books**
- id (INT, PK, AUTO_INCREMENT)
- title (VARCHAR 255)
- author (VARCHAR 255)
- description (TEXT)
- price (DECIMAL 10,2)
- category_id (INT, FK)
- seller_id (INT, FK)
- image (VARCHAR 255)
- stock (INT)
- created_at (TIMESTAMP)

**orders**
- id (INT, PK, AUTO_INCREMENT)
- user_id (INT, FK)
- total (DECIMAL 10,2)
- status (ENUM: 'pending', 'processing', 'shipped', 'delivered')
- created_at (TIMESTAMP)

**order_items**
- id (INT, PK, AUTO_INCREMENT)
- order_id (INT, FK)
- book_id (INT, FK)
- quantity (INT)
- price (DECIMAL 10,2)

**cart**
- id (INT, PK, AUTO_INCREMENT)
- user_id (INT, FK)
- book_id (INT, FK)
- quantity (INT)

## 5. File Structure

```
book-store-app/
├── index.php              # Main frontend
├── admin/
│   ├── index.php          # Admin dashboard
│   ├── books.php          # Manage books
│   ├── orders.php         # Manage orders
│   └── users.php          # Manage users
├── auth/
│   ├── login.php          # Login page
│   ├── register.php       # Registration page
│   └── logout.php        # Logout handler
├── api/
│   ├── cart.php           # Cart API
│   ├── books.php          # Books API
│   └── orders.php         # Orders API
├── config/
│   └── database.php       # Database connection
├── css/
│   └── style.css          # Custom styles
├── js/
│   └── main.js            # jQuery/JavaScript
├── image/                 # Book images
└── assets/                # Bootstrap, Font Awesome
```
