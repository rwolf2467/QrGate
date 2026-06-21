# QrGate - Modern Ticketing and Access Control System

![QrGate Logo](backend/data/assets/logo.png)

**QrGate** is a modern, web-based ticketing and access control system for events. It enables ticket sales, management, and QR code-based validation, providing a user-friendly interface for administrators and visitors.

## Overview

QrGate is a comprehensive system for managing events, tickets, and access control. It consists of a **Backend** (Python/Quart) and a **Frontend** (PHP) with a modern, responsive user interface.

### Key Features

- **Ticket Sales**: Simple ticket purchase via web interface with support for various payment methods (cash, Stripe)
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

## Screenshots
<img width="2559" height="1481" alt="image" src="https://github.com/user-attachments/assets/f9306b5c-6986-4feb-995f-c33abb9ef94c" />
<img width="2559" height="1481" alt="image" src="https://github.com/user-attachments/assets/99df98d8-4dda-488e-87b5-ff37d477349a" />
---
<img width="2559" height="1481" alt="image" src="https://github.com/user-attachments/assets/10506a2e-93d3-497c-89bf-20705cd20036" />
<img width="2559" height="1481" alt="image" src="https://github.com/user-attachments/assets/f5bf87e0-1dcc-487c-a0fc-2e607ad01406" />
<img width="2559" height="1481" alt="image" src="https://github.com/user-attachments/assets/c1bfce15-6743-4c7f-a2c4-0206fa70d5ce" />
---
<img width="642" height="1396" alt="image" src="https://github.com/user-attachments/assets/0c72301c-c936-4522-8c47-92aafd9191ea" />



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

### Run with Compose (recommended)

```bash
cp .env.example .env          # set QRGATE_AUTH_KEY + passwords
docker compose -f docker-compose.single.yml up -d --build
# → http://localhost:8080  (redirects to /install on first run)
```

### Or with plain `docker run`

```bash
docker build -t qrgate .
docker run -d --name qrgate \
  -p 8080:80 \
  -e QRGATE_AUTH_KEY="$(openssl rand -hex 32)" \
  -e QRGATE_ADMIN_PASSWORD="change-me-admin" \
  -e QRGATE_SETUP_URL="http://localhost:8080/install" \
  -v qrgate_data:/app/backend/data \
  -v qrgate_codes:/app/backend/codes \
  qrgate
```

The container's entrypoint automatically derives the frontend's API key from `QRGATE_AUTH_KEY`, so you only set the secret once. All other `QRGATE_*` variables from [`.env.example`](.env.example) work here too. Then follow the [setup wizard](#first-run-setup-wizard).

## Pterodactyl Panel (Egg)

QrGate can run on a [Pterodactyl](https://pterodactyl.io/) game-panel as a single server. Because Pterodactyl runs containers **rootless** (user `container`, working dir `/home/container`) and assigns the web port via `${SERVER_PORT}`, it needs a Pterodactyl-tailored image — [`Dockerfile.pterodactyl`](Dockerfile.pterodactyl) — not the plain all-in-one image.

### 1. Build & push the Pterodactyl image

```bash
docker build -f Dockerfile.pterodactyl -t redwolf2467/qrgate:pterodactyl .
docker push redwolf2467/qrgate:pterodactyl
```

This image listens on `${SERVER_PORT}`, writes data/tickets under `/home/container` (the persistent volume), and starts the backend, PHP-FPM and nginx via supervisor.

### 2. Import the egg

In the panel: **Admin → Nests → Import Egg**, upload [`pterodactyl/egg-qrgate.json`](pterodactyl/egg-qrgate.json). It pre-fills everything below.

### What the egg fields mean (if you fill them by hand)

| Panel field | Value |
|-------------|-------|
| **Name** | `QrGate` |
| **Startup Command** | `/usr/local/bin/qrgate-ptero.sh` |
| **Docker Images** | `redwolf2467/qrgate:pterodactyl` (format `Display Name|image:tag`) |
| **Stop Command** | `^C` |
| **Startup Configuration** (done-regex) | `{ "done": "QrGate backend server started" }` |
| **Configuration Files** | `{}` |
| **Install script** | container `alpine:3.19`, entrypoint `ash`, `mkdir -p /mnt/server/data /mnt/server/codes` |

### 3. Variables to set on the server

| Variable | Required | Purpose |
|----------|----------|---------|
| `QRGATE_AUTH_KEY` | ✅ | shared secret (backend auth_key + frontend API key). `openssl rand -hex 32` |
| `QRGATE_ADMIN_PASSWORD` | optional | admin password (also settable in the wizard) |
| `QRGATE_ORIGIN_URL`, `QRGATE_FRONTEND_ORIGIN` | optional | public URL / CORS origin |
| `QRGATE_SMTP_*` | optional | mail settings (also settable in the wizard) |

Pterodactyl injects `SERVER_PORT` and `SERVER_IP` automatically; the entrypoint renders nginx for that port and prints the wizard link in the console. Assign **one port allocation** (the web port) — the backend stays internal on `127.0.0.1:1654`. After the server starts, open the printed `/install` link and run the [setup wizard](#first-run-setup-wizard).

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
