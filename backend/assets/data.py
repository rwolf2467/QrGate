import json
import quart
from typing import Dict, Optional
from reds_simple_logger import Logger

logger = Logger()
logger.success("Data.py loaded")


def load_date(date: str):
    data = load_show()

    dates = data.get("dates")
    if not isinstance(dates, dict):
        raise ValueError("dates key in shows.json must contain a dictionary")

    for key, value in dates.items():
        if value.get("date") == date:
            print("Found show:", value)
            return value

    print(f"No show found for date: {date}")
    return None


def save_date(date: str, updated_data):
    data = load_show()
    dates = data.get("dates")
    if not isinstance(dates, dict):
        raise ValueError("dates key in shows.json must contain a dictionary")

    for key, value in dates.items():
        if value["date"] == date:

            dates[key] = updated_data
            break

    save_show(data)


def load_show():
    with open("data/shows.json", "r", encoding="utf-8") as f:
        data = json.load(f)
        return data


def save_show(data: dict):
    with open("data/shows.json", "w", encoding="utf-8") as f:
        json.dump(data, f, indent=4)


def load_tickets() -> Dict[str, Dict]:
    with open("data/tickets.json", "r", encoding="utf-8") as f:
        return json.load(f)


def load_ticket_id(tid: str) -> Optional[Dict]:
    try:
        tickets = load_tickets()
        if tickets.get(tid) is None:
            return None
        return tickets.get(tid)
    except Exception:
        return None


def save_tickets(tid: str, new_ticket: dict):
    tickets = load_tickets()
    tickets[tid] = new_ticket

    with open("data/tickets.json", "w", encoding="utf-8") as f:
        json.dump(tickets, f, indent=4, ensure_ascii=False)


def img_show(app=quart.Quart):
    @app.route("/image/show")
    async def show_img():
        tid = quart.request.args.get("img")
        return await quart.send_file(f"./images/{tid}.png")
