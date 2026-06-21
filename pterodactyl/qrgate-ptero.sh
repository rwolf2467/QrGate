#!/bin/bash
# Pterodactyl entrypoint for QrGate (rootless, single container).
#
# Pterodactyl runs this as the unprivileged `container` user in /home/container,
# injects ${SERVER_PORT} / ${SERVER_IP} for the web allocation, plus the egg's
# QRGATE_* variables. We render nginx's config for that port, make sure the
# writable dirs exist, then hand off to supervisor which runs the backend,
# php-fpm and nginx together.
set -e

# Frontend API key defaults to the backend auth key so the secret is set once.
export QRGATE_API_KEY="${QRGATE_API_KEY:-$QRGATE_AUTH_KEY}"
export QRGATE_API_BASE_URL="${QRGATE_API_BASE_URL:-http://127.0.0.1:1654/}"
export QRGATE_PORT="${QRGATE_PORT:-1654}"   # internal backend port (not exposed)
export QRGATE_RELOAD=0
# Shared secret file (read by backend + PHP frontend) so the API key can be set
# online in the wizard; QRGATE_SUPERVISED lets the backend self-restart on change.
export QRGATE_KEY_FILE="${QRGATE_KEY_FILE:-/home/container/data/secret.key}"
export QRGATE_SUPERVISED=1
export SERVER_PORT="${SERVER_PORT:-8080}"
# Prefer a real public IP for the printed setup link; SERVER_IP from wings is
# often the internal bind address (0.0.0.0 / 127.x), which isn't reachable.
if [ -z "$QRGATE_SETUP_URL" ]; then
    PUB_IP="$SERVER_IP"
    case "$PUB_IP" in ""|"0.0.0.0"|127.*) PUB_IP="" ;; esac
    if [ -z "$PUB_IP" ]; then
        PUB_IP="$(/opt/venv/bin/python -c "import urllib.request as u;print(u.urlopen('https://api.ipify.org',timeout=3).read().decode())" 2>/dev/null || true)"
    fi
    [ -z "$PUB_IP" ] && PUB_IP="<your-server-ip>"
    export QRGATE_SETUP_URL="http://${PUB_IP}:${SERVER_PORT}/install"
fi

# Writable runtime dirs.
mkdir -p /tmp/qrgate-nginx/body /tmp/qrgate-nginx/proxy /tmp/qrgate-nginx/fastcgi \
         /tmp/qrgate-nginx/uwsgi /tmp/qrgate-nginx/scgi
# Persisted data + generated tickets live in the Pterodactyl volume.
mkdir -p /home/container/data /home/container/codes

# Render nginx config for the assigned port (only ${SERVER_PORT} is substituted,
# so nginx's own $variables stay intact).
envsubst '${SERVER_PORT}' < /etc/qrgate/nginx.conf.template > /tmp/qrgate-nginx.conf

echo "[qrgate] starting on port ${SERVER_PORT} — setup wizard: ${QRGATE_SETUP_URL}"
exec supervisord -n -c /etc/qrgate/supervisord.conf
