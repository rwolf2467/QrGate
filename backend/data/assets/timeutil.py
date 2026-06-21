import datetime as dt
import config.conf as config

try:
    from zoneinfo import ZoneInfo  # Python 3.9+
except ImportError:  # pragma: no cover - older runtimes
    ZoneInfo = None

# Default to a DST-aware zone so "today" matches local wall-clock all year.
# Override via `timezone = "<IANA name>"` in config.conf; `utc_offset` stays
# as a last-resort fallback only (it does NOT handle daylight saving).
_DEFAULT_TZ = "Europe/Berlin"


def _tzinfo():
    name = getattr(config, "timezone", None) or _DEFAULT_TZ
    if ZoneInfo is not None:
        try:
            return ZoneInfo(name)
        except Exception:
            pass
    return dt.timezone(dt.timedelta(hours=getattr(config, "utc_offset", 0)))


def local_now() -> dt.datetime:
    """Current timezone-aware local time (DST-correct)."""
    return dt.datetime.now(tz=_tzinfo())


def today_iso() -> str:
    """Local calendar date as YYYY-MM-DD."""
    return local_now().date().isoformat()
