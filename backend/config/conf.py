import datetime as dt
import os


def _env(name, default):
    """Read an env var, falling back to the hardcoded default.

    Lets the same conf.py work both for a bare `python main.py` run and for the
    Docker image, where secrets/URLs are injected via the environment (see
    docker-compose.yml / .env). Empty strings are treated as unset.
    """
    val = os.environ.get(name)
    return val if val not in (None, "") else default


def _env_list(name, default):
    """Comma-separated env var -> list, falling back to `default`."""
    val = os.environ.get(name)
    if val in (None, ""):
        return default
    return [item.strip() for item in val.split(",") if item.strip()]


version = 1.0

# Local timezone (IANA name). DST-aware — preferred. Used by assets/timeutil.
timezone = _env("QRGATE_TIMEZONE", "Europe/Berlin")
# Fallback only, used if `timezone` is unset/unavailable. Does NOT handle DST.
utc_offset = 1

class API:
    port = int(_env("QRGATE_PORT", 1654))
    backend_url = _env("QRGATE_BACKEND_URL", "https://qrgate-backend.example.com/") # The adress (url or ip) where your backend server is reachable.
    # Allowed CORS origin for direct browser->backend requests. MUST be locked
    # to your real frontend URL in production (e.g. "https://tickets.example.com").
    # Do NOT use "*" in production. PLACEHOLDER — set per deployment.
    frontend_origin = _env("QRGATE_FRONTEND_ORIGIN", "https://qrgate-frontend.example.com")


class Auth:
    auth_key = _env("QRGATE_AUTH_KEY", "YourGeneratedKeyHere")

    # ------------------------------------------------------------------ #
    # Per-deployment login accounts for /api/user/check/.
    # PLACEHOLDERS ONLY — these MUST be set per deployment and the passwords
    # MUST be rotated to real secrets before going live. Each role has a list
    # of allowed usernames and a single shared password for that role.
    # ------------------------------------------------------------------ #
    ticketflow_usernames = _env_list("QRGATE_TICKETFLOW_USERNAMES", ["ticketflow"])   # set real box-office usernames
    ticketflow_password = _env("QRGATE_TICKETFLOW_PASSWORD", "CHANGE_ME_ticketflow")   # ROTATE before deployment

    handheld_usernames = _env_list("QRGATE_HANDHELD_USERNAMES", ["handheld"])       # set real scanner usernames
    handheld_password = _env("QRGATE_HANDHELD_PASSWORD", "CHANGE_ME_handheld")        # ROTATE before deployment

    admin_usernames = _env_list("QRGATE_ADMIN_USERNAMES", ["admin"])             # set real admin usernames
    admin_password = _env("QRGATE_ADMIN_PASSWORD", "CHANGE_ME_admin")              # ROTATE before deployment

class Mail:
    smtp_server = _env("QRGATE_SMTP_SERVER", "smtp.example.com")
    smtp_port = int(_env("QRGATE_SMTP_PORT", 587))
    smtp_user = _env("QRGATE_SMTP_USER", "user@example.com")
    smtp_password = _env("QRGATE_SMTP_PASSWORD", "smtp_password")
    mail_title = "Your QrGate Ticket - {id}"
    mail_title_paid = "Your Ticket has been paid - {id}"
    mail_paid_title = "Ticket paid"
    