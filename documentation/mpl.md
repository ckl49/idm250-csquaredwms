# MPL API Documentation (mpl.php)

## Overview

**Endpoint**  
`https://digmstudents.westphal.drexel.edu/~ckl49/idm250-csquaredwms/api/orders.php` (or whatever file routes to this code)

**Supported Methods**  
POST

**Content-Type**  
`application/json`

**CORS**  
`Access-Control-Allow-Origin: *`

**Authentication**  
Required.  
A valid API key must be provided. The exact header name/format is not shown in the provided snippet and must be confirmed separately.

---

## Data Model: mpl Table

Based strictly on the SQL in the provided file, the following columns are used:

| Field Name         | Type     | Required (POST) | Notes |
|-------------------|----------|-----------------|------|
| id                | integer  | Yes             | Inserted as provided (not auto-generated in this snippet) |
| order_number      | integer  | Yes             |  |
| truck_number      | integer  | Yes             |  |
| expected_delivery | string   | Yes             | Bound as a string (`"s"`). Format is not validated in code. |

---

## Response Format

Common success pattern:
```json
{ "success": true, "data": "New item created successfully" }
```

Common error patterns:
```json
{ "error": "Bad Request", "details": "Missing required field(s)" }
{ "success": false, "error": "Database error: <error message>" }
```

---

# POST Method

Creates a new MPL record.

### Required JSON Body
```json
{
  "id": 1,
  "order_number": 10001,
  "truck_number": 77,
  "expected_delivery": "2026-02-25 14:30:00"
}
```

### Missing Required Fields

HTTP 400
```json
{
  "error": "Bad Request",
  "details": "Missing required field(s)"
}
```

### Success Response

HTTP 201
```json
{
  "success": true,
  "data": "New item created successfully"
}
```

### Database Error

May return HTTP 200 with:
```json
{
  "success": false,
  "error": "Database error: <error message>"
}
```

Note: The code does not explicitly set a non-200 status code for database failures.

### Example
```bash
curl -X POST "<BASE_URL>/mpl.php" \
  -H "Content-Type: application/json" \
  -d '{
    "id": 1,
    "order_number": 10001,
    "truck_number": 77,
    "expected_delivery": "2026-02-25 14:30:00"
  }'
```

---

## Unsupported Methods

Only POST is implemented in the provided code. Other HTTP methods are not handled explicitly and may result in default server behavior.