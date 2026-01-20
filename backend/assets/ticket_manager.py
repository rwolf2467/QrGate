import quart
import config.conf as config # type: ignore
from assets.data import load_tickets, save_tickets, load_ticket_id
from assets.data import load_date, save_date, load_show
from assets.stats import log_ticket_sale
import os
import json

from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate,
    Paragraph,
    Image,
    Spacer,
    Table,
    TableStyle,
)
from reportlab.lib.units import inch
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.mime.application import MIMEApplication
import qrcode, string, random
from reds_simple_logger import Logger
from datetime import datetime
from typing import Optional, Dict, Any, List

logger = Logger()
logger.success("Ticket_manager.py loaded")


def create_ticket(app=quart.Quart):
    @app.route("/api/ticket/create", methods=["POST"])   # type: ignore
    async def create_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            data: dict = await quart.request.get_json()
            print(data)

            paid: bool = data.get("paid", False)
            valid_date: str = str(data.get("valid_date"))
            first_name: str = str(data.get("first_name"))
            last_name: str = str(data.get("last_name"))
            email: str = str(data.get("email"))
            tickets: int = data.get("tickets", 1) 
            add_people: list = data.get("add_people", [])
            t_type: str = str(data.get("type", "visitor")) 
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

            
            price_per_ticket = float(date["price"])
            log_ticket_sale(valid_date, tickets, price_per_ticket)

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

            await send_email(
                first_name,
                last_name,
                email,
                tid,
                paid,
                date=valid_date,
                event_time=date["time"],
            )

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

                await send_email(
                    person,
                    "",
                    email,
                    tid,
                    paid,
                    date=valid_date,
                    event_time=date["time"],
                )

            return (
                quart.jsonify(
                    {"status": "success", "message": "Tickets created", "tid": tid}
                ),
                200,
            )
        except Exception as e:
            print("Error:", str(e))
            return quart.jsonify({"status": "error", "message": str(e)}), 500

    @app.route("/api/ticketflow/create", methods=["POST"])   # type: ignore
    async def create_ticket_flow():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401

        data: dict = await quart.request.get_json()
        print(data)

        
        paid: bool = data.get("paid", False)
        valid_date_input: Optional[str] = data.get("valid_date")
        t_type: str = data.get("type", "visitor")  
        first_name_input: Optional[str] = data.get("first_name", "").strip()
        last_name_input: Optional[str] = data.get("last_name", "").strip()
        email_input: Optional[str] = (data.get("email") or "").strip()
        tickets_input: Optional[int] = data.get("tickets", 1)  

        
        valid_date: str = valid_date_input if valid_date_input else ""
        first_name: str = first_name_input if first_name_input else "Unknown"
        last_name: str = last_name_input if last_name_input else "Unknown"
        email: Optional[str] = email_input if email_input else None
        tickets: int = tickets_input if tickets_input else 1

        
        if not valid_date or valid_date.strip() == "":
            if t_type not in ("admin", "vip"):
                return (
                    quart.jsonify(
                        {
                            "status": "error",
                            "message": "Valid date is required for this ticket type",
                        }
                    ),
                    400,
                )
            valid_date = "Unlimited"
        else:
            date_info:dict = load_date(valid_date) 
            if t_type not in ("admin", "vip"):
                if not date_info:
                    return (
                        quart.jsonify(
                            {"status": "error", "message": "Invalid date provided"}
                        ),
                        400,
                    )
                if tickets > int(date_info["tickets_available"]):
                    return (
                        quart.jsonify(
                            {
                                "status": "error",
                                "message": "Not enough tickets available",
                            }
                        ),
                        400,
                    )
                date_info["tickets_available"] -= tickets
                save_date(valid_date, date_info)
                
                
                price_per_ticket = float(date_info["price"])
                log_ticket_sale(valid_date, tickets, price_per_ticket)

        
        raw_tid = data.get("tid")
        if raw_tid is None or str(raw_tid).strip() == "":
            tid = generate_ticket_id(valid_date)
        else:
            tid = str(raw_tid).strip()

        
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
        print(ticket)
        save_tickets(tid, ticket)

        
        
        if valid_date != "Unlimited":
            date_info_loaded = load_date(valid_date)
            date_info: dict = date_info_loaded if date_info_loaded is not None else {"time": ""}
        else:
            date_info = {"time": ""}

        event_time = date_info.get("time", "") if valid_date != "Unlimited" else ""
        generate_ticket_pdf(tid, first_name, last_name, valid_date, event_time, variant="simple")

        
        if email:
            await send_email(
                first_name,
                last_name,
                email,
                tid,
                paid,
                date=valid_date,
                event_time=event_time,
            )

        return (
            quart.jsonify(
                {"status": "success", "message": "Ticket created", "tid": tid}
            ),
            200,
        )


def generate_ticket_pdf(
    tid: str,
    first_name: str,
    last_name: str,
    date: str,
    event_time: str,
    variant: str = "standard",  
):
    """
    Generates a printable PDF ticket and saves it to ./codes/{tid}.pdf
    Also generates the QR code image ./codes/{tid}.png

    Variants:
      - "standard": Full A4 ticket with banner and detailed info (default)
      - "simple": Minimal A5 ticket for fast printing at the box office
    """
    qr_image_path = f"./codes/{tid}.png"
    pdf_filename = f"./codes/{tid}.pdf"

    
    os.makedirs("./codes", exist_ok=True)

    
    qr = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_L,  # type: ignore
        box_size=10,
        border=4,
    )
    qr.add_data(tid)
    qr.make(fit=True)
    img = qr.make_image(fill_color="black", back_color="white")
    img.save(qr_image_path) 

    
    show_data = load_show()
    styles = getSampleStyleSheet()

    if variant == "simple":
        
        from reportlab.lib.pagesizes import A5

        pdf = SimpleDocTemplate(
            pdf_filename,
            pagesize=A5,
            rightMargin=20,
            leftMargin=20,
            topMargin=20,
            bottomMargin=20,
        )
        elements = []

        
        title = Paragraph(f"Ticket – {show_data['orga_name']}", styles["Heading1"])
        elements.append(title)
        elements.append(Spacer(1, 12))

        
        name = Paragraph(f"<b>Name:</b> {first_name} {last_name}", styles["Normal"])
        tid_para = Paragraph(f"<b>ID:</b> {tid}", styles["Normal"])
        elements.extend([name, Spacer(1, 6), tid_para, Spacer(1, 12)])

        
        if date and date != "Unlimited":
            date_para = Paragraph(f"<b>Date:</b> {date}", styles["Normal"])
            elements.append(date_para)
            if event_time:
                time_para = Paragraph(f"<b>Time:</b> {event_time}", styles["Normal"])
                elements.append(time_para)
            elements.append(Spacer(1, 12))

        
        note = Paragraph("Show this ticket at entrance.", styles["Normal"])
        elements.append(note)
        elements.append(Spacer(1, 20))

        
        elements.append(Spacer(1, 80))  

        qr_image_left = Image(qr_image_path, width=80, height=80)
        qr_image_right = Image(qr_image_path, width=80, height=80)

        ticket_id = Paragraph(f"ID: {tid}", styles["Normal"])
        name_short = Paragraph(f"{first_name} {last_name}", styles["Normal"])

        date_display = Paragraph(f"Date: {date}" if date else "", styles["Normal"])
        time_display = Paragraph(
            f"Time: {event_time}" if event_time else "", styles["Normal"]
        )

        qr_table = Table(
            [
                [[ticket_id, name_short], [date_display, time_display]],
                [qr_image_left, qr_image_right],
            ],
            colWidths=[2.2 * inch, 2.2 * inch],
        )

        qr_table.setStyle(
            TableStyle(
                [
                    ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                    ("ALIGN", (0, 0), (-1, -1), "CENTER"),
                    ("GRID", (0, 0), (-1, -1), 0, colors.transparent),
                    ("LINEBELOW", (0, 0), (-1, 0), 0.5, colors.black),
                    ("LINEBEFORE", (1, 0), (1, -1), 0.5, colors.black),
                ]
            )
        )

        elements.append(qr_table)
        elements.append(Spacer(1, 10))

        managed_by = Paragraph("QrGate - avocloud.net", styles["Normal"])
        elements.append(managed_by)

    else:
        
        from reportlab.lib.pagesizes import A4

        pdf = SimpleDocTemplate(
            pdf_filename,
            pagesize=A4,
            rightMargin=30,
            leftMargin=30,
            topMargin=-5,
            bottomMargin=10,
        )
        elements = []

        banner_path = os.path.join(
            os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
            "data",
            "assets",
            "banner.png",
        )

        if os.path.exists(banner_path):
            banner = Image(banner_path, width=8.5 * inch, height=2.5 * inch)
            banner.hAlign = "CENTER"
            elements.append(banner)
            elements.append(Spacer(1, 30))

        title = Paragraph(
            f"Your ticket for the event at {show_data['orga_name']}", styles["Title"]
        )
        elements.append(title)
        elements.append(Spacer(1, 30))

        usage_message_1 = Paragraph(
            "This ticket, once paid for, will allow you to go directly to the entrance. The ticket will be checked upon entry and then immediately validated.",
            styles["Normal"],
        )
        elements.append(usage_message_1)

        elements.append(Spacer(1, 5))

        usage_message_2 = Paragraph(
            "This ticket can only be used once. To re-enter, a stamp or ribbon is required, which will be issued at the exit on request. Please note that this ticket is only valid on the day specified.",
            styles["Normal"],
        )
        elements.append(usage_message_2)

        elements.append(Spacer(1, 5))

        description3 = Paragraph(
            "Children under the age stated on the website may enter free of charge.",
            styles["Normal"],
        )
        elements.append(description3)

        elements.append(Spacer(1, 12))

        description2 = Paragraph(
            "The QR code below is required to validate your ticket. Please have this ticket ready before entering.",
            styles["Normal"],
        )
        elements.append(description2)

        elements.append(Spacer(1, 20))

        closing_message = Paragraph(
            f"We wish you lots of fun during your stay at {show_data['orga_name']}.",
            styles["Normal"],
        )
        elements.append(closing_message)
        elements.append(Spacer(1, 20))

        elements.append(Spacer(1, 180))

        qr_image_left = Image(qr_image_path, width=100, height=100)
        qr_image_right = Image(qr_image_path, width=100, height=100)

        ticket_id = Paragraph(f"Ticket ID: {tid}", styles["Normal"])
        name = Paragraph(f"Name: {first_name} {last_name}", styles["Normal"])
        date_para = Paragraph(f"Date: {date}", styles["Normal"])
        time_para = Paragraph(f"Time: {event_time}", styles["Normal"])

        qr_table = Table(
            [
                [[ticket_id, name], [date_para, time_para]],
                [qr_image_left, qr_image_right],
            ],
            colWidths=[3.5 * inch, 3.5 * inch],
        )

        qr_table.setStyle(
            TableStyle(
                [
                    ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                    ("ALIGN", (0, 0), (-1, -1), "CENTER"),
                    ("GRID", (0, 0), (-1, -1), 0, colors.transparent),
                    ("LINEBELOW", (0, 0), (-1, 0), 0.5, colors.black),
                    ("LINEBEFORE", (1, 0), (1, -1), 0.5, colors.black),
                ]
            )
        )

        elements.append(qr_table)
        elements.append(Spacer(1, 20))

        managed_by_style = ParagraphStyle(
            name="ManagedBy",
            parent=styles["Normal"],
            alignment=1,
        )
        managed_by = Paragraph("Managed by QrGate - avocloud.net", managed_by_style)
        elements.append(managed_by)

    
    pdf.build(elements)
    return pdf_filename


async def send_email(
    first_name: str,
    last_name: str,
    email: str,
    tid: str,
    paid: bool,
    date: str,
    event_time: str,
    type: str = "normal",
):
    """
    Sends an email with the ticket PDF attached.
    Automatically generates the PDF if needed.
    """
    if not email:
        return  

    
    pdf_path = f"./codes/{tid}.pdf"
    if not os.path.exists(pdf_path):
        generate_ticket_pdf(tid, first_name, last_name, date, event_time)

    qr_image_path = f"./codes/{tid}.png"

    message = MIMEMultipart()
    message["From"] = config.Mail.smtp_user
    message["To"] = email

    show_data = load_show()

    if type != "normal":
        message["Subject"] = (config.Mail.mail_title_paid).format(id=str(first_name))
        html_content = f"""
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
        """
    else:
        message["Subject"] = (config.Mail.mail_title).format(id=str(first_name))
        paid_message_html = (
            "Your ticket is paid and ready to use."
            if paid
            else """As your ticket <span style="background-color: rgba(234, 51, 51, 0.2);padding: 2px 8px;color: rgb(254, 180, 180);border-radius: 4px;transition: all 0.3s ease;">has not yet been paid</span> for, it cannot yet be used. We therefore ask you to pay for your tickets on the day of the event at the entrence in order to activate it"""
        )
        html_content = f"""
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
        """

    message.attach(MIMEText(html_content, "html"))

    
    with open(pdf_path, "rb") as pdf_file:
        part = MIMEApplication(pdf_file.read(), Name=os.path.basename(pdf_path))
        part["Content-Disposition"] = (
            f'attachment; filename="{os.path.basename(pdf_path)}"'
        )
        message.attach(part)

    
    with smtplib.SMTP(config.Mail.smtp_server, config.Mail.smtp_port) as server:
        server.starttls()
        server.login(config.Mail.smtp_user, config.Mail.smtp_password)
        server.sendmail(config.Mail.smtp_user, email, message.as_string())


def edit_ticket(app=quart.Quart):
    @app.route("/api/ticket/edit", methods=["POST"])   # type: ignore
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
                date = load_date(valid_date)
                await send_email(
                    first_name,
                    last_name,
                    ticket.get("email"), 
                    tid,
                    paid,
                    date=valid_date,
                    event_time=date["time"], 
                )
            return quart.jsonify({"status": "success", "message": "Ticket edited"}), 200
        except Exception as e:
            print(e)
            return quart.jsonify({"status": "error", "message": str(e)}), 500


def view_ticket(app=quart.Quart):
    @app.route("/api/ticket/get", methods=["GET", "POST"])    # type: ignore
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

    @app.route("/codes/show", methods=["GET"])   # type: ignore
    async def show_code():
        tid = quart.request.args.get("tid")
        return await quart.send_file(f"./codes/{tid}.png")

    @app.route("/codes/pdf", methods=["GET"])    # type: ignore
    async def show_pdf():
        tid = quart.request.args.get("tid")
        pdf_path = f"./codes/{tid}.pdf"
        if os.path.exists(pdf_path):
            return await quart.send_file(pdf_path, mimetype="application/pdf")
        else:
            return quart.jsonify({"error": "PDF not found"}), 404


def get_available_tickets(app=quart.Quart):
    @app.route("/api/ticket/available_tickets/<show_id>", methods=["GET"])   # type: ignore
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
    date_parts = valid_date.split("-")
    year = date_parts[0]
    month = date_parts[1]
    day = date_parts[2]

    letters = string.ascii_uppercase
    digits = string.digits
    random_part = "".join(random.choice(letters + digits) for _ in range(4))

    ticket_id = f"{year}-{day}{month}-{random_part}"
    return ticket_id