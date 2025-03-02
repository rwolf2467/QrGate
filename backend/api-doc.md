> /api/ticket/validate - POST / GET
> 
> Request:
> 
> ```json
> Header: Authorization - KEY
> 
> {
>     "tid": 12345678
> }
> ```
> 
> Response:
> 
> ```json
> {
>     "status": "success", 
>     "message": "Ticket is valid", 
>     "data": 12345678
> }
> 
> 
> {
>     "status": "error", 
>     "message": "Ticket is not valid today"
> }
> ```



> /api/ticket/create - POST
> 
> Request:
> 
> ```json
> HEADER: Authorization - KEY
> 
> {
>     "paid": false,
>     "valid_date": "2025-02-10", #yyyy-mm-dd
>     "first_name": "Max",
>     "last_name": "Mustermann",
>     "email": "max.mustermann@example.com",
>     "tickets": 3
> }
> ```
> 
> Response:
> 
> ```json
> {
>    "status": "success",
>    "message": "Ticket created",
>    "tid": 12345678
> }
> 
> 
> {
>     "status": "error", 
>     "message": "Not enough tickets available""
> }
> ```



> /api/ticket/edit - POST
> 
> Request:
> 
> ```json
> HEADER: Authorization - KEY
> 
> {
>     "tid": 12345678,
>     "paid": true, #optional
>     "valid_date": "2025-02-10", #optinal
>     "first_name": "Max", #optional
>     "last_name": "Mustermann", #optional
>     "tickets": 3
> }
> ```
> 
> Response:
> 
> ```json
> {
>     "status": "success", 
>     "message": "Ticket edited"
> }
> 
> 
> {
>     "status": "error", 
>     "message": "<ERROR MESSAGE>"
> }
> ```



> /api/show/edit - POST
> 
> Request:
> 
> ```json
> HEADER: Authorization - KEY
> 
> {
>     "orga_name": "Helenenbühne", #optinal
>     "title": "Show Name 1", #optinal
>     "banner": "https://<link to image>", #optional
>     "dates": {
>         "1": {
>             "date": "2025-02-12",
>             "time": "hh:mm",
>             "tickets": 200,
>             "tickets_available": 130,
>             "price": "12"
>         },
>         "2": {
>             "date": "2025-02-11",
>             "time": "hh:mm",
>             "tickets": 200,
>             "tickets_available": 10,
>             "price": "18"
>         }
>     } #optional
> }
> ```
> 
> Response:
> 
> ```json
> {
>     "status": "success", 
>     "message": "Show config saved"
> }
> 
> {
>     "status": "error", 
>     "message": "<ERROR MESSAGE>"
> }
> ```



> /api/show/get - POST / GET
> 
> Request:
> 
> ```json
> HEADER: Authorization - KEY
> ```
> 
> Response:
> 
> ```json
> {
>     "orga_name": "avocloud",
>     "title": "veranstalltungsname",
>     "banner": "https://www.donau-ries-aktuell.de/sites/default/files/styles/max_2600x2600/public/2020-02/8Frauen_PR_7566_04.02.2020.jpg",
>     "dates": {
>         "1": {
>             "date": "2025-02-12",
>             "time": "hh:mm",
>             "tickets": 200,
>             "tickets_available": 130,
>             "price": "12"
>         },
>         "2": {
>             "date": "2025-02-11",
>             "time": "hh:mm",
>             "tickets": 200,
>             "tickets_available": 10,
>             "price": "18"
>         }
>     }
> }
> ```


