"""First-run setup wizard backend.

On a fresh install the operator is sent to the frontend `/install` page, which
drives a short wizard (SMTP credentials, the first event, the admin password)
and POSTs the result to `/api/setup/complete` here.

State lives in a tiny key/value `settings` table:

  installed   -> "1" once the wizard has been completed
  smtp_*      -> runtime SMTP overrides (pushed into config.Mail live)

The SMTP settings are applied on top of `config.Mail` at startup and whenever
the wizard saves them, so e-mail sending picks them up without a restart and
without editing conf.py.

Security model: `/api/setup/complete` is only open while the system is NOT yet
installed. Once installed it is locked and requires the API auth_key (same
trust model as the rest of the admin API). This prevents a stranger from
re-running setup against a live system.
"""

import asyncio
import hmac
import json
import os
import secrets
import smtplib
import threading

import quart
from werkzeug.security import generate_password_hash

import config.conf as config
from assets.data import get_db, load_show, save_show
from assets.timeutil import local_now
from reds_simple_logger import Logger

logger = Logger()
logger.success("Setup.py loaded")

# Setting keys we treat as live SMTP overrides for config.Mail.
_SMTP_KEYS = {
    "smtp_server": "smtp_server",
    "smtp_port": "smtp_port",
    "smtp_user": "smtp_user",
    "smtp_password": "smtp_password",
}


# --------------------------------------------------------------------------- #
# settings table helpers
# --------------------------------------------------------------------------- #
def init_setup() -> None:
    """Create the settings table idempotently."""
    conn = get_db()
    try:
        conn.execute(
            """
            CREATE TABLE IF NOT EXISTS settings (
                key   TEXT PRIMARY KEY,
                value TEXT
            )
            """
        )
        conn.commit()
    finally:
        conn.close()


def get_setting(key: str, default=None):
    conn = get_db()
    try:
        row = conn.execute(
            "SELECT value FROM settings WHERE key = ?", (key,)
        ).fetchone()
    finally:
        conn.close()
    return row["value"] if row is not None else default


def set_setting(key: str, value) -> None:
    conn = get_db()
    try:
        conn.execute(
            """
            INSERT INTO settings (key, value) VALUES (?, ?)
            ON CONFLICT(key) DO UPDATE SET value = excluded.value
            """,
            (key, None if value is None else str(value)),
        )
        conn.commit()
    finally:
        conn.close()


def is_installed() -> bool:
    return get_setting("installed") == "1"


def apply_settings_to_config() -> None:
    """Push any stored SMTP overrides onto config.Mail so ticket_manager picks
    them up. No-op for keys that were never set in the wizard."""
    server = get_setting("smtp_server")
    if server:
        config.Mail.smtp_server = server
    port = get_setting("smtp_port")
    if port:
        try:
            config.Mail.smtp_port = int(port)
        except (TypeError, ValueError):
            pass
    user = get_setting("smtp_user")
    if user:
        config.Mail.smtp_user = user
    pw = get_setting("smtp_password")
    if pw:
        config.Mail.smtp_password = pw


def _authorized() -> bool:
    key = quart.request.headers.get("Authorization")
    if not key:
        return False
    return hmac.compare_digest(str(key), str(config.Auth.auth_key))


def write_auth_key(new_key: str) -> None:
    """Persist a new API secret to the shared key file (read by both backend and
    the PHP frontend) and update the live in-process value immediately."""
    path = config.key_file
    parent = os.path.dirname(path)
    if parent:
        os.makedirs(parent, exist_ok=True)
    with open(path, "w") as fh:
        fh.write(new_key.strip() + "\n")
    try:
        os.chmod(path, 0o600)
    except OSError:
        pass
    config.Auth.auth_key = new_key.strip()


def _schedule_restart() -> None:
    """Hard-exit the backend shortly after responding so the supervisor restarts
    it cleanly with the new secret. Only meaningful when running under a process
    supervisor (the Docker images set QRGATE_SUPERVISED=1); a bare dev run would
    just stop, so we skip it there (the key is already applied live anyway)."""
    if os.environ.get("QRGATE_SUPERVISED") not in ("1", "true", "True"):
        return

    def _bye():
        logger.warn("Restarting backend to apply new configuration...")
        os._exit(0)

    threading.Timer(1.5, _bye).start()


def _set_admin_password(new_password: str) -> None:
    """Set the password of the seeded admin account (first admin username) and
    clear its forced-change flag. Falls back to creating the account if missing."""
    username = (config.Auth.admin_usernames or ["admin"])[0]
    pw_hash = generate_password_hash(new_password)
    conn = get_db()
    try:
        row = conn.execute(
            "SELECT id FROM users WHERE can_admin = 1 ORDER BY id LIMIT 1"
        ).fetchone()
        if row is not None:
            conn.execute(
                "UPDATE users SET password_hash = ?, must_change_pw = 0 WHERE id = ?",
                (pw_hash, row["id"]),
            )
        else:
            conn.execute(
                """
                INSERT INTO users (
                    username, password_hash, can_admin, can_ticketflow,
                    can_handheld, must_change_pw, created_at
                ) VALUES (?, ?, 1, 1, 1, 0, ?)
                """,
                (username, pw_hash, str(local_now())),
            )
        conn.commit()
    finally:
        conn.close()


# --------------------------------------------------------------------------- #
# routes
# --------------------------------------------------------------------------- #
def setup_routes(app=quart.Quart):
    @app.route("/api/setup/status", methods=["GET"])
    async def setup_status():
        # Public, unauthenticated: the frontend guard checks this on every
        # request to decide whether to send visitors to /install.
        return quart.jsonify({"status": "success", "installed": is_installed()}), 200

    @app.route("/api/setup/complete", methods=["POST"])
    async def setup_complete():
        # Open only while not yet installed; locked behind auth_key afterwards.
        if is_installed() and not _authorized():
            return (
                quart.jsonify(
                    {"status": "error", "message": "Already installed"}
                ),
                403,
            )

        try:
            data: dict = await quart.request.get_json(force=True) or {}
        except Exception:
            return (
                quart.jsonify({"status": "error", "message": "Invalid JSON"}),
                400,
            )

        smtp = data.get("smtp") or {}
        event = data.get("event") or {}
        admin = data.get("admin") or {}
        security = data.get("security") or {}

        # --- SMTP ---------------------------------------------------------- #
        if smtp.get("server"):
            set_setting("smtp_server", smtp.get("server"))
        if smtp.get("port"):
            set_setting("smtp_port", smtp.get("port"))
        if smtp.get("user"):
            set_setting("smtp_user", smtp.get("user"))
        if smtp.get("password"):
            set_setting("smtp_password", smtp.get("password"))
        apply_settings_to_config()

        # --- First event --------------------------------------------------- #
        # Build (or update) the singleton show. Keep the store locked so the
        # operator opens sales deliberately from the admin dashboard.
        title = (event.get("title") or "").strip()
        if title:
            show = load_show()
            show["orga_name"] = (event.get("orga_name") or show.get("orga_name") or "").strip()
            show["title"] = title
            show["subtitle"] = (event.get("subtitle") or show.get("subtitle") or "").strip()
            show.setdefault("payment_methods", event.get("payment_methods") or "both")
            show.setdefault("store_lock", True)
            show.setdefault("votes", {"count": 0, "value": 0, "average": 0})

            # Optional first date.
            date_val = (event.get("date") or "").strip()
            if date_val:
                try:
                    tickets = int(event.get("tickets") or 0)
                except (TypeError, ValueError):
                    tickets = 0
                dates = show.get("dates") or {}
                # Next free numeric key.
                next_key = str(max([int(k) for k in dates.keys() if str(k).isdigit()] or [0]) + 1)
                dates[next_key] = {
                    "date": date_val,
                    "time": (event.get("time") or "").strip(),
                    "tickets": tickets,
                    "tickets_available": tickets,
                    "price": str(event.get("price") or "0"),
                }
                show["dates"] = dates
            save_show(show)

        # --- Admin password ----------------------------------------------- #
        new_pw = admin.get("password")
        if new_pw and len(str(new_pw)) >= 8:
            _set_admin_password(str(new_pw))

        # --- Security key -------------------------------------------------- #
        # If the operator generated a new API secret in the wizard, persist it
        # to the shared key file. This requires a backend restart so callers
        # consistently pick up the new value; the frontend reads the same file
        # and reconnects automatically.
        # Only rotate the key when running supervised in a single container,
        # where backend and frontend share the key file. In a split deployment
        # the frontend can't read this file, so changing it here would break
        # auth; there the secret must come from QRGATE_AUTH_KEY instead.
        restart = False
        supervised = os.environ.get("QRGATE_SUPERVISED") in ("1", "true", "True")
        new_key = (security.get("key") or "").strip()
        if supervised and new_key and len(new_key) >= 16 and new_key != config.Auth.auth_key:
            write_auth_key(new_key)
            restart = True

        set_setting("installed", "1")
        set_setting("installed_at", str(local_now()))
        logger.success("Setup wizard completed — system marked as installed.")

        resp = quart.jsonify(
            {"status": "success", "message": "Setup complete", "restart": restart}
        )
        if restart:
            _schedule_restart()
        return resp, 200

    @app.route("/api/setup/genkey", methods=["GET"])
    async def setup_genkey():
        # Helper for the wizard's "regenerate" button when client-side crypto
        # is unavailable. Public while not installed.
        if is_installed() and not _authorized():
            return quart.jsonify({"status": "error", "message": "Locked"}), 403
        return quart.jsonify({"status": "success", "key": secrets.token_hex(32)}), 200

    @app.route("/api/setup/test-mail", methods=["POST"])
    async def setup_test_mail():
        # Verify the SMTP credentials actually connect + authenticate, so the
        # operator finds out in the wizard rather than when the first ticket
        # silently fails to send. Open while not installed; locked afterwards.
        if is_installed() and not _authorized():
            return quart.jsonify({"status": "error", "message": "Locked"}), 403
        try:
            data = await quart.request.get_json(force=True) or {}
        except Exception:
            data = {}
        smtp = data.get("smtp") or {}
        server = (smtp.get("server") or "").strip()
        if not server:
            return (
                quart.jsonify({"status": "error", "ok": False, "message": "No SMTP server given"}),
                400,
            )
        try:
            port = int(smtp.get("port") or 587)
        except (TypeError, ValueError):
            port = 587
        ok, message = await asyncio.to_thread(
            _smtp_test, server, port, smtp.get("user") or "", smtp.get("password") or ""
        )
        return (
            quart.jsonify({"status": "success", "ok": ok, "message": message}),
            200,
        )


def _smtp_test(server: str, port: int, user: str, password: str):
    """Blocking SMTP connect + (optional) STARTTLS + login. Returns (ok, msg)."""
    try:
        with smtplib.SMTP(server, port, timeout=10) as s:
            s.ehlo()
            if s.has_extn("starttls"):
                s.starttls()
                s.ehlo()
            if user:
                s.login(user, password)
        return True, "Connection and login successful."
    except smtplib.SMTPAuthenticationError:
        return False, "Authentication failed — check username and password."
    except Exception as e:  # noqa: BLE001 - surface any connect/TLS error to the user
        return False, "Connection failed: " + str(e)
