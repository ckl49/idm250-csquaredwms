# MPL API Documentation (mpl.php)

## Overview

**Endpoint**  
`https://digmstudents.westphal.drexel.edu/~ckl49/idm250-csquaredwms/api/mpl.php`

**Supported Methods**  
GET, POST, DELETE

**Content-Type**  
`application/json`

**CORS**  
`Access-Control-Allow-Origin: *`

**Authentication**  
Required.

This API supports two authentication methods:

If authentication is not present, the API returns:

HTTP 401
```json
{ "error": "Unauthorized" }
```

**Request Type**  
All interactions are performed using HTTP requests that return JSON responses.

---

## Data Model: mpl Table

| Field Name        | Type    | Required (POST create) | Notes |
|------------------|---------|------------------------|-------|
| id               | integer | Auto-generated         | Primary identifier |
| order_number     | integer | Yes                    | Supplier order reference |
| truck_number     | integer | Yes                    | Truck identifier |
| expected_delivery| string  | Yes                    | Stored as string. Format not validated in code |

---

## Data Model: mpl_items Table

Each MPL record may contain multiple unit entries stored in `mpl_items`.

| Field Name      | Type          | Required (POST create) | Notes |
|----------------|--------------|------------------------|-------|
| id             | integer      | Auto-generated         | Primary key |
| mpl_id         | integer      | Auto-set               | References mpl.id |
| order_number   | integer      | Optional               | Present in DB |
| ficha          | integer      | Expected               | Item reference |
| quantity       | integer      | Expected               | Quantity received |
| description    | string       | Expected               | Item description |
| sku            | string       | Expected               | Stock keeping unit |
| uom_primary    | string       | Expected               | Unit of measure |
| piece_count    | integer      | Expected               | Pieces per unit |
| length_inches  | decimal      | Expected               | Length |
| width_inches   | decimal      | Expected               | Width |
| height_inches  | decimal      | Expected               | Height |
| weight_lbs     | decimal      | Expected               | Weight |
| assembly       | string       | Expected               | Yes/No |
| rate           | decimal      | Expected               | Unit rate |

Note: The current PHP snippet only inserts a subset of these fields. The database schema supports full unit details.

---

## Response Format

### Success Responses
```json
{ "success": true, "data": [...] }
{ "success": true, "data": "New MPL created successfully" }
{ "success": true, "message": "MPL 5 deleted successfully" }
```

### Error Responses
```json
{ "success": false, "error": "No MPL records found" }
{ "error": "Bad Request", "details": "Missing required field(s)" }
{ "error": "Bad Request", "details": "mpl_id is required" }
{ "success": false, "error": "Database error: <error message>" }
{ "error": "Unauthorized" }
{ "error": "Method Not Allowed" }
```

---

# GET Method

Returns all records from the `mpl` table.

### Request
No request body required.

### Success Response
```json
{
  "success": true,
  "data": [
    {
      "id": 3,
      "order_number": 44,
      "truck_number": 3,
      "expected_delivery": "2026-05-01"
    }
  ]
}
```

Important: This endpoint returns data from `mpl` only. It does not automatically return associated `mpl_items`.

---

# POST Method

The POST method supports two actions:

- Default action: Create a new MPL record
- `action: "receive"`: Mark an MPL as received

---

## POST Action: Create MPL (default)

Creates a new MPL record and inserts related unit details into `mpl_items`.

### Required Top-Level Fields

- `order_number`
- `truck_number`
- `expected_delivery`

### Optional Field

- `units` (array of full unit detail objects)

### Example JSON Body

```json
{
  "order_number": 44,
  "truck_number": 3,
  "expected_delivery": "2026-05-01",
  "units": [
    {
      "ficha": 101,
      "sku": "SKU-001",
      "description": "Red Chair",
      "quantity": 20,
      "uom_primary": "EA",
      "piece_count": 1,
      "length_inches": 24.5,
      "width_inches": 18.0,
      "height_inches": 36.0,
      "weight_lbs": 15.5,
      "assembly": "No",
      "rate": 99.99
    },
    {
      "ficha": 102,
      "sku": "SKU-002",
      "description": "Blue Table",
      "quantity": 15,
      "uom_primary": "EA",
      "piece_count": 1,
      "length_inches": 48.0,
      "width_inches": 24.0,
      "height_inches": 30.0,
      "weight_lbs": 25.0,
      "assembly": "No",
      "rate": 149.99
    }
  ]
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
  "data": "New MPL created successfully"
}
```

---

## POST Action: Receive MPL

Marks an MPL as received using `receive_mpl()`.

### Required JSON Body
```json
{
  "action": "receive",
  "mpl_id": 3
}
```

### Missing mpl_id

HTTP 400
```json
{
  "error": "Bad Request",
  "details": "mpl_id is required"
}
```

### Success / Failure Status Codes

- HTTP 200 if receiving succeeds
- HTTP 422 if receiving fails

### Example Success Response
```json
{
  "success": true,
  "message": "MPL received successfully"
}
```

---

# DELETE Method

Deletes an MPL record using `id`.

### Required JSON Body
```json
{
  "id": 3
}
```

### Missing id
```json
{
  "success": false,
  "error": "ID is required for deletion"
}
```

### Success Response
```json
{
  "success": true,
  "message": "MPL 3 deleted successfully"
}
```

### Not Found
```json
{
  "success": false,
  "error": "No MPL found with ID 3"
}
```

---

# Unsupported Methods

Any HTTP method other than GET, POST, DELETE returns:

HTTP 405
```json
{
  "error": "Method Not Allowed"
}
```