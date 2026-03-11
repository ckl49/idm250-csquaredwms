# MPL API Documentation

## Endpoint
`https://digmstudents.westphal.drexel.edu/~ckl49/idm250-csquaredwms/api/mpl.php`

## Methods
- `GET`
- `POST`
- `DELETE`

## Content Type
`application/json`

## Authentication
You must be authenticated with either:
- a valid PHP session
- an `x-api-key` header

### Unauthorized Response
```json
{
  "error": "Unauthorized"
}
```

## Fields

### Create MPL Fields
- `reference_numb` (required)
- `trailer_name` (required)
- `ship_date` (required)
- `items` (optional array)

### Receive MPL Fields
- `action`: `"receive"`
- `mpl_id` (required)

### Delete Fields
- `id` (required)

### Item Format
```json
{
  "item_id": 123
}
```

## GET
Gets MPL records from this external API:

`https://digmstudents.westphal.drexel.edu/~an943/Shay_Manufacturing/APIs/api-mpl.php`

The request uses:
- method: `GET`
- header: `x-api-key: test`

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

### No Records Response
```json
{
  "success": false,
  "error": "No MPL records found"
}
```

## POST
POST supports 2 actions:
- create a new MPL
- receive an MPL

If `action` is not included, it creates a new MPL.

### POST Create MPL

#### Required Fields
- `reference_numb`
- `trailer_name`
- `ship_date`

#### Optional Fields
- `items`

#### Example Request
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

#### Success Response
```json
{
  "success": true,
  "message": "MPL created successfully"
}
```

#### Missing Fields Response
```json
{
  "error": "Bad Request",
  "details": "Missing required field(s): reference_numb, trailer_name, ship_date"
}
```

### POST Receive MPL

#### Example Request
```json
{
  "action": "receive",
  "mpl_id": 3
}
```

If successful, the API also sends this to the external API:
```json
{
  "id": 3,
  "status": "accepted"
}
```

#### Success Response
```json
{
  "success": true,
  "message": "MPL received successfully"
}
```

#### Missing `mpl_id` Response
```json
{
  "error": "Bad Request",
  "details": "mpl_id is required"
}
```

#### Failure Response
```json
{
  "success": false,
  "message": "MPL receive failed"
}
```

## DELETE
Deletes an MPL by `id`.

### Example Request
```json
{
  "id": 3
}
```

### Success Response
```json
{
  "success": true,
  "message": "MPL 3 deleted successfully"
}
```

### Missing ID Response
```json
{
  "success": false,
  "error": "ID is required for deletion"
}
```

### Not Found Response
```json
{
  "success": false,
  "error": "No MPL found with ID 3"
}
```

## Method Not Allowed
```json
{
  "error": "Method Not Allowed"
}
```

## Notes
- `GET` pulls data from the external MPL API, not the local database.
- `POST` create saves a new MPL locally with status `"draft"`.
- `POST` receive marks an MPL as received and sends `"accepted"` to the external API.
- `DELETE` removes an MPL by `id`.
- This API uses `reference_numb`, `trailer_name`, `ship_date`, and `items`.