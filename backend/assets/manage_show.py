import quart
import config.conf as config
from assets.data import load_show, save_show
from reds_simple_logger import Logger
import os

logger = Logger()
logger.success("Manage_show.py loaded")


def edit_show(app=quart.Quart):
    @app.route("/api/show/edit", methods=["POST"])
    async def edit_show():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            data: dict = await quart.request.get_json()
            show: dict = load_show()

            orga_name: str = (
                data.get("orga_name")
                if data.get("orga_name")
                else show.get("orga_name")
            )
            title: str = data.get("title") if data.get("title") else show.get("title")
            dates: dict = data.get("dates") if data.get("dates") else show.get("dates")
            banner: str = (
                data.get("banner") if data.get("banner") else show.get("banner")
            )

            show["orga_name"] = orga_name
            show["banner"] = banner
            show["title"] = title
            show["subtitle"] = data.get("subtitle", show.get("subtitle"))
            show["dates"] = dates
            show["store_lock"] = bool(data.get("store_lock", show.get("store_lock")))
            show["payment_methods"] = data.get("payment_methods", show.get("payment_methods", "both"))

            save_show(show)
            return (
                quart.jsonify({"status": "success", "message": "Show config saved"}),
                200,
            )
        except Exception as e:
            return quart.jsonify({"status": "error", "message": str(e)}), 500


def get_show(app=quart.Quart):
    @app.route("/api/show/get", methods=["POST", "GET"])
    async def get_show():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            show: dict = load_show()

            return show, 200
        except Exception as e:
            return quart.jsonify({"status": "error", "message": str(e)}), 500

    @app.route("/api/show/get/logo", methods=["POST", "GET"])
    async def get_show_logo():
        try:

            logo_path = os.path.join(
                os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
                "data",
                "assets",
                "logo.png",
            )
            with open(logo_path, "rb") as f:
                image_data = f.read()
            response = quart.Response(image_data, mimetype="image/png")
            return response
        except Exception as e:
            return quart.jsonify({"status": "error", "message": str(e)}), 500

    @app.route("/api/show/get/wallpaper", methods=["POST", "GET"])
    async def get_show_wallpaper():
        try:
            wallpaper_path = os.path.join(
                os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
                "data",
                "assets",
                "wallpaper.png",
            )
            with open(wallpaper_path, "rb") as f:
                image_data = f.read()
            response = quart.Response(image_data, mimetype="image/png")
            return response
        except Exception as e:
            return quart.jsonify({"status": "error", "message": str(e)}), 500

    @app.route("/api/show/get/price", methods=["POST", "GET"])
    async def get_show_price():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            data: dict = await quart.request.get_json()
            date: str = data.get("date")

            show: dict = load_show()
            dates = show.get("dates")

            if not isinstance(dates, dict):
                return (
                    quart.jsonify(
                        {"status": "error", "message": "Invalid dates format"}
                    ),
                    400,
                )

            for key, value in dates.items():
                if value.get("date") == date:
                    price = value.get("price", "0")
                    return quart.jsonify({"status": "success", "price": price}), 200

            return quart.jsonify({"status": "error", "message": "Date not found"}), 404
        except Exception as e:
            return quart.jsonify({"status": "error", "message": str(e)}), 500

    @app.route("/api/show/get/payment_methods", methods=["POST", "GET"])
    async def get_payment_methods():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            show: dict = load_show()
            payment_methods = show.get("payment_methods", "both")
            return quart.jsonify({"status": "success", "payment_methods": payment_methods}), 200
        except Exception as e:
            return quart.jsonify({"status": "error", "message": str(e)}), 500
