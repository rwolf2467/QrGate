import datetime as dt
version = 1.0

# Local timezone (IANA name). DST-aware — preferred. Used by assets/timeutil.
timezone = "Europe/Berlin"
# Fallback only, used if `timezone` is unset/unavailable. Does NOT handle DST.
utc_offset = 1

class API:
    port = 1654
    backend_url = "https://qrgate-backend.example.com/" # The adress (url or ip) where your backend server is reachable.
    # Allowed CORS origin for direct browser->backend requests. MUST be locked
    # to your real frontend URL in production (e.g. "https://tickets.example.com").
    # Do NOT use "*" in production. PLACEHOLDER — set per deployment.
    frontend_origin = "https://qrgate-frontend.example.com"


class Auth:
    auth_key = "YourGeneratedKeyHere"

    # ------------------------------------------------------------------ #
    # Per-deployment login accounts for /api/user/check/.
    # PLACEHOLDERS ONLY — these MUST be set per deployment and the passwords
    # MUST be rotated to real secrets before going live. Each role has a list
    # of allowed usernames and a single shared password for that role.
    # ------------------------------------------------------------------ #
    ticketflow_usernames = ["ticketflow"]   # set real box-office usernames
    ticketflow_password = "CHANGE_ME_ticketflow"   # ROTATE before deployment

    handheld_usernames = ["handheld"]       # set real scanner usernames
    handheld_password = "CHANGE_ME_handheld"        # ROTATE before deployment

    admin_usernames = ["admin"]             # set real admin usernames
    admin_password = "CHANGE_ME_admin"              # ROTATE before deployment

class Mail:
    smtp_server = "smtp.example.com"
    smtp_port = 587
    smtp_user = "user@example.com"
    smtp_password = "smtp_password"
    mail_title = "Your QrGate Ticket - {id}"
    mail_title_paid = "Your Ticket has been paid - {id}"
    mail_paid_title = "Ticket paid"
    