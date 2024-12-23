import quart
import config.conf as config
from assets.data import load_tickets, save_tickets
import os
import datetime as dt

def create_ticket(app = quart.Quart):
    @app.route('/api/ticket/create', methods=['POST'])
    async def create_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get('Authorization')):
            return quart.jsonify({'status': 'error', 'message': 'Unauthorized'}), 401
        try:
            data: dict = await quart.request.get_json()
            tickets:dict = load_tickets()
            tid:str = os.urandom(16).hex()
            paid: bool = True if data.get('paid') == True else False
            valid_date: str = data.get('valid_date') #? JJJJ-MM-DD
            
            tickets[tid] = {
                "valid": paid,
                "valid_date": valid_date,
                "used_at": None,
                "created_at": str(dt.datetime.now())
            }
            save_tickets(tickets)
            return quart.jsonify({'status': 'success', 'message': 'Ticket created', 'tid': tid}), 200
        except Exception as e:
            return quart.jsonify({'status': 'error', 'message': str(e)}), 500
        
def edit_ticket(app = quart.Quart):
    @app.route('/api/ticket/edit', methods=['POST'])
    async def edit_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get('Authorization')):
            return quart.jsonify({'status': 'error', 'message': 'Unauthorized'}), 401
        try:
            data: dict = await quart.request.get_json()
            tickets:dict = load_tickets()
            tid:str = data.get('tid')

            if tid not in tickets:
                return quart.jsonify({'status': 'error', 'message': 'Ticket not found'}), 404

            paid: bool = True if data.get('paid') else tickets[tid]["tid"]["valid"]
            valid_date: str = data.get('valid_date') if data.get('valid_date') else tickets[tid]["valid_date"]
            
            if tid in tickets:
                tickets[tid]["valid"] = paid
                tickets[tid]["valid_date"] = valid_date
                save_tickets(tickets)
                return quart.jsonify({'status': 'success', 'message': 'Ticket edited'}), 200
            else:
                return quart.jsonify({'status': 'error', 'message': 'Ticket not found'}), 404
        except Exception as e:
            return quart.jsonify({'status': 'error', 'message': str(e)}), 500