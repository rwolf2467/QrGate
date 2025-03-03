import datetime as dt
version = 0.4

utc_offset = 1

class API:
    port = 1654


class Auth:
    auth_key = "YourGeneratedKeyHere"

class Mail:
    smtp_server = "smtp.example.com"
    smtp_port = 587
    smtp_user = "user@example.com"
    smtp_password = "smtp_password"
    mail_title = "Your QrGate Ticket - {id}"
    mail_title_paid = "Your Ticket has been paid - {id}"
    mail_paid_title = "Ticket paid"
    