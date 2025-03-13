import quart
import config.conf as config
from assets.data import load_tickets, save_tickets, load_ticket_id
from assets.data import load_date, save_date, load_show
import os
import json

from reportlab.lib.pagesizes import letter
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import SimpleDocTemplate, Paragraph, Image, Spacer
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.mime.application import MIMEApplication
import qrcode, string, random
from reds_simple_logger import Logger
from datetime import datetime

logger = Logger()
logger.success("Ticket_manager.py loaded")


def create_ticket(app=quart.Quart):
    @app.route("/api/ticket/create", methods=["POST"])
    async def create_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            data: dict = await quart.request.get_json()
            print(data)

            paid: bool = data.get("paid", False)
            valid_date: str = data.get("valid_date")
            first_name: str = data.get("first_name")
            last_name: str = data.get("last_name")
            email: str = data.get("email")
            tickets: int = data.get("tickets")
            add_people: list = data.get("add_people", [])
            t_type: str = data.get("type") if data.get("type") else "visitor"
            date = load_date(valid_date)
            if not date:
                return (
                    quart.jsonify(
                        {"status": "error", "message": "Invalid date provided"}
                    ),
                    400,
                )

            tickets_available = int(date["tickets_available"])
            if tickets > tickets_available:
                return (
                    quart.jsonify(
                        {"status": "error", "message": "Not enough tickets available"}
                    ),
                    400,
                )

            date["tickets_available"] -= tickets
            save_date(valid_date, date)

            tid = generate_ticket_id(valid_date)
            ticket = {
                "tid": tid,
                "first_name": first_name,
                "last_name": last_name,
                "email": email,
                "paid": paid,
                "valid_date": valid_date,
                "type": t_type,
                "valid": paid,
                "used_at": None,
                "access_attempts": [],
            }
            save_tickets(tid, ticket)

            await send_email(first_name, last_name, email, tid, paid)

            for person in add_people:
                tid = generate_ticket_id(valid_date)
                ticket = {
                    "tid": tid,
                    "first_name": person,
                    "last_name": "",
                    "email": email,
                    "paid": paid,
                    "valid_date": valid_date,
                    "type": t_type,
                    "valid": paid,
                    "used_at": None,
                    "access_attempts": [],
                }
                save_tickets(tid, ticket)

                await send_email(person, "", email, tid, paid)

            return (
                quart.jsonify(
                    {"status": "success", "message": "Tickets created", "tid": tid}
                ),
                200,
            )
        except Exception as e:
            print("Error:", str(e))
            return quart.jsonify({"status": "error", "message": str(e)}), 500

    @app.route("/api/ticketflow/create", methods=["POST"])
    async def create_ticket_flow():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401

        data: dict = await quart.request.get_json()
        print(data)

        paid: bool = data.get("paid", False)
        valid_date: str = data.get("valid_date")
        t_type: str = data.get("type", "not provided")
        ticketId: str = str(data.get("tid"))

        first_name: str = data.get("first_name", "not provided")
        last_name: str = data.get("last_name", "not provided")
        email: str = data.get("email", "not provided")
        tickets: int = data.get("tickets", 0)
        logger.debug.info(valid_date)
        if not valid_date or str(valid_date) == "":
            if t_type != "admin" and t_type != "vip":
                return (
                    quart.jsonify(
                        {"status": "error", "message": "Valid date is required for this ticket type"}
                    ),
                    400,
                )
            valid_date = "Unlimited"
        else:
            date = load_date(valid_date)
            if t_type != "admin" and t_type != "vip":
                if not date:
                    return (
                        quart.jsonify(
                            {"status": "error", "message": "Invalid date provided"}
                        ),
                        400,
                    )

                tickets_available = int(date["tickets_available"])
                if tickets > tickets_available:
                    return (
                        quart.jsonify(
                            {"status": "error", "message": "Not enough tickets available"}
                        ),
                        400,
                    )

                date["tickets_available"] -= tickets
                save_date(valid_date, date)

        if not ticketId or ticketId == "":
            tid = generate_ticket_id(valid_date)
        else:
            tid = ticketId
        ticket = {
            "tid": tid,
            "first_name": first_name,
            "last_name": last_name,
            "email": email if email != "not provided" else None,
            "paid": paid,
            "valid_date": valid_date,
            "type": t_type,
            "valid": paid,
            "used_at": None,
            "access_attempts": [],
        }
        save_tickets(tid, ticket)

        if email and email != "not provided":
            await send_email(first_name, last_name, email, tid, paid)

        return (
            quart.jsonify(
                {"status": "success", "message": "Tickets created", "tid": tid}
            ),
            200,
        )


async def send_email(first_name, last_name, email, tid, paid, type="normal"):
    show_data = load_show()
    message = MIMEMultipart()
    message["From"] = config.Mail.smtp_user
    message["To"] = email

    qr_image_path = f"./codes/{tid}.png"
    pdf_filename = f"./codes/{tid}.pdf"

    if type != "normal":

        message["Subject"] = (config.Mail.mail_title_paid).format(id=str(first_name))
        message.attach(
            MIMEText(
                f"""
                <!DOCTYPE html>
                <html lang="de">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>QrGate E-Mail</title>
                </head>
                <body style="background-color: #0a0a0a; color: #ffffff; font-family: Arial, sans-serif; line-height: 1.6;background-size: 23px 23px;background-image: repeating-linear-gradient(45deg, #222222 0, #222222 2.3000000000000003px, #0a0a0a 0, #0a0a0a 50%);background-attachment: fixed;">
                    <div style="height: 14px;background: linear-gradient(90deg, #9333ea, #ec4899, #eab308);width: 100%;position: fixed;top: 0;z-index: 1000;background-size: 200% 200%;animation: gradient 10s ease infinite;"></div>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td align="center">
                                <h1 style="font-size: 3.5rem; color: #ffffff;">Your QrGate Ticket <span style="background-color: rgba(147, 51, 234, 0.2);padding: 2px 8px;color: rgb(216, 180, 254);border-radius: 4px;transition: all 0.3s ease;">has been paid</span></h1>
                                <p style="color: #888888;">Your ticket has been paid for on site. This email serves as confirmation that your ticket has been paid for.</p>
                                <p style="color: #888888;">Below and in the attachment you will find your ticket again. Use the QrCode to get through the entrance.</p>
                                <p style="color: #888888;">This ticket is for <span style="background-color: rgba(234, 176, 51, 0.2);padding: 2px 8px;color: rgb(255, 176, 112);border-radius: 4px;transition: all 0.3s ease;">{first_name} {last_name}</span>.</p>
                                <p style="color: #888888;">The QR code below is required to validate your ticket. Please have this ticket ready before entering.</p>
                                <img src="{config.API.backend_url}/codes/show?tid={tid}" alt="QR-Code" style="width: 200px; height: 200px; border: 4px solid #222222; border-radius: 8px;">
                                <p style="color: #888888; font-size: small;"><a href="{config.API.backend_url}/codes/show?tid={tid}">{tid}</a></p>
                                <p style="color: #888888;">This ticket can only be used once. To re-enter, a stamp or ribbon is required, which will be issued at the exit on request.<br>We wish you lots of fun during your stay.</p>
                                <br>
                                <p style="color: #888888; font-size: small;">Managed by Qr-Gate - avocloud.net</p>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>
                </body>
                </html>
                """,
                "html",
            )
        )
    else:

        message["Subject"] = (config.Mail.mail_title).format(id=str(first_name))
        paid_message = (
            ""
            if paid
            else "As your ticket has not yet been paid for, it cannot yet be used."
        )
        paid_message_html = (
            "Your ticket is paid and ready to use."
            if paid
            else """As your ticket <span style="background-color: rgba(234, 51, 51, 0.2);padding: 2px 8px;color: rgb(254, 180, 180);border-radius: 4px;transition: all 0.3s ease;">has not yet been paid</span> for, it cannot yet be used. We therefore ask you to pay for your tickets on the day of the event at the entrence in order to activate it"""
        )

        qr = qrcode.QRCode(
            version=1,
            error_correction=qrcode.constants.ERROR_CORRECT_L,
            box_size=10,
            border=4,
        )
        qr.add_data(tid)
        qr.make(fit=True)

        img = qr.make_image(fill_color="black", back_color="white")
        img.save(f"./codes/{tid}.png")

        message.attach(
            MIMEText(
                f"""
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>QrGate E-Mail</title>
        </head>
        <body style="background-color: #0a0a0a; color: #ffffff; font-family: Arial, sans-serif; line-height: 1.6;background-size: 23px 23px;background-image: repeating-linear-gradient(45deg, #222222 0, #222222 2.3000000000000003px, #0a0a0a 0, #0a0a0a 50%);background-attachment: fixed;">
            <div style="height: 14px;background: linear-gradient(90deg, #9333ea, #ec4899, #eab308);width: 100%;position: fixed;top: 0;z-index: 1000;background-size: 200% 200%;animation: gradient 10s ease infinite;"></div>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        <h1 style="font-size: 3.5rem; color: #ffffff;">Your QrGate <span style="background-color: rgba(147, 51, 234, 0.2);padding: 2px 8px;color: rgb(216, 180, 254);border-radius: 4px;transition: all 0.3s ease;">Ticket</span></h1>
                        <p style="color: #888888;">{paid_message_html}</p>
                        <p style="color: #888888;">This ticket is for <span style="background-color: rgba(234, 176, 51, 0.2);padding: 2px 8px;color: rgb(255, 176, 112);border-radius: 4px;transition: all 0.3s ease;">{first_name} {last_name}</span>.</p>
                        <p style="color: #888888;">The QR code below is required to validate your ticket. Please have this ticket ready before entering.</p>
                        <img src="{config.API.backend_url}/codes/show?tid={tid}" alt="QR-Code" style="width: 200px; height: 200px; border: 4px solid #222222; border-radius: 8px;">
                        <p style="color: #888888; font-size: small;"><a href="{config.API.backend_url}/codes/show?tid={tid}">{tid}</a></p>
                        <p style="color: #888888;">This ticket can only be used once. To re-enter, a stamp or ribbon is required, which will be issued at the exit on request.<br>We wish you lots of fun during your stay.</p>
                        <br>
                        <p style="color: #888888; font-size: small;">Managed by Qr-Gate - avocloud.net</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        </body>
        </html>
        """,
                "html",
            )
        )

        pdf = SimpleDocTemplate(pdf_filename, pagesize=letter)
        elements = []

        styles = getSampleStyleSheet()
        highlighted_style = ParagraphStyle(
            name="Highlighted",
            parent=styles["Normal"],
            fontSize=14,
            textColor=colors.red,
            spaceAfter=12,
            fontName="Helvetica-Bold",
        )
        title = Paragraph("Your QrGate Ticket", styles["Title"])
        elements.append(title)
        elements.append(Spacer(1, 12))

        description = Paragraph(paid_message, highlighted_style)
        elements.append(description)
        elements.append(Spacer(1, 12))

        description2 = Paragraph(
            "The QR code below is required to validate your ticket. Please have this ticket ready before entering.",
            styles["Normal"],
        )
        elements.append(description2)
        elements.append(Spacer(1, 12))

        qr_image = Image(qr_image_path, width=150, height=150)
        elements.append(qr_image)
        elements.append(Spacer(1, 12))

        ticket_id = Paragraph(f"Ticket ID: {tid}", styles["Normal"])
        elements.append(ticket_id)
        elements.append(Spacer(1, 12))

        name = Paragraph(f"Name: {first_name} {last_name}", styles["Normal"])
        elements.append(name)
        elements.append(Spacer(1, 12))

        usage_message = Paragraph(
            "This ticket can only be used once. To re-enter, a stamp or ribbon is required, which will be issued at the exit on request.",
            styles["Normal"],
        )
        elements.append(usage_message)
        elements.append(Spacer(1, 12))

        closing_message = Paragraph(
            f"We wish you lots of fun during your stay at {show_data['orga_name']}.",
            styles["Normal"],
        )
        elements.append(closing_message)
        elements.append(Spacer(1, 12))

        managed_by = Paragraph("Managed by QrGate - avocloud.net", styles["Normal"])
        elements.append(managed_by)

        pdf.build(elements)

    with open(pdf_filename, "rb") as pdf_file:
        part = MIMEApplication(pdf_file.read(), Name=os.path.basename(pdf_filename))
        part["Content-Disposition"] = (
            f'attachment; filename="{os.path.basename(pdf_filename)}"'
        )
        message.attach(part)

    with smtplib.SMTP(config.Mail.smtp_server, config.Mail.smtp_port) as server:
        server.starttls()
        server.login(config.Mail.smtp_user, config.Mail.smtp_password)
        server.sendmail(config.Mail.smtp_user, email, message.as_string())


def edit_ticket(app=quart.Quart):
    @app.route("/api/ticket/edit", methods=["POST"])
    async def edit_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401

        try:
            data: dict = await quart.request.get_json()
            tid: str = str(data.get("tid")).upper()
            print(tid)

            if tid is None:
                return (
                    quart.jsonify({"status": "error", "message": "Ticket not found"}),
                    404,
                )

            ticket = load_ticket_id(tid)

            if ticket is None:
                return (
                    quart.jsonify({"status": "error", "message": "Ticket not found"}),
                    404,
                )

            print(data.get("valid"))
            print(data.get("paid"))
            print(data.get("valid_date"))
            print(data.get("first_name"))
            print(data.get("last_name"))
            print(data.get("type"))

            paid: bool = data.get("paid", ticket["paid"])
            paid_old: bool = ticket["paid"]
            valid_date: str = data.get("valid_date", ticket["valid_date"])
            first_name: str = data.get("first_name", ticket["first_name"])
            last_name: str = data.get("last_name", ticket["last_name"])
            valid: bool = data.get("valid", ticket["valid"])

            ticket.update(
                {
                    "paid": paid,
                    "valid_date": valid_date,
                    "first_name": first_name,
                    "last_name": last_name,
                    "valid": valid,
                    "type": data.get("type", ticket["type"]),
                }
            )

            if paid and not paid_old:
                ticket["valid"] = True

            save_tickets(tid, ticket)
            if paid and not paid_old:
                print("Sending email")
                await send_email(
                    first_name,
                    last_name,
                    ticket.get("email"),
                    tid,
                    paid,
                    type="paid_on_site",
                )
            return quart.jsonify({"status": "success", "message": "Ticket edited"}), 200
        except Exception as e:
            print(e)
            return quart.jsonify({"status": "error", "message": str(e)}), 500


def view_ticket(app=quart.Quart):
    @app.route("/api/ticket/get", methods=["GET", "POST"])
    async def view_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            data: dict = await quart.request.get_json()
            tid: str = str(data.get("tid")).upper()

            ticket = load_ticket_id(tid)
            if ticket == None:
                data = {
                    "tid": f"{tid}",
                    "first_name": "Unknown",
                    "last_name": "Unknown",
                    "type": "Unknown",
                    "paid": "Unknown",
                    "valid_date": "Unknown",
                    "valid": "Unknown",
                    "used_at": "Unknown",
                    "access_attempts": [],
                }
                logger.debug.info(f"Ticket not found: {data}")
                return (
                    quart.jsonify(
                        {"status": "error", "message": "Ticket not found", "data": data}
                    ),
                    200,
                )
            return (
                quart.jsonify(
                    {"status": "success", "message": "Ticket loaded", "data": ticket}
                ),
                200,
            )
        except Exception as e:
            return quart.jsonify({"status": "error", "message": str(e)}), 500

    @app.route("/codes/show")
    async def show_code():
        tid = quart.request.args.get("tid")
        return await quart.send_file(f"./codes/{tid}.png")


def get_available_tickets(app=quart.Quart):
    @app.route("/api/ticket/available_tickets/<show_id>", methods=["GET"])
    async def available_tickets(show_id):
        try:

            with open("backend/data/shows.json", "r") as f:
                shows_data = json.load(f)

            if show_id not in shows_data["dates"]:
                return (
                    quart.jsonify({"status": "error", "message": "Show ID not found"}),
                    404,
                )

            available_tickets = shows_data["dates"][show_id]["tickets_available"]

            return (
                quart.jsonify(
                    {"status": "success", "available_tickets": available_tickets}
                ),
                200,
            )
        except Exception as e:
            return quart.jsonify({"status": "error", "message": str(e)}), 500


def generate_ticket_id(valid_date):
    # Das Datum wird in das gewünschte Format umgewandelt
    date_parts = valid_date.split("-")
    year = date_parts[0]
    month = date_parts[1]
    day = date_parts[2]

    letters = string.ascii_uppercase
    digits = string.digits
    random_part = "".join(random.choice(letters + digits) for _ in range(4))

    ticket_id = f"{year}-{day}{month}-{random_part}"
    return ticket_id
