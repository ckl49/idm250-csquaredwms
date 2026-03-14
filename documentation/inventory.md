# Inventory API Documentation (inventory.php)

## Overview

**Endpoint**  
`https://digmstudents.westphal.drexel.edu/~ckl49/idm250-csquaredwms/api/inventory.php`

**Supported Methods**  
GET, POST, PUT, DELETE

**Content-Type**  
`application/json`

**CORS**  
`Access-Control-Allow-Origin: *`

**Authentication**  
Required.

Authentication should be provided by API key in request headers

Header format:

```
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

## Data Model: inventory Table

The API explicitly selects the following columns from the `inventory` table:

| Field Name        | Type           | Required (POST) | Notes |
|-------------------|---------------|-----------------|------|
| inventory_id      | integer       | No              | Returned as alias of `id` in GET |
| order_id          | integer       | Yes             | Associated order |
| unit_numb         | string        | Yes             | Unit number |
| ficha             | string        | Yes             | Internal identifier |
| sku               | string        | Yes             | Product SKU |
| quantity          | integer       | Yes             | Quantity of items |
| description1      | string        | Yes             | Primary description |
| description2      | string        | No              | Secondary description |
| quantity_unit     | string        | Yes             | Unit of measure |
| footage_quantity  | decimal/float | Yes             | Square footage quantity |

---

## Response Format

### Success Responses

```json
{ "success": true, "data": [...] }
```

```json
{ "success": true, "message": "Item created", "id": 10 }
```

```json
{ "success": true, "message": "Item updated" }
```

```json
{ "success": true, "message": "Item deleted" }
```

### Error Responses

```json
{ "error": "Unauthorized" }
```

```json
{ "error": "Bad Request", "details": "Missing required field: field_name" }
```

```json
{ "success": false, "error": "No inventory records found" }
```

```json
{ "success": false, "error": "<database error message>" }
```

```json
{ "error": "Method not allowed" }
```

---

# GET Method

Returns all inventory records.

### Request
No request body required.

### Example Response

```json
{
  "success": true,
  "data": [
    {
      "inventory_id": 1,
      "order_id": 120,
      "unit_numb": "A12",
      "ficha": "F100",
      "sku": "SKU-001",
      "quantity": 50,
      "description1": "Wood Panel",
      "description2": "Walnut Finish",
      "quantity_unit": "EA",
      "footage_quantity": 35.5
    }
  ]
}
```

### No Records Found

```json
{
  "success": false,
  "error": "No inventory records found"
}
```

---

# POST Method

Creates a new inventory item.

### Required JSON Body

```json
{
  "order_id": 120,
  "unit_numb": "A12",
  "ficha": "F100",
  "sku": "SKU-001",
  "quantity": 50,
  "description1": "Wood Panel",
  "description2": "Walnut Finish",
  "quantity_unit": "EA",
  "footage_quantity": 35.5
}
```

### Required Fields

- order_id
- ficha
- unit_numb
- sku
- quantity
- description1
- quantity_unit
- footage_quantity

### Missing Required Field

HTTP 400

```json
{
  "error": "Bad Request",
  "details": "Missing required field: sku"
}
```

### Success Response

HTTP 201

```json
{
  "success": true,
  "message": "Item created",
  "id": 10
}
```

### Database Error

```json
{
  "success": false,
  "error": "Database error message"
}
```

---

# PUT Method

Updates an existing inventory item.

### Required JSON Body

```json
{
  "id": 10,
  "order_id": 120,
  "unit_numb": "A12",
  "ficha": "F100",
  "sku": "SKU-001",
  "quantity": 75,
  "description1": "Wood Panel",
  "description2": "Updated description",
  "quantity_unit": "EA",
  "footage_quantity": 40.0
}
```

### Required Field

- id

### Missing ID

HTTP 400

```json
{
  "error": "Missing required field: id"
}
```

### Success Response

```json
{
  "success": true,
  "message": "Item updated"
}
```

### Database Error

```json
{
  "success": false,
  "error": "Database error message"
}
```

---

# DELETE Method

Deletes an inventory item.

### Required JSON Body

```json
{
  "id": 10
}
```

### Missing ID

HTTP 400

```json
{
  "error": "Missing required field: id"
}
```

### Success Response

```json
{
  "success": true,
  "message": "Item deleted"
}
```

### Database Error

```json
{
  "success": false,
  "error": "Database error message"
}
```

---

## Unsupported Methods

Any HTTP method other than GET, POST, PUT, or DELETE will return:

HTTP 405

```json
{
  "error": "Method not allowed"
}
```