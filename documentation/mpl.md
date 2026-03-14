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

Authentication is provided by API key in request headers.

Header format:

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

## Data Model: mpl Table

Based strictly on fields referenced in this file, the following `mpl` table columns are used:

| Field Name      | Type    | Required | Notes |
|----------------|---------|----------|------|
| id             | integer | No       | Used for DELETE |
| reference_numb | string  | Yes      | Required when creating a new MPL |
| trailer_name   | string  | Yes      | Required when creating a new MPL |
| ship_date      | string  | Yes      | Required when creating a new MPL |
| status         | string  | Auto-set | Set to `draft` during create |
| item_id        | integer | No       | Updated from the optional `items` array |

---

## External API Dependency

The GET method fetches data from this external API:

`https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api-mpl.php`

The receive action in POST also notifies that external API with an updated status.

---

## Response Format

### Success Responses

```json
{ "success": true, "data": [...] }
```

```json
{ "success": true, "message": "MPL created successfully" }
```

```json
{ "success": true, "message": "MPL received successfully" }
```

```json
{ "success": true, "message": "MPL 3 deleted successfully" }
```

### Error Responses

```json
{ "error": "Unauthorized" }
```

```json
{ "error": "Bad Request", "details": "Missing required field(s): reference_numb, trailer_name, ship_date" }
```

```json
{ "error": "Bad Request", "details": "mpl_id is required" }
```

```json
{ "success": false, "error": "No MPL records found" }
```

```json
{ "success": false, "error": "ID is required for deletion" }
```

```json
{ "success": false, "error": "No MPL found with ID 3" }
```

```json
{ "success": false, "error": "Database error: <error message>" }
```

```json
{ "error": "Method Not Allowed" }
```

---

# GET Method

Fetches MPL records from the external API.

### Request
No request body required.

### External Request Behavior

The API sends a GET request to the external endpoint using this header:

```http
x-api-key: test
```

### Success Response

```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "reference_numb": "REF-1001",
      "trailer_name": "TR-7",
      "ship_date": "2026-03-15",
      "status": "pending"
    }
  ]
}
```

### No Records Found

```json
{
  "success": false,
  "error": "No MPL records found"
}
```

Note: The exact fields returned by GET depend on the external API response.

---

# POST Method

POST supports two actions:

1. Create a new MPL
2. Receive an MPL

If `action` is not included, the API defaults to create behavior.

---

## POST Create MPL

Creates a new MPL record in the local database.

### Required JSON Body

```json
{
  "reference_numb": "REF-1001",
  "trailer_name": "TR-7",
  "ship_date": "2026-03-15",
  "items": [
    {
      "item_id": 123
    },
    {
      "item_id": 456
    }
  ]
}
```

### Required Fields

- `reference_numb`
- `trailer_name`
- `ship_date`

### Optional Fields

- `items`

### Item Format

```json
{
  "item_id": 123
}
```

### Missing Required Fields

HTTP 400

```json
{
  "error": "Bad Request",
  "details": "Missing required field(s): reference_numb, trailer_name, ship_date"
}
```

### Success Response

HTTP 201

```json
{
  "success": true,
  "message": "MPL created successfully"
}
```

### Database Error

```json
{
  "success": false,
  "error": "Database error: <error message>"
}
```

### Create Behavior Notes

- A new MPL row is inserted with `status` set to `draft`.
- If `items` are included, the API attempts to update `item_id` values for rows matching the same `reference_numb` where `item_id IS NULL`.

---

## POST Receive MPL

Receives an existing MPL.

### Required JSON Body

```json
{
  "action": "receive",
  "mpl_id": 3
}
```

### Required Fields

- `action` = `"receive"`
- `mpl_id`

### Missing `mpl_id`

HTTP 400

```json
{
  "error": "Bad Request",
  "details": "mpl_id is required"
}
```

### Success Response

HTTP 200

```json
{
  "success": true,
  "message": "MPL received successfully"
}
```

### Failure Response

HTTP 422

```json
{
  "success": false,
  "message": "MPL receive failed"
}
```

Note: The exact success or failure response depends on what the `receive_mpl($conn, $mpl_id)` helper function returns.

### External Notification

If receive succeeds, the API sends this JSON payload to the external MPL API:

```json
{
  "id": 3,
  "status": "accepted"
}
```

---

# DELETE Method

Deletes an MPL by `id` from the local database.

### Required JSON Body

```json
{
  "id": 3
}
```

### Missing ID

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

### Not Found Response

```json
{
  "success": false,
  "error": "No MPL found with ID 3"
}
```

### Database Error

```json
{
  "success": false,
  "error": "<error message>"
}
```

---

## Unsupported Methods

Any HTTP method other than GET, POST, or DELETE will return:

HTTP 405

```json
{
  "error": "Method Not Allowed"
}
```

---

## Important Implementation Notes

- GET does not query the local database. It pulls MPL records from the external API.
- POST without an `action` field creates a new MPL locally.
- POST with `action: "receive"` calls the local `receive_mpl()` helper and, on success, sends `status: "accepted"` to the external API.
- DELETE removes an MPL from the local database by `id`.
- The exact response structure for the receive action depends on the `receive_mpl()` helper function.