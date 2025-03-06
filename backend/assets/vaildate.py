import quart
import datetime
import config.conf as config
from typing import Dict, Optional
from assets.data import load_tickets, save_tickets, load_ticket_id
from reds_simple_logger import Logger

logger = Logger()
logger.success("Validate.py loaded")


def validate_ticket(app: quart.Quart):
    @app.route("/api/ticket/validate", methods=["POST", "GET"])
    async def validate_ticket():

        time = datetime.datetime.now(
            tz=datetime.timezone(datetime.timedelta(hours=config.utc_offset))
        )

        auth_key = quart.request.headers.get("Authorization")
        if config.Auth.auth_key != auth_key:
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401

        try:
            data: Dict = await quart.request.get_json()
            ticket_id: Optional[str] = data.get("tid")

            if not ticket_id:

                return (
                    quart.jsonify(
                        {"status": "error", "message": "Ticket ID is required"}
                    ),
                    400,
                )

            ticket: Dict = load_ticket_id(ticket_id)
            app.logger.debug(f"Loaded ticket: {ticket}")

            if not ticket:
                logger.debug.info(f"Ticket not found: {ticket_id}")
                temp_data = {
                    "tid": "Unknown",
                    "first_name": "Unknown",
                    "last_name": "Unknown",
                    "paid": "Unknown",
                    "valid_date": "Unknown",
                    "type": "Unknown",
                    "valid": "Unknown",
                    "used_at": "Unknown",
                    "access_attempts": [],
                }
                return (
                    quart.jsonify(
                        {
                            "status": "error",
                            "message": "Ticket not found",
                            "data": temp_data,
                        }
                    ),
                    200,
                )

            if ticket.get("type") == "admin":
                ticket["access_attempts"].append(
                    {
                        "status": "success",
                        "type": "valid - ADMIN ACCESS",
                        "time": str(time.strftime("%Y.%m.%d - %H:%M:%S")),
                    }
                )
                ticket["used_at"] = str(time.strftime("%Y.%m.%d - %H:%M:%S"))
                save_tickets(ticket_id, ticket)
                return (
                    quart.jsonify(
                        {
                            "status": "success",
                            "message": "Ticket is valid - ADMIN",
                            "data": ticket,
                        }
                    ),
                    200,
                )

            if ticket.get("type") == "vip":
                ticket["access_attempts"].append(
                    {
                        "status": "success",
                        "type": "valid",
                        "time": str(time.strftime("%Y.%m.%d - %H:%M:%S")),
                    }
                )
                ticket["used_at"] = str(time.strftime("%Y.%m.%d - %H:%M:%S"))
                save_tickets(ticket_id, ticket)
                return (
                    quart.jsonify(
                        {
                            "status": "success",
                            "message": "Ticket is valid - VIP",
                            "data": ticket,
                        }
                    ),
                    200,
                )

            if not ticket.get("paid", False):
                logger.debug.info(f"Ticket is not paid: {ticket_id}")
                ticket["access_attempts"].append(
                    {
                        "status": "error",
                        "type": "not_paid",
                        "time": str(time.strftime("%Y.%m.%d - %H:%M:%S")),
                    }
                )
                save_tickets(ticket_id, ticket)
                return (
                    quart.jsonify(
                        {
                            "status": "error",
                            "message": "Ticket is not paid",
                            "data": ticket,
                        }
                    ),
                    200,
                )

            if not ticket.get("valid", False):
                logger.debug.info(f"Ticket is not valid: {ticket_id}")
                ticket["access_attempts"].append(
                    {
                        "status": "error",
                        "type": "already_used",
                        "time": str(time.strftime("%Y.%m.%d - %H:%M:%S")),
                    }
                )
                save_tickets(ticket_id, ticket)
                return (
                    quart.jsonify(
                        {
                            "status": "error",
                            "message": "Ticket already used",
                            "data": ticket,
                        }
                    ),
                    200,
                )

            valid_date = ticket.get("valid_date")

            if valid_date != str(time.date().isoformat()):
                ticket["access_attempts"].append(
                    {
                        "status": "error",
                        "type": "invalid_date",
                        "time": str(time.strftime("%Y.%m.%d - %H:%M:%S")),
                    }
                )
                save_tickets(ticket_id, ticket)
                logger.debug.info(f"Ticket is not valid today: {ticket_id}")
                return (
                    quart.jsonify(
                        {
                            "status": "error",
                            "message": "Ticket is not valid today",
                            "data": ticket,
                        }
                    ),
                    200,
                )

            ticket["valid"] = False
            ticket["used_at"] = str(time.strftime("%Y.%m.%d - %H:%M:%S"))
            ticket["access_attempts"].append(
                {
                    "status": "success",
                    "type": "valid",
                    "time": str(time.strftime("%Y.%m.%d - %H:%M:%S")),
                }
            )
            save_tickets(ticket_id, ticket)
            ticket: Dict = load_ticket_id(ticket_id)
            logger.info(f"Ticket validated: {ticket_id}")
            return (
                quart.jsonify(
                    {"status": "success", "message": "Ticket is valid", "data": ticket}
                ),
                200,
            )

        except Exception as e:
            app.logger.error(f"Error validating ticket: {e}")
            return (
                quart.jsonify({"status": "error", "message": "Internal server error"}),
                500,
            )
