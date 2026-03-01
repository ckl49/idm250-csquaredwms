# Inventory API Documentation (inventory.php)

## Overview

**Endpoint**  
`https://digmstudents.westphal.drexel.edu/~ckl49/idm250-csquaredwms/api/inventory.php`

**Supported Methods**  
GET, POST

**Content-Type**  
`application/json`

**CORS**  
`Access-Control-Allow-Origin: *`

**Authentication**  
Required.

If authentication is not present, the API returns:

HTTP 401
```json
{ "error": "Unauthorized" }
```

**Request Type**  
All interactions are performed using HTTP requests that return JSON responses.

---

## Data Model: inventory Table

Because `GET` uses `SELECT * FROM inventory`, additional columns may exist. Based strictly on fields referenced in this file, the following columns are used:

| Field Name      | Type          | Required (POST) | Notes |
|----------------|---------------|-----------------|------|
| id             | integer       | Yes             | Inserted as provided (not auto-generated in this snippet) |
| quant_instock  | integer       | Not validated*  | Used in INSERT and should be included |
| ficha          | integer       | Yes             |  |
| sku            | string        | Yes             |  |
| description    | string        | Yes             |  |
| uom_primary    | string        | Yes             | Unit of measure |
| piece_count    | integer       | Yes             |  |
| length_inches  | decimal/float | Yes             |  |
| width_inches   | decimal/float | Yes             |  |
| height_inches  | decimal/float | Yes             |  |
| weight_lbs     | decimal/float | Yes             |  |
| assembly       | string        | Yes             |  |
| rate           | decimal/float | Yes             |  |
| time_stamp     | datetime      | Auto-set        | Set by `NOW()` during insert |

\*Although `quant_instock` is not checked in the required-field validation, it is used in the prepared statement and should always be included to avoid unexpected behavior.

---

## Response Format

### Success Responses
```json
{ "success": true, "data": [...] }
{ "success": true, "data": "New item created successfully" }
```

### Error Responses
```json
{ "success": false, "error": "Invalid request method" }
{ "error": "Bad Request", "details": "Missing required field(s)" }
{ "success": false, "error": "Database error: <error message>" }
{ "error": "Unauthorized" }
```

---

# GET Method

Returns all inventory records.

### Request
No request body required.

### Success Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "quant_instock": 50,
      "ficha": 101,
      "sku": "SKU-001",
      "description": "Red Chair",
      "uom_primary": "EA",
      "piece_count": 1,
      "length_inches": 24.5,
      "width_inches": 18.0,
      "height_inches": 36.0,
      "weight_lbs": 15.5,
      "assembly": "No",
      "rate": 99.99,
      "time_stamp": "2026-02-25 10:00:00"
    }
  ]
}
```

### No Records Found
```json
{
  "success": false,
  "error": "Invalid request method"
}
```

Note: This error message is misleading. It is returned when zero records exist, not when the HTTP method is invalid.

---

# POST Method

Creates a new inventory record.

### Required JSON Body

```json
{
  "id": 1,
  "quant_instock": 50,
  "ficha": 101,
  "sku": "SKU-001",
  "description": "Red Chair",
  "uom_primary": "EA",
  "piece_count": 1,
  "length_inches": 24.5,
  "width_inches": 18.0,
  "height_inches": 36.0,
  "weight_lbs": 15.5,
  "assembly": "No",
  "rate": 99.99
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

---

## Unsupported Methods

Only GET and POST are implemented in the provided code. Other HTTP methods are not handled explicitly and may result in default server behavior.