# 🚀 Admin Dashboard - WordPress Orders + Tawk.to Chat

Modern PHP dashboard for managing WooCommerce orders and Tawk.to live chats.

## ✅ Features
- **Authentication** — Login/logout with session management
- **User Management** — Add/edit/delete users with roles (Super Admin, Admin, Agent)
- **WooCommerce Orders** — View, filter, update order statuses
- **Tawk.to Live Chats** — View conversations, send replies in real-time
- **Support Tickets** — View and reply to Tawk.to tickets
- **Dashboard Stats** — Live stats from both platforms
- **Settings** — Configure API keys + test connections
- **Modern Dark UI** — Professional, responsive design

## 🛠️ Requirements
- PHP 7.4+
- MySQL 5.7+
- cURL extension enabled
- WooCommerce on WordPress
- Tawk.to account with REST API access

## ⚡ Quick Setup

### 1. Upload Files
Upload the `dashboard/` folder to your web server.

### 2. Run Installer
Open: `http://yoursite.com/dashboard/install.php`
Fill in database details + admin account → Click Install.

### 3. Configure Config
Edit `includes/config.php`:
```php
define('APP_URL', 'http://yoursite.com/dashboard');  // Your dashboard URL
```

### 4. Login & Configure API
- Login at `login.php`
- Go to Settings → Enter WooCommerce & Tawk.to API keys
- Test connections

### 5. Delete installer
Delete `install.php` after setup!

## 📁 File Structure
```
dashboard/
├── index.php              # Main dashboard
├── login.php              # Login page
├── logout.php             # Logout
├── install.php            # One-time installer (delete after use!)
├── includes/
│   ├── config.php         # ⚙️ Configuration (edit this!)
│   ├── db.php             # Database class
│   ├── auth.php           # Authentication
│   ├── woocommerce.php    # WooCommerce API class
│   ├── tawkto.php         # Tawk.to API class
│   ├── functions.php      # Helper functions
│   ├── layout_header.php  # Sidebar + topbar
│   └── layout_footer.php  # Footer + scripts
├── pages/
│   ├── orders.php         # Orders management
│   ├── chats.php          # Live chat panel
│   ├── tickets.php        # Support tickets
│   ├── users.php          # User management
│   └── settings.php       # API settings
└── assets/
    ├── css/style.css      # Main stylesheet
    └── js/app.js          # Main JavaScript
```

## 🔑 Default Login
After running `install.php`, use the admin credentials you created.

## 📞 Support
Configure API keys in Settings page. Use "Test Connection" buttons to verify.
