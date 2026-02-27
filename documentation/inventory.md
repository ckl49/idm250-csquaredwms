# Inventory API Documentation

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
A valid API key must be provided. The exact header format is not shown in the provided code and must be confirmed separately.

---

## Data Model: inventory Table

Based strictly on the SQL in the provided file, the following fields are used:

| Field Name       | Type            | Required (POST) | Notes |
|------------------|----------------|-----------------|-------|
| id               | integer        | Yes             | Primary identifier |
| quant_instock    | integer        | Not validated*  | Used in INSERT but not checked in required-field validation |
| ficha            | integer        | Yes             |  |
| sku              | string         | Yes             |  |
| description      | string         | Yes             |  |
| uom_primary      | string         | Yes             | Unit of measure |
| piece_count      | integer        | Yes             |  |
| length_inches    | decimal/float  | Yes             |  |
| width_inches     | decimal/float  | Yes             |  |
| height_inches    | decimal/float  | Yes             |  |
| weight_lbs       | decimal/float  | Yes             |  |
| assembly         | string         | Yes             |  |
| rate             | decimal/float  | Yes             |  |
| time_stamp       | datetime       | Auto-set        | Set by `NOW()` during insert |

*Although `quant_instock` is not validated as required, it is used in the prepared statement and should always be included.

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
      "ficha": 123,
      "sku": "ABC-001",
      "description": "Sample item",
      "uom_primary": "pcs",
      "piece_count": 10,
      "length_inches": 12.5,
      "width_inches": 8.0,
      "height_inches": 4.0,
      "weight_lbs": 2.3,
      "assembly": "Yes",
      "rate": 1.25,
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

### Example

```bash
curl -X GET "<BASE_URL>/inventory.php" \
  -H "Accept: application/json"
```

---

# POST Method

Creates a new inventory record.

### Required JSON Body

```json
{
  "id": 1,
  "quant_instock": 50,
  "ficha": 123,
  "sku": "ABC-001",
  "description": "Sample item",
  "uom_primary": "pcs",
  "piece_count": 10,
  "length_inches": 12.5,
  "width_inches": 8.0,
  "height_inches": 4.0,
  "weight_lbs": 2.3,
  "assembly": "Yes",
  "rate": 1.25
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

## Implementation Notes

- `time_stamp` is automatically set using `NOW()` during insertion.
- Only GET and POST are implemented.
- Other HTTP methods are not handled and may result in default server behavior.
