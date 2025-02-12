import quart
import config.conf as config
from assets.data import load_tickets, save_tickets, load_ticket_id
from assets.data import load_date, save_date
import os
import datetime as dt

from reportlab.lib.pagesizes import letter
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import SimpleDocTemplate, Paragraph, Image, Spacer
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.mime.application import MIMEApplication
import qrcode, string, random


def create_ticket(app=quart.Quart):
    @app.route("/api/ticket/create", methods=["POST"])
    async def create_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            data: dict = await quart.request.get_json()
            print(data)
            
            paid: bool = data.get("paid", False)
            valid_date: str = data.get("valid_date")  # Format: YYYY-MM-DD
            first_name: str = data.get("first_name")
            last_name: str = data.get("last_name")
            email: str = data.get("email")
            seats: int = data.get("seats")

            date = load_date(valid_date)
            if not date:
                return (
                    quart.jsonify(
                        {"status": "error", "message": "Invalid date provided"}
                    ),
                    400,
                )

            seats_available = int(date["seats_available"])
            if seats > seats_available:
                return (
                    quart.jsonify(
                        {"status": "error", "message": "Not enough seats available"}
                    ),
                    400,
                )

            date["seats_available"] -= seats
            save_date(valid_date, date)
            for i in range(seats):
                characters = string.ascii_uppercase + string.digits
                tid_parts = [
                    "".join(random.choice(characters) for _ in range(4)) for _ in range(3)
                ]
                tid: str = "-".join(tid_parts)
                ticket = {
                    "tid": tid,
                    "first_name": first_name,
                    "last_name": last_name,
                    "paid": paid,
                    "valid_date": valid_date,
                    "seats": seats,
                    "valid": paid,
                    "used_at": None,
                }
                save_tickets(tid, ticket)

                if paid:
                    paid_message = ""
                    paid_message_html = "Your ticket is paid and ready to use."
                else:
                    paid_message = """As your ticket has not yet been paid for, it cannot yet be used. We therefore ask you to pay for your tickets on the day of the event at the entrence in order to activate it."""
                    paid_message_html = """As your ticket <span style="background-color: rgba(234, 51, 51, 0.2);padding: 2px 8px;color: rgb(254, 180, 180);border-radius: 4px;transition: all 0.3s ease;">has not yet been paid</span> for, it cannot yet be used. We therefore ask you to pay for your tickets on the day of the event at the entrence in order to activate it"""
            
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

                message = MIMEMultipart()
                message["From"] = config.Mail.smtp_user
                message["To"] = email
                message["Subject"] = (config.Mail.mail_title).format(id=int(i+1))

                mail_body = f"""
                <!DOCTYPE html>
                <html lang="de">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>QR-Code E-Mail</title>

                </head>
                <body>
                <!DOCTYPE html>
                <html lang="de">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>QR-Code E-Mail</title>
                </head>
                <body style="background-color: #0a0a0a; color: #ffffff; font-family: Arial, sans-serif; line-height: 1.6;background-size: 23px 23px;background-image: repeating-linear-gradient(45deg, #222222 0, #222222 2.3000000000000003px, #0a0a0a 0, #0a0a0a 50%);background-attachment: fixed;">
                    <div style="height: 14px;background: linear-gradient(90deg, #9333ea, #ec4899, #eab308);width: 100%;position: fixed;top: 0;z-index: 1000;background-size: 200% 200%;animation: gradient 10s ease infinite;"></div>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td align="center">
                                <h1 style="font-size: 3.5rem; color: #ffffff;">Your QrGate <span style="background-color: rgba(147, 51, 234, 0.2);padding: 2px 8px;color: rgb(216, 180, 254);border-radius: 4px;transition: all 0.3s ease;">Ticket</span></h1>
                                <p style="color: #888888;">{paid_message_html}</p>
                                <p style="color: #888888;">The QR code below is required to validate your ticket. Please have this ticket ready before entering.</p>
                                <img src="https://qrgate.avocloud.net/codes/show?tid={tid}" alt="QR-Code" style="width: 200px; height: 200px; border: 4px solid #222222; border-radius: 8px;">
                                <p style="color: #888888; font-size: small;"><a href="https://qrgate.avocloud.net/codes/show?tid={tid}">{tid}</a></p>
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
                """

                message.attach(MIMEText(mail_body, "html"))
                qr_image_path = f"./codes/{tid}.png"
                pdf_filename = f"./codes/{tid}.pdf"
                pdf = SimpleDocTemplate(pdf_filename, pagesize=letter)
                elements = []

                styles = getSampleStyleSheet()
                highlighted_style = ParagraphStyle(
                    name='Highlighted',
                    parent=styles['Normal'],
                    fontSize=14,
                    textColor=colors.red,
                    spaceAfter=12,
                    fontName='Helvetica-Bold'
                )
                title = Paragraph("Your QrGate Ticket", styles['Title'])
                elements.append(title)
                elements.append(Spacer(1, 12))

                description = Paragraph(paid_message, highlighted_style)
                elements.append(description)
                elements.append(Spacer(1, 12))

                description2 = Paragraph("The QR code below is required to validate your ticket. Please have this ticket ready before entering.", styles['Normal'])
                elements.append(description2)
                elements.append(Spacer(1, 12))


                qr_image = Image(qr_image_path, width=200, height=200)
                elements.append(qr_image)
                elements.append(Spacer(1, 12))


                ticket_id = Paragraph(f"Ticket ID: {tid}", styles['Normal'])
                elements.append(ticket_id)
                elements.append(Spacer(1, 12))

                usage_message = Paragraph("This ticket can only be used once. To re-enter, a stamp or ribbon is required, which will be issued at the exit on request.", styles['Normal'])
                elements.append(usage_message)
                elements.append(Spacer(1, 12))

                closing_message = Paragraph("We wish you lots of fun during your stay.", styles['Normal'])
                elements.append(closing_message)
                elements.append(Spacer(1, 12))

                managed_by = Paragraph("Managed by QrGate - avocloud.net", styles['Normal'])
                elements.append(managed_by)

                pdf.build(elements)

                with open(pdf_filename, "rb") as pdf_file:
                    part = MIMEApplication(pdf_file.read(), Name=os.path.basename(pdf_filename))
                    part['Content-Disposition'] = f'attachment; filename="{os.path.basename(pdf_filename)}"'
                    message.attach(part)
                
                with smtplib.SMTP(config.Mail.smtp_server, config.Mail.smtp_port) as server:
                    server.starttls()
                    server.login(config.Mail.smtp_user, config.Mail.smtp_password)
                    server.sendmail(config.Mail.smtp_user, email, message.as_string())
                

            return (
                quart.jsonify(
                    {"status": "success", "message": "Ticket created", "tid": tid}
                ),
                200,
            )
        except Exception as e:
            print("Error:", str(e))
            return quart.jsonify({"status": "error", "message": str(e)}), 500


def edit_ticket(app=quart.Quart):
    @app.route("/api/ticket/edit", methods=["POST"])
    async def edit_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            data: dict = await quart.request.get_json()
            tid: str = data.get("tid")
            ticket = load_ticket_id(tid)

            if tid == None:
                return (
                    quart.jsonify({"status": "error", "message": "Ticket not found"}),
                    404,
                )

            paid: bool = True if data.get("paid") else ticket.paid
            valid: bool = True if data.get("paid") else ticket.valid
            valid_date: str = (
                data.get("valid_date") if data.get("valid_date") else ticket.valid_date
            )
            first_name: str = (
                data.get("first_name") if data.get("first_name") else ticket.first_name
            )
            last_name: str = (
                data.get("last_name") if data.get("last_name") else ticket.last_name
            )
            seats: int = data.get("seats") if data.get("seats") else ticket.seats

            ticket["paid"] = paid
            ticket["valid_date"] = valid_date
            ticket["first_name"] = first_name
            ticket["last_name"] = last_name
            ticket["valid"] = valid
            ticket["seats"] = seats
            save_tickets(tid, ticket)
            return quart.jsonify({"status": "success", "message": "Ticket edited"}), 200
        except Exception as e:
            return quart.jsonify({"status": "error", "message": str(e)}), 500


def view_ticket(app=quart.Quart):
    @app.route("/api/ticket/get", methods=["GET", "POST"])
    async def view_ticket():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        try:
            data: dict = await quart.request.get_json()
            tid: str = data.get("tid")

            ticket = load_ticket_id(tid)
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
        print("TEST")
        tid = quart.request.args.get("tid")
        return await quart.send_file(f"./codes/{tid}.png")
