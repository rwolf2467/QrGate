import quart
import config.conf as config
from assets.data import load_show, save_show

def edit_show(app = quart.Quart):
    @app.route("/api/show/edit", methods=['POST'])
    async def edit_show():
        if config.Auth.auth_key != (key := quart.request.headers.get('Authorization')):
            return quart.jsonify({'status': 'error', 'message': 'Unauthorized'}), 401
        try:
            data: dict = await quart.request.get_json()
            show: dict = load_show()

            orga_name: str = data.get("orga_name") if data.get("orga_name") else show.get("orga_name")
            title: str = data.get("title") if data.get("title") else show.get("title")
            dates: dict = data.get("dates") if data.get("dates") else show.get("dates")
            banner: str = data.get("banner") if data.get("banner") else show.get("banner")

            show["orga_name"] = orga_name
            show["banner"] = banner
            show["title"] = title
            show["dates"] = dates

            save_show(show)
            return quart.jsonify({'status': 'success', 'message': 'Show config saved'}), 200
        except Exception as e:
            return quart.jsonify({'status': 'error', 'message': str(e)}), 500
        
def get_show(app = quart.Quart):
    @app.route("/api/show/get", methods=['POST', "GET"])
    async def get_show():
        if config.Auth.auth_key != (key := quart.request.headers.get('Authorization')):
            return quart.jsonify({'status': 'error', 'message': 'Unauthorized'}), 401
        try:
            show: dict = load_show()

            return show, 200
        except Exception as e:
            return quart.jsonify({'status': 'error', 'message': str(e)}), 500