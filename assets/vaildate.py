import quart
import datetime as dt
import config.conf as config
from typing import Dict, Optional
from assets.data import load_tickets, save_tickets, load_ticket_id


def validate_ticket(app: quart.Quart):
    @app.route("/api/ticket/validate", methods=["POST", "GET"])
    async def validate_ticket():
        # Check authorization
        auth_key = quart.request.headers.get("Authorization")
        if config.Auth.auth_key != auth_key:
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401

        try:
            data: Dict = await quart.request.get_json()
            ticket_id: Optional[str] = data.get("tid")  # Ticket ID

            if not ticket_id:
                return (
                    quart.jsonify(
                        {"status": "error", "message": "Ticket ID is required"}
                    ),
                    400,
                )

            ticket: Dict = load_ticket_id(ticket_id)

            if not ticket:
                return (
                    quart.jsonify({"status": "error", "message": "Ticket not found"}),
                    404,
                )

            if not ticket.get("valid", False):
                return (
                    quart.jsonify(
                        {"status": "error", "message": "Ticket is not valid"}
                    ),
                    400,
                )

            if not ticket.get("paid", False):
                return (
                    quart.jsonify({"status": "error", "message": "Ticket is not paid"}),
                    400,
                )

            valid_date = ticket.get("valid_date")
            if valid_date != str(dt.datetime.now().date()):
                return (
                    quart.jsonify(
                        {"status": "error", "message": "Ticket is not valid today"}
                    ),
                    400,
                )

            ticket["valid"] = False
            ticket["used_at"] = str(dt.datetime.now())
            save_tickets(ticket)

            return (
                quart.jsonify({"status": "success", "message": "Ticket is valid", "data": load_ticket_id(ticket_id)}),
                200,
            )

        except Exception as e:
            app.logger.error(f"Error validating ticket: {e}")
            return (
                quart.jsonify({"status": "error", "message": "Internal server error"}),
                500,
            )
