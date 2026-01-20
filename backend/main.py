from reds_simple_logger import Logger

logger = Logger()
logger.working("Starting up QrGate backend server...")

import quart
import quart_cors
from quart import Response
from assets.vaildate import validate_ticket
from assets.ticket_manager import create_ticket, edit_ticket, view_ticket
from assets.manage_show import get_show, edit_show
from assets.data import img_show
from assets.vote import vote
from assets.user import user_check
from assets.stats import get_stats_api
from assets.image_manager import upload_image, get_image, get_current_images
from config import conf as config
import datetime as dt

app = quart.Quart(__name__)
app = quart_cors.cors(app, allow_origin="*")

time = dt.datetime.now(tz=dt.timezone(dt.timedelta(hours=config.utc_offset)))

logger.working("Enabling systems...")
validate_ticket(app)
create_ticket(app)
edit_ticket(app)
view_ticket(app)
get_show(app)
edit_show(app)
img_show(app)
vote(app)
user_check(app)
get_stats_api(app)
upload_image(app)
get_image(app)
get_current_images(app)
logger.success("Systems enabled.")

qr_gate = """

       ________          ________        __          
       \_____  \_______ /  _____/_____ _/  |_  ____  
       /  / \  \_  __ /   \  ___\__  \\   ___/ __ \ 
       /   \_/.  |  | \\    \_\  \/ __ \|  | \  ___/ 
       \_____\ \_|__|   \______  (____  |__|  \___  >
              \__>             \/     \/          \/ 

       """
print(qr_gate)
logger.info("QrGate backend server started. - Developed by avocloud.net")

print(str(time.date()) + " - " + str(time.time()))
if __name__ == "__main__":
    app.run(debug=True, port=config.API.port, host="0.0.0.0", use_reloader=True)
