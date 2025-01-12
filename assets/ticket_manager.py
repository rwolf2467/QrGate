import quart
import config.conf as config
from assets.data import load_tickets, save_tickets, load_ticket_id
from assets.data import load_date, save_date
import os
import datetime as dt

def create_ticket(app=quart.Quart):
    @app.route('/api/ticket/create', methods=['POST'])
    async def create_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get('Authorization')):
            return quart.jsonify({'status': 'error', 'message': 'Unauthorized'}), 401
        try:
            data: dict = await quart.request.get_json()
            tid: str = os.urandom(16).hex()
            paid: bool = data.get('paid', False)
            valid_date: str = data.get('valid_date')  # Format: YYYY-MM-DD
            first_name: str = data.get("first_name")
            last_name: str = data.get("last_name")
            method: str = data.get("method")
            seats: int = data.get("seats")

            date = load_date(valid_date)
            if not date:
                return quart.jsonify({'status': 'error', 'message': 'Invalid date provided'}), 400

            seats_available = int(date["seats_available"])
            if seats > seats_available:
                return quart.jsonify({'status': 'error', 'message': 'Not enough seats available'}), 400

            date["seats_available"] -= seats
            save_date(valid_date, date)

            ticket = {
                "tid": tid,
                "first_name": first_name,
                "last_name": last_name,
                "paid": paid,
                "valid_date": valid_date,
                "method": method,
                "seats": seats,
                "valid": paid,
                "used_at": None
            }
            save_tickets(tid, ticket)

            return quart.jsonify({'status': 'success', 'message': 'Ticket created', 'tid': tid}), 200
        except Exception as e:
            print("Error:", str(e))
            return quart.jsonify({'status': 'error', 'message': str(e)}), 500


        
def edit_ticket(app = quart.Quart):
    @app.route('/api/ticket/edit', methods=['POST'])
    async def edit_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get('Authorization')):
            return quart.jsonify({'status': 'error', 'message': 'Unauthorized'}), 401
        try:
            data: dict = await quart.request.get_json()
            tid:str = data.get('tid')
            ticket = load_ticket_id(tid)

            if tid == None:
                return quart.jsonify({'status': 'error', 'message': 'Ticket not found'}), 404

            paid: bool = True if data.get('paid') else ticket.paid
            valid: bool = True if data.get('paid') else ticket.valid
            valid_date: str = data.get('valid_date') if data.get('valid_date') else ticket.valid_date
            first_name: str = data.get("first_name") if data.get("first_name") else ticket.first_name
            last_name: str = data.get("last_name") if data.get("last_name") else ticket.last_name
            seats: int = data.get("seats") if data.get("seats") else ticket.seats

            ticket["paid"] = paid
            ticket["valid_date"] = valid_date
            ticket["first_name"] = first_name
            ticket["last_name"] = last_name
            ticket["valid"] = valid
            ticket["seats"] = seats
            save_tickets(tid, ticket)
            return quart.jsonify({'status': 'success', 'message': 'Ticket edited'}), 200
        except Exception as e:
            return quart.jsonify({'status': 'error', 'message': str(e)}), 500
        
def view_ticket(app = quart.Quart):
    @app.route("/api/ticket/get", methods=["GET", 'POST'])
    async def view_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get('Authorization')):
            return quart.jsonify({'status': 'error', 'message': 'Unauthorized'}), 401
        try:
            data: dict = await quart.request.get_json()
            tid:str = data.get('tid')

            ticket = load_ticket_id(tid)
            return quart.jsonify({'status': 'success', 'message': 'Ticket loaded', "data": ticket}), 200
        except Exception as e:
            return quart.jsonify({'status': 'error', 'message': str(e)}), 500