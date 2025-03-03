import quart
import datetime
import config.conf as config


def user_check(app: quart.Quart):
    @app.route("/api/user/check/", methods=["GET", "POST"])
    async def ticketflow_check():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        data = await quart.request.get_json()
        username = data.get("username")
        password = data.get("password")
        print(username, password)
        if (
            username in config.Auth.ticketflow_usernames
            and password == config.Auth.ticketflow_password
        ):
            print(f"Ticketflow user {username} authenticated")
            return quart.jsonify({"status": "success", "username": username, "message": "user authenticated"}), 200
        elif (
            username in config.Auth.handheld_usernames
            and password == config.Auth.handheld_password
        ):
            print(f"Handheld user {username} authenticated")
            return quart.jsonify({"status": "success", "username": username, "message": "user authenticated"}), 200
        elif (
            username in config.Auth.admin_usernames
            and password == config.Auth.admin_password
        ):
            print(f"Admin user {username} authenticated")
            return quart.jsonify({"status": "success", "username": username, "message": "user authenticated"}), 200
        else:
            print(f"Ticketflow user {username} authentication failed")
            return quart.jsonify({"status": "error", "message": "Authentication failed" }), 200
