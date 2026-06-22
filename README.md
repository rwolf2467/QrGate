<img src="backend/data/assets/logo.png" width="96" alt="QrGate">

# QrGate

`// open-source · since 2024`

**Ticketing & access control that just runs.** Sell tickets, validate QR codes at the door, and manage events from one admin panel — Python/Quart backend, PHP frontend. Bilingual (English + German), light **and** dark.

Part of the [avocloud](https://avocloud.net) toolset.

## Run it (fastest)

One image, no config files — pull, start detached, finish in the browser:

```bash
docker run -d --name qrgate -p 8080:80 \
  -e QRGATE_WEB_PORT=8080 \
  -v qrgate_data:/app/backend/data \
  -v qrgate_codes:/app/backend/codes \
  redwolf2467/qrgate
```

Open <http://localhost:8080> → you're redirected to the **`/install` wizard**, which generates your API secret and walks you through SMTP, the first event and the admin password. Details: [Single All-in-One Container](#single-all-in-one-container). Want two independently restartable containers instead? See [Quick Start with Docker](#quick-start-with-docker).

## Overview

QrGate is a comprehensive system for managing events, tickets, and access control. It consists of a **Backend** (Python/Quart) and a **Frontend** (PHP) with a modern, responsive user interface.

### Key Features

- **Guided Ticket Checkout**: Multi-step booking wizard (details → tickets & names → payment → confirm) with **Stripe** (card) and **cash** (pay-at-door) support
- **Binding Booking Consent**: Mandatory consent checkbox plus cancellation/storno info, enforced server-side; configurable contact email shown to customers
- **Ticket Delivery**: PDF tickets by email with the QR code embedded inline (generated in-memory — single-page layout)
- **Access Control**: QR code-based ticket validation for entry, with a mobile handheld scanner
- **Admin Panel**: Dashboard for events, dates, locations, images, tickets and statistics
- **Maintenance & Data Tools**: One-click **database backup** download plus a guarded danger zone (wipe data, reinstall, factory reset)
- **Multi-language Support**: German and English
- **Responsive Design**: Optimized for desktop and mobile, light **and** dark

### Security Features

- **CSRF Protection**: All forms are protected against Cross-Site Request Forgery attacks
- **XSS Prevention**: User inputs are sanitized using `htmlspecialchars()`
- **Session-based Authentication**: Secure login system with PHP sessions
- **API Key Authentication**: Backend communication secured with authorization headers
- **Role-based Access Control**: Three user levels (Admin, Ticketflow, Handheld)

## Screenshots
<img width="2560" height="1492" alt="image" src="https://github.com/user-attachments/assets/d7e8562a-fd46-45d8-a389-45bc17bfee2e" />
<img width="2560" height="1492" alt="image" src="https://github.com/user-attachments/assets/9dc34513-e245-4569-acdc-d3ec11d130da" />
<img width="1840" height="1263" alt="image" src="https://github.com/user-attachments/assets/e013fbab-2cf3-4f2a-aeb7-dfbfa23a63f7" />

<img width="585" height="1266" alt="IMG_8895" src="https://github.com/user-attachments/assets/45bad24d-b2eb-45c1-b5b6-9da6a7c2360b" />
<img width="585" height="1266" alt="IMG_8894" src="https://github.com/user-attachments/assets/c383987e-23ac-4e6b-b2a1-f196bca5a937" />



## Installation

There are three ways to run QrGate:

- **[Quick Start with Docker](#quick-start-with-docker)** — recommended. One command brings up both containers on a shared network.
- **[Single All-in-One Container](#single-all-in-one-container)** — backend, PHP and nginx in one image; publish the whole project as a single Docker stack.
- **[Manual Installation](#manual-installation)** — run the backend and frontend directly on the host.

## Quick Start with Docker

The repository ships a full Docker setup: a Python backend container and an nginx + PHP-FPM frontend container, wired together on a shared bridge network. The frontend reaches the backend internally at `http://backend:1654`, so the backend never needs to be exposed to the host.

### Requirements

- Docker Engine 20.10+
- Docker Compose v2 (`docker compose`)

### Steps

1. **Clone the repository:**

   ```bash
   git clone https://github.com/rwolf2467/QrGate.git
   cd QrGate
   ```
2. **Create your environment file:**

   ```bash
   cp .env.example .env
   ```
   Edit `.env` and set real secrets. At minimum change `QRGATE_AUTH_KEY` (shared by backend and frontend — they must match) and the role passwords. Generate a strong key with:

   ```bash
   openssl rand -hex 32
   ```
3. **Build and start the stack:**

   ```bash
   docker compose up -d --build
   ```
4. **Open the app:** the frontend is published on [http://localhost:8080](http://localhost:8080). Change the host port in `docker-compose.yml` (`ports: "8080:80"`) if needed, and put a reverse proxy with TLS in front for production.
5. **Run the setup wizard** — see below.

## First-Run Setup Wizard

On a fresh install QrGate is **not configured yet**. The first time the backend container starts it prints a banner to the logs with the wizard link:

```
================================================================
 QrGate is NOT yet set up.
 Open the setup wizard to finish installation:
   -> http://localhost:8080/install
================================================================
```

(The printed URL comes from `QRGATE_SETUP_URL`; set it to your real public address.)

Open `/install` and the wizard walks you through:

1. **E-mail (SMTP)** — server, port, username and password used to send tickets.
2. **First event** — organizer name, event title/subtitle, first date, ticket count, price and payment methods.
3. **Admin account** — the password for the `admin` login (min. 8 characters).

Until setup is finished, **every page automatically redirects to `/install`**. After completing the wizard the system is marked as installed, the redirect stops, and you are sent to the admin login. Ticket sales stay **locked** until you deliberately open them from the admin dashboard.

The wizard writes SMTP settings and the install flag to the backend's `settings` table (persisted in the `qrgate_data` volume), creates the first event, and sets the admin password — no config files need to be edited by hand. The `/api/setup/complete` endpoint locks itself once installation is done.

### What the Docker setup contains

| File | Purpose |
|------|---------|
| `docker-compose.yml` | Orchestrates both containers, the shared `qrgate` network, and named volumes |
| `.env.example` | Template for all configuration/secrets injected at runtime |
| `backend/Dockerfile` | Python 3.12 image, installs `backend/requirements.txt`, runs `main.py` |
| `frontend/Dockerfile` | Multi-stage: Composer deps + nginx/PHP-FPM runtime via supervisor |
| `frontend/docker/` | nginx, php-fpm, supervisor and PHP ini config |

**Persistence:** ticket/show/stats data (`backend/data`) and generated PDFs/QR codes (`backend/codes`) are stored in the named volumes `qrgate_data` and `qrgate_codes`, so they survive container rebuilds.

**Configuration via environment:** both `backend/config/conf.py` and `frontend/config.php` read their values from `QRGATE_*` environment variables, falling back to the in-file defaults when unset. This means you configure a Docker deployment entirely through `.env` — no need to edit the config files.

**Common commands:**

```bash
docker compose logs -f          # tail logs from both containers
docker compose down             # stop and remove containers (keeps volumes)
docker compose up -d --build    # rebuild after code changes
docker compose down -v          # also remove data volumes (DESTROYS data)
```

## Single All-in-One Container

If you'd rather ship the **whole project as one image / one stack**, the root [`Dockerfile`](Dockerfile) bundles the Python backend, PHP-FPM and nginx into a single container, supervised by `supervisor`. The frontend talks to the backend over `localhost` inside the container, so only port 80 is exposed.

> Trade-off: simpler to publish and run, but you can't restart/scale backend and frontend independently. For that, use the two-container [`docker-compose.yml`](docker-compose.yml) above.

### Zero-config quick start

**No environment variables required.** The container boots with a temporary bootstrap secret; you generate the real one (and everything else) in the web installer:

```bash
docker pull redwolf2467/qrgate
docker run -d --name qrgate -p 8080:80 \
  -e QRGATE_WEB_PORT=8080 \
  -v qrgate_data:/app/backend/data \
  -v qrgate_codes:/app/backend/codes \
  redwolf2467/qrgate
```

> `QRGATE_WEB_PORT` is only used to print the correct setup link in the console on first start (the container can't see the host's `-p` mapping). Set it to whatever host port you published. The console prints the server's public IP automatically.

Open <http://localhost:8080> → it redirects to the **`/install` wizard**. There you configure SMTP, the first event, the admin password and — in the **Security** step — your API secret:

- a strong random key is **pre-generated** in your browser, with a **↻ Regenerate** button;
- on **Finish & restart** the key is saved to a shared key file, the backend **restarts automatically**, and the page shows a loader that **reconnects on its own** and sends you to the admin login.

The volumes keep your data, tickets and the generated key across restarts/upgrades. That's it — no `-e` flags, no editing files.

### Optional: pre-seed config via env / Compose

You can still set everything up front (skips parts of the wizard) — e.g. with Compose:

```bash
cp .env.example .env          # set QRGATE_AUTH_KEY + passwords
docker compose -f docker-compose.single.yml up -d --build
```

…or with plain `docker run -e QRGATE_AUTH_KEY=… -e QRGATE_ADMIN_PASSWORD=… …`. Any `QRGATE_*` from [`.env.example`](.env.example) works; a value set this way is used as the default until you change it in the wizard. The container derives the frontend's API key from `QRGATE_AUTH_KEY`, so the secret is only set once. Then follow the [setup wizard](#first-run-setup-wizard).

> Note: online key rotation in the wizard relies on a key file shared between backend and frontend, so it applies to the **single-container** setups. In the two-container [`docker-compose.yml`](docker-compose.yml), set the secret via `QRGATE_AUTH_KEY` instead.


## Manual Installation

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
   pip install -r requirements.txt
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
│   ├── config/              # Configuration files (conf.py, env-overridable)
│   ├── codes/               # Generated PDFs and QR codes
│   ├── data/                # Data storage (shows, tickets, stats)
│   ├── requirements.txt     # Python dependencies
│   ├── Dockerfile           # Backend container image
│   └── main.py              # Main backend server
│
├── frontend/
│   ├── admin/               # Admin interface
│   │   ├── ticketflow/      # Box office interface
│   │   └── handheld/        # Mobile QR scanner
│   ├── help/                # Help pages
│   ├── screens/             # Event display screens
│   ├── docker/              # nginx, php-fpm, supervisor config for the container
│   ├── buy.php              # Ticket purchase
│   ├── config.php           # Frontend configuration (env-overridable)
│   ├── Dockerfile           # Frontend container image (nginx + PHP-FPM)
│   └── index.php            # Main page
│
├── Dockerfile               # All-in-one image (backend + PHP + nginx)
├── docker/                  # Service config for the all-in-one image
├── docker-compose.yml       # Two-container stack (backend + frontend)
├── docker-compose.single.yml# Single all-in-one container stack
├── .env.example             # Configuration/secrets template for Docker
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

Frontend configuration is done in `frontend/config.php`. Here you can adjust settings such as the API base URL, authentication key, and user passwords. Stripe payment settings are configured directly in the Admin Dashboard.

**Important:** The `API_KEY` in the frontend must match the `auth_key` in the backend configuration.

### Configuration via Environment Variables

For containerized or 12-factor deployments, every config value can be overridden with a `QRGATE_*` environment variable instead of editing the config files. When a variable is unset, the in-file default is used. This is how the Docker setup is configured — see [`.env.example`](.env.example) for the full list.

| Variable | Applies to | Maps to |
|----------|-----------|---------|
| `QRGATE_AUTH_KEY` | backend + frontend | `Auth.auth_key` / `API_KEY` (must match) |
| `QRGATE_API_BASE_URL` | frontend | `API_BASE_URL` (set to `http://backend:1654/` in Docker) |
| `QRGATE_BACKEND_URL` | backend | `API.backend_url` |
| `QRGATE_FRONTEND_ORIGIN` | backend | `API.frontend_origin` (CORS) |
| `QRGATE_ORIGIN_URL` | frontend | `ORIGIN_URL` |
| `QRGATE_ADMIN_PASSWORD` / `QRGATE_TICKETFLOW_PASSWORD` / `QRGATE_HANDHELD_PASSWORD` | backend + frontend | role passwords |
| `QRGATE_SMTP_SERVER` / `QRGATE_SMTP_PORT` / `QRGATE_SMTP_USER` / `QRGATE_SMTP_PASSWORD` | backend | `Mail.*` |

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
