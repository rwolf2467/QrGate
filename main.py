import quart
from quart import Response
from assets.vaildate import validate_ticket
from assets.ticket_manager import create_ticket, edit_ticket, view_ticket
from assets.manage_show import get_show, edit_show
from config import conf as config
import datetime as dt
import logging
import subprocess as sp
import asyncio, subprocess

app = quart.Quart(__name__)

validate_ticket(app)
create_ticket(app)
edit_ticket(app)
view_ticket(app)
get_show(app)
edit_show(app)

print(str(dt.datetime.now().date()))
if __name__ == "__main__":
    app.run(debug=True, port=config.API.port, host="0.0.0.0", use_reloader=True)
