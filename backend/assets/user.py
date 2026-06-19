import quart
import datetime
import hmac
import config.conf as config


def _check_password(supplied, expected) -> bool:
    """Timing-safe password comparison; tolerates None/non-str input."""
    if supplied is None or expected is None:
        return False
    return hmac.compare_digest(str(supplied), str(expected))


def user_check(app: quart.Quart):
    @app.route("/api/user/check/", methods=["GET", "POST"])
    async def ticketflow_check():
        key = quart.request.headers.get("Authorization")
        if not key or not hmac.compare_digest(str(key), str(config.Auth.auth_key)):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            data = await quart.request.get_json(silent=True) or {}
            username = data.get("username")
            password = data.get("password")
            if (
                username in config.Auth.ticketflow_usernames
                and _check_password(password, config.Auth.ticketflow_password)
            ):
                print(f"Ticketflow user {username} authenticated")
                return quart.jsonify({"status": "success", "username": username, "message": "user authenticated"}), 200
            elif (
                username in config.Auth.handheld_usernames
                and _check_password(password, config.Auth.handheld_password)
            ):
                print(f"Handheld user {username} authenticated")
                return quart.jsonify({"status": "success", "username": username, "message": "user authenticated"}), 200
            elif (
                username in config.Auth.admin_usernames
                and _check_password(password, config.Auth.admin_password)
            ):
                print(f"Admin user {username} authenticated")
                return quart.jsonify({"status": "success", "username": username, "message": "user authenticated"}), 200
            else:
                print(f"User {username} authentication failed")
                return quart.jsonify({"status": "error", "message": "Authentication failed" }), 200
        except Exception:
            # Never leak config/internal details (e.g. a future missing-config
            # AttributeError) to the client; return a generic 500.
            return quart.jsonify({"status": "error", "message": "Internal server error"}), 500
