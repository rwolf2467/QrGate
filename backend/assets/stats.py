import json
import quart
from typing import Dict, Any
from datetime import datetime
from reds_simple_logger import Logger
from assets.data import load_show
import config.conf as config

logger = Logger()
logger.success("Stats.py loaded")


def load_stats() -> Dict[str, Any]:
    """Load statistics from stats.json"""
    try:
        with open("data/stats.json", "r", encoding="utf-8") as f:
            return json.load(f)
    except (FileNotFoundError, json.JSONDecodeError):
        # Return default structure if file doesn't exist or is invalid
        return {"sales_by_date": {}, "income_by_date": {}}


def save_stats(stats: Dict[str, Any]):
    """Save statistics to stats.json"""
    with open("data/stats.json", "w", encoding="utf-8") as f:
        json.dump(stats, f, indent=4)


def log_ticket_sale(date: str, tickets_sold: int, price_per_ticket: float):
    """Log a ticket sale to statistics"""
    stats = load_stats()
    
    # Update sales count for the date
    current_date = datetime.now().strftime("%Y-%m-%d")
    
    if current_date in stats["sales_by_date"]:
        stats["sales_by_date"][current_date] += tickets_sold
    else:
        stats["sales_by_date"][current_date] = tickets_sold
    
    # Update income for the date
    income = tickets_sold * price_per_ticket
    if current_date in stats["income_by_date"]:
        stats["income_by_date"][current_date] += income
    else:
        stats["income_by_date"][current_date] = income
    
    save_stats(stats)
    logger.info(f"Logged sale: {tickets_sold} tickets on {current_date}, income: €{income:.2f}")


def get_statistics():
    """Get all statistics"""
    return load_stats()


def get_stats_api(app=quart.Quart):
    @app.route("/api/stats", methods=["GET"])  # type: ignore
    async def get_stats():
        if config.Auth.auth_key != (key := quart.request.headers.get("Authorization")):
            return quart.jsonify({"status": "error", "message": "Unauthorized"}), 401
        
        try:
            stats = get_statistics()
            return quart.jsonify({
                "status": "success",
                "data": stats
            }), 200
        except Exception as e:
            logger.error(f"Error getting stats: {str(e)}")
            return quart.jsonify({"status": "error", "message": str(e)}), 500