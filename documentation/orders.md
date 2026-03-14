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

Authentication can be provided by API key in request headers

Accepted header formats:

```http
x-api-key: YOUR_API_KEY
```

If authentication is not present, the API returns:

HTTP 401
```json
{
  "error": "Unauthorized"
}
```

**Request Type**  
All interactions are performed using HTTP requests that return JSON responses.

---

## Data Model: orders Table

Based strictly on fields referenced in this file, the following local `orders` table columns are used:

| Field Name     | Type    | Used In     | Required | Notes |
|---------------|---------|-------------|----------|-------|
| reference_numb| string  | POST ship   | Yes      | Stored locally when an order is marked shipped |
| status        | string  | POST ship   | Auto-set | Set to `shipped` during ship action |

Note: This API primarily acts as a proxy for an external orders API. Most order data returned by GET, PUT, and DELETE comes from the external API rather than the local database.

---

## External API Dependency

This endpoint communicates with an external API at:

`https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api_orders.php`

The following methods forward requests to the external API:

- GET
- PUT
- DELETE

The POST `ship` action also sends shipment data to the external API after local inventory is updated.

If the external API cannot be reached, the API may return:

```json
{
  "success": false,
  "error": "Could not reach external API"
}
```

If the external API returns invalid JSON, the API may return:

```json
{
  "success": false,
  "error": "Invalid response from external API"
}
```

---

## Response Format

### Success Responses

```json
{ "success": true, "data": [...] }
```

```json
{ "success": true, "message": "Order shipped and inventory updated.", "external_result": {...} }
```

```json
{ "success": true }
```

### Error Responses

```json
{ "error": "Unauthorized" }
```

```json
{ "error": "Bad Request" }
```

```json
{ "success": false, "error": "ID is required for update" }
```

```json
{ "success": false, "error": "ID is required for deletion" }
```

```json
{ "success": false, "error": "Could not reach external API" }
```

```json
{ "success": false, "error": "Invalid response from external API" }
```

```json
{ "error": "Method Not Allowed" }
```

---

# GET Method

Retrieves orders from the external API.

### Request
No request body required.

### Success Response

HTTP 200

```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "reference": "REF-1001",
      "date": "2026-03-10",
      "truck": "Trailer A",
      "status": "pending"
    }
  ]
}
```

### External API Failure

HTTP 502

```json
{
  "success": false,
  "error": "Could not reach external API"
}
```

or

```json
{
  "success": false,
  "error": "Invalid response from external API"
}
```

Note: The exact order fields returned by GET depend on the external API response.

---

# POST Method

The POST method currently supports a ship action.

### Supported Action

- `action: "ship"`

If no action is provided, the code defaults to `create`, but no create logic is implemented in the provided file.

---

## POST Action: Ship Order

Ships an order, updates local inventory, notifies the external API, and stores shipped status locally.

### Required JSON Body

```json
{
  "action": "ship",
  "order_id": 42,
  "item_ids": [1, 2, 3],
  "reference": "REF-1001",
  "ship_date": "2026-03-13",
  "trailer_name": "Trailer A"
}
```

### Required Fields

- `order_id`
- `item_ids`

### Optional Fields

- `reference`
- `ship_date`
- `trailer_name`

### Missing Required Fields

HTTP 400

```json
{
  "error": "Bad Request"
}
```

### Success Response

HTTP 200

```json
{
  "success": true,
  "message": "Order shipped and inventory updated.",
  "external_result": {
    "success": true
  }
}
```

### Inventory Update Failure

If local inventory shipping fails, the API returns:

HTTP 422

```json
{
  "success": false,
  "error": "Inventory update failed"
}
```

Note: The exact failure response depends on what `ship_order($conn, $order_id, $reference)` returns.

### External Shipment Payload

When the ship action succeeds locally, the API sends this payload to the external API:

```json
{
  "id": 42,
  "reference": "REF-1001",
  "date": "2026-03-13",
  "truck": "Trailer A",
  "status": "shipped",
  "selected_items": [1, 2, 3]
}
```

### Local Database Behavior

After a successful ship action, the API inserts or updates a local `orders` record:

- `reference_numb` = provided `reference`
- `status` = `shipped`

---

# PUT Method

Forwards an update request to the external API.

### Required JSON Body

`id` is required.

```json
{
  "id": 42,
  "reference": "REF-1001",
  "date": "2026-03-13",
  "truck": "Trailer A",
  "status": "processing"
}
```

### Missing id

HTTP 400

```json
{
  "success": false,
  "error": "ID is required for update"
}
```

### Success Response

HTTP 200

```json
{
  "success": true
}
```

### Failure Response

HTTP 422

```json
{
  "success": false,
  "error": "Update failed"
}
```

Note: The exact success or failure structure depends on the external API response.

---

# DELETE Method

Forwards a delete request to the external API.

### Required JSON Body

```json
{
  "id": 42
}
```

### Missing id

HTTP 400

```json
{
  "success": false,
  "error": "ID is required for deletion"
}
```

### Success Response

HTTP 200

```json
{
  "success": true
}
```

### Failure Response

HTTP 422

```json
{
  "success": false,
  "error": "Delete failed"
}
```

Note: The exact success or failure structure depends on the external API response.

---

## Unsupported Methods

Any HTTP method other than GET, POST, PUT, or DELETE returns:

HTTP 405

```json
{
  "error": "Method Not Allowed"
}
```

---

## Important Implementation Notes

- GET, PUT, and DELETE do not directly query the local database. They proxy requests to an external API.
- POST only implements shipping logic in the provided file.
- The POST ship action performs three operations in sequence:
  1. Deducts shipped items from local inventory
  2. Sends shipment data to the external API
  3. Stores shipped status locally in the `orders` table