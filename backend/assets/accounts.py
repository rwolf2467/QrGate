"""Managed user accounts.

Replaces the old shared role-passwords with per-person accounts stored in the
`users` table. Each account has its own password (werkzeug hash) and a set of
access grants (admin / ticketflow / handheld). A default `admin`/`admin` account
is seeded on first run with `must_change_pw` set so the operator is forced to
pick a real password right after setup.

All routes are protected by the shared API auth_key (same trust model as the
rest of the API): the account-management endpoints are only ever reached through
the admin proxy, which itself requires an authenticated admin PHP session.
"""

import quart
import hmac
import config.conf as config
from werkzeug.security import generate_password_hash, check_password_hash
from assets.data import get_db
from assets.timeutil import local_now
from assets.ratelimit import allow
from reds_simple_logger import Logger

logger = Logger()
logger.success("Accounts.py loaded")

# Default credentials seeded on first run. must_change_pw forces a change at
# first login. These password values are also treated as "still default" on
# login as a belt-and-suspenders trigger for the forced change.
DEFAULT_ADMIN_USER = "admin"
DEFAULT_ADMIN_PW = "admin"
_DEFAULT_PASSWORDS = {"admin", "admin123"}


def _authorized() -> bool:
    key = quart.request.headers.get("Authorization")
    if not key:
        return False
    return hmac.compare_digest(str(key), str(config.Auth.auth_key))


def init_accounts() -> None:
    """Create the users table and seed the default admin if there are none."""
    conn = get_db()
    try:
        conn.execute(
            """
            CREATE TABLE IF NOT EXISTS users (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                username       TEXT UNIQUE NOT NULL COLLATE NOCASE,
                password_hash  TEXT NOT NULL,
                can_admin      INTEGER DEFAULT 0,
                can_ticketflow INTEGER DEFAULT 0,
                can_handheld   INTEGER DEFAULT 0,
                must_change_pw INTEGER DEFAULT 0,
                created_at     TEXT
            )
            """
        )
        conn.commit()
        count = conn.execute("SELECT COUNT(*) AS c FROM users").fetchone()["c"]
        if count == 0:
            conn.execute(
                """
                INSERT INTO users (
                    username, password_hash, can_admin, can_ticketflow,
                    can_handheld, must_change_pw, created_at
                ) VALUES (?, ?, 1, 1, 1, 1, ?)
                """,
                (
                    DEFAULT_ADMIN_USER,
                    generate_password_hash(DEFAULT_ADMIN_PW),
                    local_now().isoformat(),
                ),
            )
            conn.commit()
            logger.info("Seeded default admin account (admin/admin, must change pw).")
    finally:
        conn.close()


# --------------------------------------------------------------------------- #
# Data access
# --------------------------------------------------------------------------- #
def _row_to_user(row, include_hash: bool = False) -> dict:
    u = {
        "username": row["username"],
        "can_admin": bool(row["can_admin"]),
        "can_ticketflow": bool(row["can_ticketflow"]),
        "can_handheld": bool(row["can_handheld"]),
        "must_change_pw": bool(row["must_change_pw"]),
        "created_at": row["created_at"],
    }
    if include_hash:
        u["password_hash"] = row["password_hash"]
    return u


def get_user(username: str):
    conn = get_db()
    try:
        row = conn.execute(
            "SELECT * FROM users WHERE username = ?", (username,)
        ).fetchone()
    finally:
        conn.close()
    return row


def list_users() -> list:
    conn = get_db()
    try:
        rows = conn.execute("SELECT * FROM users ORDER BY username").fetchall()
    finally:
        conn.close()
    return [_row_to_user(r) for r in rows]


def count_admins(exclude: str = None) -> int:
    conn = get_db()
    try:
        if exclude:
            row = conn.execute(
                "SELECT COUNT(*) AS c FROM users WHERE can_admin = 1 AND username <> ?",
                (exclude,),
            ).fetchone()
        else:
            row = conn.execute(
                "SELECT COUNT(*) AS c FROM users WHERE can_admin = 1"
            ).fetchone()
    finally:
        conn.close()
    return row["c"]


# --------------------------------------------------------------------------- #
# Routes
# --------------------------------------------------------------------------- #
def auth_routes(app: quart.Quart):
    @app.route("/api/auth/login", methods=["POST"])  # type: ignore
    async def auth_login():
        if not _authorized():
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        ip = quart.request.remote_addr or "unknown"
        if not allow(f"auth_login:{ip}", max_hits=10, window_seconds=300):
            return quart.jsonify({"status": "error", "message": "Too many attempts"}), 429

        data = await quart.request.get_json(silent=True) or {}
        username = str(data.get("username") or "").strip()
        password = str(data.get("password") or "")
        if not username or not password:
            return quart.jsonify({"status": "error", "message": "Missing credentials"}), 400

        row = get_user(username)
        if row is None or not check_password_hash(row["password_hash"], password):
            logger.info(f"Login failed for {username!r}")
            return quart.jsonify({"status": "error", "message": "Invalid credentials"}), 200

        # Force a change if the account is flagged OR an admin is still using a
        # well-known default password.
        must_change = bool(row["must_change_pw"]) or (
            bool(row["can_admin"]) and password in _DEFAULT_PASSWORDS
        )
        return (
            quart.jsonify(
                {
                    "status": "success",
                    "username": row["username"],
                    "permissions": {
                        "admin": bool(row["can_admin"]),
                        "ticketflow": bool(row["can_ticketflow"]),
                        "handheld": bool(row["can_handheld"]),
                    },
                    "must_change_pw": must_change,
                }
            ),
            200,
        )

    @app.route("/api/auth/change_password", methods=["POST"])  # type: ignore
    async def auth_change_password():
        if not _authorized():
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401

        data = await quart.request.get_json(silent=True) or {}
        username = str(data.get("username") or "").strip()
        old_password = str(data.get("old_password") or "")
        new_password = str(data.get("new_password") or "")

        if not username or not new_password:
            return quart.jsonify({"status": "error", "message": "Missing fields"}), 400
        if len(new_password) < 6:
            return quart.jsonify({"status": "error", "message": "Password too short (min 6)"}), 400

        row = get_user(username)
        if row is None or not check_password_hash(row["password_hash"], old_password):
            return quart.jsonify({"status": "error", "message": "Current password is wrong"}), 200

        conn = get_db()
        try:
            conn.execute(
                "UPDATE users SET password_hash = ?, must_change_pw = 0 WHERE username = ?",
                (generate_password_hash(new_password), username),
            )
            conn.commit()
        finally:
            conn.close()
        logger.info(f"Password changed for {username!r}")
        return quart.jsonify({"status": "success", "message": "Password updated"}), 200


def user_routes(app: quart.Quart):
    @app.route("/api/users/list", methods=["GET", "POST"])  # type: ignore
    async def users_list():
        if not _authorized():
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        return quart.jsonify({"status": "success", "users": list_users()}), 200

    @app.route("/api/users/create", methods=["POST"])  # type: ignore
    async def users_create():
        if not _authorized():
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        data = await quart.request.get_json(silent=True) or {}
        username = str(data.get("username") or "").strip()
        password = str(data.get("password") or "")
        if not username or not password:
            return quart.jsonify({"status": "error", "message": "Username and password required"}), 200
        if len(password) < 6:
            return quart.jsonify({"status": "error", "message": "Password too short (min 6)"}), 200
        if get_user(username) is not None:
            return quart.jsonify({"status": "error", "message": "Username already exists"}), 200

        conn = get_db()
        try:
            conn.execute(
                """
                INSERT INTO users (
                    username, password_hash, can_admin, can_ticketflow,
                    can_handheld, must_change_pw, created_at
                ) VALUES (?, ?, ?, ?, ?, 0, ?)
                """,
                (
                    username,
                    generate_password_hash(password),
                    1 if data.get("can_admin") else 0,
                    1 if data.get("can_ticketflow") else 0,
                    1 if data.get("can_handheld") else 0,
                    local_now().isoformat(),
                ),
            )
            conn.commit()
        finally:
            conn.close()
        logger.info(f"Created account {username!r}")
        return quart.jsonify({"status": "success", "message": "Account created"}), 200

    @app.route("/api/users/update", methods=["POST"])  # type: ignore
    async def users_update():
        if not _authorized():
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        data = await quart.request.get_json(silent=True) or {}
        username = str(data.get("username") or "").strip()
        row = get_user(username)
        if row is None:
            return quart.jsonify({"status": "error", "message": "Account not found"}), 200

        can_admin = 1 if data.get("can_admin") else 0
        # Don't let the last admin strip their own admin rights and lock everyone out.
        if not can_admin and bool(row["can_admin"]) and count_admins(exclude=username) == 0:
            return quart.jsonify({"status": "error", "message": "Cannot remove the last admin"}), 200

        conn = get_db()
        try:
            conn.execute(
                """
                UPDATE users SET can_admin = ?, can_ticketflow = ?, can_handheld = ?
                 WHERE username = ?
                """,
                (
                    can_admin,
                    1 if data.get("can_ticketflow") else 0,
                    1 if data.get("can_handheld") else 0,
                    username,
                ),
            )
            # Optional password reset by an admin.
            new_password = str(data.get("password") or "")
            if new_password:
                if len(new_password) < 6:
                    conn.rollback()
                    return quart.jsonify({"status": "error", "message": "Password too short (min 6)"}), 200
                conn.execute(
                    "UPDATE users SET password_hash = ?, must_change_pw = 1 WHERE username = ?",
                    (generate_password_hash(new_password), username),
                )
            conn.commit()
        finally:
            conn.close()
        return quart.jsonify({"status": "success", "message": "Account updated"}), 200

    @app.route("/api/users/delete", methods=["POST"])  # type: ignore
    async def users_delete():
        if not _authorized():
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        data = await quart.request.get_json(silent=True) or {}
        username = str(data.get("username") or "").strip()
        row = get_user(username)
        if row is None:
            return quart.jsonify({"status": "error", "message": "Account not found"}), 200
        # Never delete the last remaining admin.
        if bool(row["can_admin"]) and count_admins(exclude=username) == 0:
            return quart.jsonify({"status": "error", "message": "Cannot delete the last admin"}), 200

        conn = get_db()
        try:
            conn.execute("DELETE FROM users WHERE username = ?", (username,))
            conn.commit()
        finally:
            conn.close()
        logger.info(f"Deleted account {username!r}")
        return quart.jsonify({"status": "success", "message": "Account deleted"}), 200
