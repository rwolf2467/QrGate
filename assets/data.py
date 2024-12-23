import json

def load_tickets():
    with open("data/tickets.json", "r", encoding="utf-8") as f:
        return json.load(f)
    
def save_tickets(data: dict):
    with open("data/tickets.json", "w", encoding="utf-8") as f:
        json.dump(data, f, indent=4)