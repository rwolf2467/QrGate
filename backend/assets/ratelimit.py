"""Minimal in-process rate limiter.

Single fixed-window counter keyed by an arbitrary string (typically client IP +
a route tag). This is intentionally simple: it lives in process memory, so it
resets on restart and is NOT shared across multiple worker processes. It exists
to blunt brute-force/abuse against the login and validate endpoints, not to be a
distributed quota system. Behind a reverse proxy, remote_addr may be the proxy —
configure the proxy to forward the real client IP if you rely on per-client limits.
"""

import time
from threading import Lock

_buckets: dict[str, tuple[int, float]] = {}  # key -> (count, window_start_monotonic)
_lock = Lock()


def allow(key: str, max_hits: int, window_seconds: float) -> bool:
    """Return True if this hit is allowed, False if `key` already used up its
    `max_hits` within the current `window_seconds` fixed window."""
    now = time.monotonic()
    with _lock:
        count, started = _buckets.get(key, (0, now))
        if now - started >= window_seconds:
            # Window expired: start a fresh one.
            count, started = 0, now
        count += 1
        _buckets[key] = (count, started)
        return count <= max_hits
