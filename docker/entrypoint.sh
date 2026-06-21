#!/bin/sh
# Combined-image entrypoint.
#
# The frontend's API key MUST match the backend's auth key. To avoid having to
# pass the same secret twice, default QRGATE_API_KEY to QRGATE_AUTH_KEY when it
# is not set explicitly. Same idea for the internal backend URL.
set -e

export QRGATE_API_KEY="${QRGATE_API_KEY:-$QRGATE_AUTH_KEY}"
export QRGATE_API_BASE_URL="${QRGATE_API_BASE_URL:-http://127.0.0.1:1654/}"

# Build the setup-wizard link shown in the console on first start. We want the
# server's PUBLIC IP (not "localhost") and the real published web port. The
# container can't know the host's port mapping, so:
#   - port  = QRGATE_WEB_PORT (set this to your published port; default 80)
#   - host  = best-effort public IP, falling back to the LAN IP, then a hint
if [ -z "$QRGATE_SETUP_URL" ]; then
    WEB_PORT="${QRGATE_WEB_PORT:-80}"
    PUB_IP="$(/opt/venv/bin/python -c "import urllib.request as u;print(u.urlopen('https://api.ipify.org',timeout=3).read().decode())" 2>/dev/null || true)"
    if [ -z "$PUB_IP" ]; then
        PUB_IP="$(hostname -i 2>/dev/null | awk '{print $1}')"
    fi
    [ -z "$PUB_IP" ] && PUB_IP="<your-server-ip>"
    if [ "$WEB_PORT" = "80" ]; then
        export QRGATE_SETUP_URL="http://${PUB_IP}/install"
    else
        export QRGATE_SETUP_URL="http://${PUB_IP}:${WEB_PORT}/install"
    fi
fi

# Make sure mounted volumes are writable by the service user.
chown -R www-data:www-data /app/backend/data /app/backend/codes 2>/dev/null || true

exec "$@"
