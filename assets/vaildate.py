import quart
import datetime as dt
import config.conf as config
from assets.data import load_tickets, save_tickets


def validate_ticket(app: quart.Quart):
    @app.route(f"/api/ticket/vaildate", methods=["POST", "GET"])
    async def validate_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            tickets: dict = load_tickets()
            data: dict = await quart.request.get_json()
            tid: str = data.get("tid")  # ? Ticket ID

            if tid in tickets:
                if bool(tickets[tid]["valid"]):
                    if tickets[tid]["valid_date"] == str(
                        dt.datetime.now().date()
                    ):  # ? JJJJ-MM-DD
                        tickets[tid]["valid"] = False
                        tickets[tid]["used_at"] = str(dt.datetime.now())
                        save_tickets(tickets)
                        return (
                            quart.jsonify(
                                {"status": "success", "message": "Ticket is valid"}
                            ),
                            200,
                        )
                    else:
                        return (
                            quart.jsonify(
                                {
                                    "status": "error",
                                    "message": "Ticket is not valid today.",
                                }
                            ),
                            400,
                        )
                else:
                    return (
                        quart.jsonify(
                            {"status": "error", "message": "Ticket is already used."}
                        ),
                        400,
                    )
            else:
                return (
                    quart.jsonify({"status": "error", "message": "Ticket not found."}),
                    404,
                )
        except Exception as e:
            return quart.jsonify({"status": "error", "message": str(e)}), 500
