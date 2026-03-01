# Orders API Documentation (orders.php)

## Overview

**Endpoint**  
`https://digmstudents.westphal.drexel.edu/~ckl49/idm250-csquaredwms/api/orders.php`

**Supported Methods**  
GET, POST, PUT, DELETE

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

## Data Model: orders Table

Because `GET` uses `SELECT * FROM orders`, additional columns may exist in the table. Based strictly on fields referenced in this file, the following columns are used:

| Field Name      | Type    | Used In            | Required | Notes |
|---------------|---------|--------------------|----------|-------|
| id            | integer | PUT, DELETE        | Yes      | Primary identifier for update and delete |
| reference_numb| integer | POST (create), PUT | Yes      | Order reference number |
| ship_date     | string  | POST (create), PUT | Yes      | Stored as string. Format not validated in code |
| trailer_name  | string  | POST (create), PUT | Yes      | Trailer identifier |

---

## Data Model: orders_items Table

When creating an order, each item is inserted into `orders_items` using the newly created order `id`.

| Field Name        | Type          | Required (POST create) | Notes |
|------------------|--------------|------------------------|-------|
| order_id         | integer      | Auto-set               | Set to the inserted order ID |
| ficha            | integer      | Expected               | Not explicitly validated |
| sku              | string       | Expected               | Not explicitly validated |
| description      | string       | Expected               | Not explicitly validated |
| quantity         | integer      | Expected               | Not explicitly validated |
| quantity_unit    | string       | Expected               | Not explicitly validated |
| footage_quantity | integer      | Expected               | Not explicitly validated |
| uom_primary      | string       | Expected               | Not explicitly validated |
| piece_count      | integer      | Expected               | Not explicitly validated |
| length_inches    | decimal      | Expected               | Not explicitly validated |
| width_inches     | decimal      | Expected               | Not explicitly validated |
| height_inches    | decimal      | Expected               | Not explicitly validated |
| weight_lbs       | decimal      | Expected               | Not explicitly validated |
| assembly         | string       | Expected               | Not explicitly validated |
| rate             | decimal      | Expected               | Not explicitly validated |

Note: The API validates only that `items` exists at the top level. It does not validate each individual item field before insertion.

---

## Response Format

### Success Responses
```json
{ "success": true, "data": [...] }
{ "success": true, "data": "New order created successfully" }
{ "success": true, "message": "Order updated successfully" }
{ "success": true, "message": "Order 42 deleted successfully" }
```

### Error Responses
```json
{ "success": false, "error": "No orders found" }
{ "error": "Bad Request", "details": "Missing required field(s)" }
{ "error": "Bad Request", "details": "order_id is required" }
{ "success": false, "error": "Database error: <error message>" }
{ "error": "Unauthorized" }
{ "error": "Method Not Allowed" }
```

---

# GET Method

Returns all rows from the `orders` table.

### Request
No request body required.

### Success Response
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "reference_numb": 45,
      "ship_date": "2026-05-01",
      "trailer_name": "TRK-003"
    }
  ]
}
```

### No Orders Found
```json
{
  "success": false,
  "error": "No orders found"
}
```

Important: This endpoint returns data from the `orders` table only. It does not return associated `orders_items`.

---

# POST Method

The POST method supports two actions:

- Default action: Create a new order
- `action: "ship"`: Ship an existing order

---

## POST Action: Create Order

Creates a new order and inserts related items into `orders_items`.

### Required JSON Body

The following top-level fields are required:
- `reference_numb`
- `ship_date`
- `trailer_name`
- `items`

```json
{
  "reference_numb": 45,
  "ship_date": "2026-05-01",
  "trailer_name": "TRK-003",
  "items": [
    {
      "ficha": 101,
      "sku": "SKU-001",
      "description": "Red Chair",
      "quantity": 5,
      "quantity_unit": "EA",
      "footage_quantity": 2,
      "uom_primary": "EA",
      "piece_count": 1,
      "length_inches": 24.5,
      "width_inches": 18.0,
      "height_inches": 36.0,
      "weight_lbs": 15.5,
      "assembly": "No",
      "rate": 99.99
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
  "data": "New order created successfully"
}
```

### Database Error

May return HTTP 200:
```json
{
  "success": false,
  "error": "Database error: <error message>"
}
```

---

## POST Action: Ship Order

Ships an existing order using `ship_order()`.

### Required JSON Body
```json
{
  "action": "ship",
  "order_id": 42
}
```

### Missing order_id

HTTP 400
```json
{
  "error": "Bad Request",
  "details": "order_id is required"
}
```

### Success / Failure Status Codes

- HTTP 200 if shipping succeeds
- HTTP 422 if shipping fails

### Example Success Response
```json
{
  "success": true,
  "message": "Order shipped successfully"
}
```

### Implementation Note

If shipping succeeds, the API attempts an outbound HTTP POST request to:

`https://theirsite.com/api/orders.php`

Payload sent:
```json
{
  "order_id": 42,
  "status": "shipped"
}
```

The outbound response is not returned to the client.

---

# PUT Method

Updates an existing order in the `orders` table.

### Required JSON Body

`id` is required.

```json
{
  "id": 42,
  "reference_numb": 45,
  "ship_date": "2026-05-01",
  "trailer_name": "TRK-003"
}
```

### Missing id
```json
{
  "success": false,
  "error": "ID is required for update"
}
```

### Success Response
```json
{
  "success": true,
  "message": "Order updated successfully"
}
```

### Failure Response
```json
{
  "success": false,
  "error": "<error message>"
}
```

Note: PUT updates only the `orders` table. It does not modify `orders_items`.

---

# DELETE Method

Deletes an order from the `orders` table using `id`.

### Required JSON Body
```json
{
  "id": 42
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
  "message": "Order 42 deleted successfully"
}
```

### Not Found
```json
{
  "success": false,
  "error": "No order found with ID 42"
}
```

---

# Unsupported Methods

Any HTTP method other than GET, POST, PUT, DELETE returns:

HTTP 405
```json
{
  "error": "Method Not Allowed"
}
```