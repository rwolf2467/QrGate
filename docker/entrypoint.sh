#!/bin/sh
# Combined-image entrypoint.
#
# The frontend's API key MUST match the backend's auth key. To avoid having to
# pass the same secret twice, default QRGATE_API_KEY to QRGATE_AUTH_KEY when it
# is not set explicitly. Same idea for the internal backend URL.
set -e

export QRGATE_API_KEY="${QRGATE_API_KEY:-$QRGATE_AUTH_KEY}"
export QRGATE_API_BASE_URL="${QRGATE_API_BASE_URL:-http://127.0.0.1:1654/}"
export QRGATE_SETUP_URL="${QRGATE_SETUP_URL:-http://localhost:8080/install}"

# Make sure mounted volumes are writable by the service user.
chown -R www-data:www-data /app/backend/data /app/backend/codes 2>/dev/null || true

exec "$@"
