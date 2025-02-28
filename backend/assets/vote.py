import quart
from assets.data import load_show, save_show
import config.conf as config


def vote(app: quart.Quart):
    @app.route("/api/vote", methods=["POST"])
    async def vote():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        data = await quart.request.json
        show = load_show()
        vote_data = show.get("votes")
        new_value: int = int(data.get("value"))
        new_count: int = vote_data.get("count") + 1
        vote_data["count"] = new_count
        vote_data["value"] = vote_data.get("value", 0) + new_value
        vote_data["average"] = round(vote_data["value"] / new_count, 1)
        comment = data.get("comment", "")
        if comment:
            if "comments" not in vote_data:
                vote_data["comments"] = []
            vote_data["comments"].append(str(comment))
        
        save_show(show)
        return quart.jsonify({"status": "success", "message": "Vote received"}), 200
