```diff
+VALIDATE TICKET
/api/ticket/vaildate
POST/GET
- API KEY (header)
- tid (ticket ID - CR code inhalt)

answer:
{"status": "success", "message": "valid"}
{"status": "error", "message": "not paied"}
...
```

```diff
+TICKET CREATE
/api/ticket/create
POST
- API KEY (header)
- first_name 
- last_name
- valid_date (datum des ausgewählten datums - jjjj-mm-dd)
- paid (barzahlung oder online (zahlung erfolgreich?) - True / False (bool))
- method (zeigt an ob mit karte oder bar bezahlt wird - bar / paypal)
- seats (anzahl an sitze die gebucht wurden)

answer:
{'status': 'success', 'message': 'Ticket created', 'tid': tid}
```

```diff
+TICKET EDIT
/api/ticket/edit
POST
- API KEY (header)
- tid (ticket ID) 
- paid (barzahlung oder online (zahlung erfolgreich?) - True / False (bool)) (optional)
- valid_date (datum des ausgewählten datums - jjjj-mm-dd) (optional)
- valid (zeigt an ob das ticket zum aktuellen zeitpunkz entwertet werden kann. Je nach dem ob das ticket bereits entwertet wurde oder bezahlt wurde)
- seats ( Anzahl an sitze die gebucht werden)

Answer:
{"status": "success", "message": "erfolgreich gespeichert"}
```

```diff
+TICKET VIEW
/api/ticket/view
PORST / GET
- API KEY (header)
- tid (ticket ID)

Answer:
{
    "status": "success",
    "message": "Ticket loaded",
    "data": {
        "first_name": string,
        "last_name": string,
        "valid": boolean,
        "valid_date": string,
        "tid": string,
        "used_at": string,
        "paid": boolean,
        "method": string
    }
}
```

```diff
+EDIT SHOW
/api/show/edit
POST
- API KEY (header)
- orga_name (optional)
- title (vorstellungsname)  (optional)
- dates (json format - siehe unten) (optional)

dates format:
{
    "1": {
        "date": "JJJJ-MM-DD",
        "time": "hh:mm",
        "seats": 200,
        "seats_available": 130,
        "price": "12"
    },
    "2": {
        "date": "JJJJ-MM-DD",
        "time": "hh:mm",
        "seats": 200,
        "seats_available": 10,
        "price": "18"
    }
}

Answer:
{"status": "success", "message": "erfolgreich gespeichert"}
```

```diff
+GET SHOW
GET / POST
- API KEY (header)

Answer:
{
    "orga_name": "avocloud",
    "title": "veranstalltungsname",
    "dates": {
        "1": {
            "date": "JJJJ-MM-DD",
            "time": "hh:mm",
            "seats": 200,
            "seats_available": 130,
            "price": "12"
        },
        "2": {
            "date": "JJJJ-MM-DD",
            "time": "hh:mm",
            "seats": 200,
            "seats_available": 10,
            "price": "18"
        }
    }
}
```