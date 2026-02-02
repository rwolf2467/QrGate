# QrGate - Modern Ticketing and Access Control System

![QrGate Logo](backend/data/assets/logo.png)

**QrGate** is a modern, web-based ticketing and access control system for events. It enables ticket sales, management, and QR code-based validation, providing a user-friendly interface for administrators and visitors.

## Overview

QrGate is a comprehensive system for managing events, tickets, and access control. It consists of a **Backend** (Python/Quart) and a **Frontend** (PHP) with a modern, responsive user interface.

### Key Features

- **Ticket Sales**: Simple ticket purchase via web interface with support for various payment methods (cash, PayPal)
- **Access Control**: QR code-based ticket validation for entry
- **Admin Panel**: Comprehensive dashboard for managing events, tickets, and statistics
- **Multi-language Support**: German and English
- **Responsive Design**: Optimized for desktop and mobile devices

### Security Features

- **CSRF Protection**: All forms are protected against Cross-Site Request Forgery attacks
- **XSS Prevention**: User inputs are sanitized using `htmlspecialchars()`
- **Session-based Authentication**: Secure login system with PHP sessions
- **API Key Authentication**: Backend communication secured with authorization headers
- **Role-based Access Control**: Three user levels (Admin, Ticketflow, Handheld)

## Installation

### Requirements

**Backend:**

- Python 3.7 or higher
- pip (Python package manager)

**Frontend:**

- Web server with PHP support (e.g., Apache with mod_php, Nginx with PHP-FPM, XAMPP, or LAMP)
- PHP 7.4 or higher
- Composer (PHP package manager)
- PHP cURL extension enabled

### Installation Steps

1. **Clone the repository:**

   ```bash
   git clone https://github.com/rwolf2467/QrGate.git
   cd QrGate
   ```
2. **Set up the Backend:**

   ```bash
   cd backend
   pip install quart quart-cors reportlab qrcode reds_simple_logger
   ```
3. **Set up the Frontend:**

   ```bash
   cd frontend
   composer install
   ```
4. **Configure the application:**

   **Backend** (`backend/config/conf.py`):

   ```python
   class API:
       port = 1654
       backend_url = "https://your-backend-url.com/"
   
   class Auth:
       auth_key = "YourSecureRandomKeyHere"
   
   class Mail:
       smtp_server = "smtp.example.com"
       smtp_port = 587
       smtp_user = "user@example.com"
       smtp_password = "your_smtp_password"
   ```

   **Frontend** (`frontend/config.php`):

   ```php
   define('API_BASE_URL', 'https://your-backend-url.com');
   define('API_KEY', 'YourSecureRandomKeyHere');  // Must match backend auth_key
   define('PAYPAL_CLIENT_ID', 'YourPayPalClientID');
   define('PAYPAL_CLIENT_SECRET', 'YourPayPalClientSecret');
   
   // Change these passwords in production!
   define('ADMIN_PASSWORD', 'your_secure_admin_password');
   define('TICKETFLOW_PASSWORD', 'your_secure_ticketflow_password');
   define('HANDHELD_PASSWORD', 'your_secure_handheld_password');
   ```
5. **Configure the web server:**
   - Point your web server's document root to the `frontend/` directory
   - Ensure the `backend/data/` and `backend/codes/` directories are writable
6. **Start the application:**

   **Backend:**

   ```bash
   cd backend
   python main.py
   ```

   **Frontend:** Access via your web server (e.g., `https://your-domain.com`)

## Project Structure

```
QrGate/
├── backend/
│   ├── assets/              # Backend modules (ticket management, validation, etc.)
│   ├── config/              # Configuration files
│   ├── codes/               # Generated PDFs and QR codes
│   ├── data/                # Data storage (shows, tickets, stats)
│   └── main.py              # Main backend server
│
├── frontend/
│   ├── admin/               # Admin interface
│   │   ├── ticketflow/      # Box office interface
│   │   └── handheld/        # Mobile QR scanner
│   ├── help/                # Help pages
│   ├── screens/             # Event display screens
│   ├── buy.php              # Ticket purchase
│   ├── config.php           # Frontend configuration
│   └── index.php            # Main page
│
└── README.md
```

## Usage

### Ticket Sales

1. Navigate to the application homepage
2. Select the desired event
3. Fill out the form and confirm the purchase
4. Your ticket will be sent via email or can be downloaded

### Access Control

1. Log in with handheld credentials
2. Navigate to the access control interface
3. Scan the ticket's QR code
4. The system validates the ticket and displays the status

### Administration

1. Log in as administrator
2. Navigate to the admin panel
3. Manage events, tickets, and view statistics

## Admin Panel

The admin panel provides the following features:

- **Dashboard**: Overview of sold tickets, available tickets, and estimated revenue
- **Statistics**: Graphical display of ticket sales and availability
- **Event Management**: Edit event settings
- **Date Management**: Add, edit, and delete event dates
- **Image Management**: Upload and manage event images

## API Routes

| Route                | Method | Purpose             |
|----------------------|--------|---------------------|
| `/api/ticket/create`   | POST   | Create ticket       |
| `/api/ticket/validate` | POST   | QR validation       |
| `/api/show/get`        | GET    | Event info          |
| `/api/show/edit`       | POST   | Update event        |
| `/api/stats`           | GET    | Sales statistics    |
| `/codes/pdf?tid=X`     | GET    | Download ticket PDF |

## Configuration

### Backend Configuration

Backend configuration is done in `backend/config/conf.py`. Here you can adjust settings such as the API port, backend URL, authentication keys, and SMTP settings for email delivery.

### Frontend Configuration

Frontend configuration is done in `frontend/config.php`. Here you can adjust settings such as the API base URL, authentication key, PayPal credentials, and user passwords.

**Important:** The `API_KEY` in the frontend must match the `auth_key` in the backend configuration.

## Contributing

We welcome contributions to QrGate. Please follow these steps:

1. Fork the repository
2. Create a new branch for your changes
3. Implement and test your changes
4. Create a pull request with a description of your changes

## License

QrGate is released under the MIT License. See the [LICENSE](backend/LICENSE) file for more information.

## Support

For questions or support, you can create an issue in the repository or contact us at:

- **Email**: support@avocloud.net

---

Developed by [avocloud.net](https://avocloud.net)