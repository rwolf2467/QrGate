import quart
from assets.vaildate import validate_ticket
from assets.ticket_manager import create_ticket, edit_ticket
import datetime as dt

app = quart.Quart(__name__)

validate_ticket(app)
create_ticket(app)
edit_ticket(app)

print(str(dt.datetime.now().date()))
if __name__ == "__main__":
    app.run(debug=True, host="0.0.0.0", port=9191)