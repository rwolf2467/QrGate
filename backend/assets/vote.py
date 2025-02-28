import quart
from data import *
import config.conf as config


def vote(app: quart.Quart):
    @app.route("/api/vote", methods=["POST"])
    async def vote():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        data = await quart.request.json
        show = load_show()
        vote_data = show.get("votes")
        new_value: int = int(data.get("value")) + 1
        new_count: int = vote_data.get("count") + 1
        vote_data["count"] = new_count
        vote_data["value"] = new_value
        vote_data["average"] = new_value / new_count
        save_show(show)
        return quart.jsonify({"status": "success", "message": "Vote received"}), 200
