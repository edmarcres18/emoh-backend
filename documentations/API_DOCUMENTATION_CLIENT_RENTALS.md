# Client Rental Properties API Documentation

## Endpoint: Get Client's Rented Properties

Allows authenticated clients to view all their rental properties including active rentals and rental history.

### Endpoint Details

- **URL**: `/api/client/my-rentals`
- **Method**: `GET`
- **Authentication**: Required (Sanctum Bearer Token)
- **Guard**: `client`

---

## Request Parameters

All parameters are optional and can be used for filtering and pagination.

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `status` | string | No | `all` | Filter by rental status: `active`, `pending`, `expired`, `terminated`, `ended`, or `all` |
| `per_page` | integer | No | `15` | Number of results per page (1-100) |
| `page` | integer | No | `1` | Page number for pagination |
| `search` | string | No | - | Search in property name, details, notes, and terms |
| `sort_by` | string | No | `created_at` | Sort field: `start_date`, `end_date`, `monthly_rent`, `created_at`, or `property_name` |
| `sort_order` | string | No | `desc` | Sort order: `asc` or `desc` |
| `date_from` | date | No | - | Filter rentals starting from this date (Y-m-d format) |
| `date_to` | date | No | - | Filter rentals up to this date (Y-m-d format) |

---

## Response Structure

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Rented properties retrieved successfully",
  "data": {
    "rentals": [
      {
        "id": 1,
        "property": {
          "id": 5,
          "name": "Modern 2-Bedroom Apartment",
          "estimated_monthly": 25000.00,
          "images": ["image1.jpg", "image2.jpg"],
          "details": "Fully furnished apartment with modern amenities",
          "status": "Rented",
          "lot_area": 150.00,
          "floor_area": 120.00,
          "category": {
            "id": 2,
            "name": "Apartment",
            "description": "Multi-unit residential building"
          },
          "location": {
            "id": 3,
            "name": "Metro Manila",
            "address": "Makati City, Metro Manila"
          }
        },
        "rental_details": {
          "monthly_rent": 25000.00,
          "formatted_monthly_rent": "₱25,000.00",
          "security_deposit": 50000.00,
          "formatted_security_deposit": "₱50,000.00",
          "start_date": "2024-01-01",
          "end_date": "2024-12-31",
          "status": "active",
          "remarks": "Active",
          "is_active": true,
          "is_expired": false,
          "remaining_days": 245,
          "total_duration_days": 365,
          "contract_signed_at": "2023-12-28 10:30:00"
        },
        "terms_conditions": "Standard rental agreement terms...",
        "notes": "Client requested early move-in",
        "documents": ["contract.pdf", "id_copy.pdf"],
        "created_at": "2023-12-28 10:30:00",
        "updated_at": "2024-01-15 14:20:00"
      }
    ],
    "statistics": {
      "total_rentals": 5,
      "active_rentals": 1,
      "pending_rentals": 0,
      "expired_rentals": 3,
      "terminated_rentals": 1
    },
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 5,
      "last_page": 1,
      "from": 1,
      "to": 5
    }
  }
}
```

### Error Responses

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

#### 403 Forbidden (Account Deactivated)
```json
{
  "success": false,
  "message": "Your account has been deactivated. Please contact support for assistance."
}
```

#### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "status": [
      "The selected status is invalid."
    ],
    "per_page": [
      "The per page must be between 1 and 100."
    ]
  }
}
```

#### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Failed to retrieve rented properties",
  "error": "Internal server error"
}
```

---

## Usage Examples

### Example 1: Get All Rentals
```bash
curl -X GET "https://your-domain.com/api/client/my-rentals" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Example 2: Get Active Rentals Only
```bash
curl -X GET "https://your-domain.com/api/client/my-rentals?status=active" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Example 3: Search and Filter Rentals
```bash
curl -X GET "https://your-domain.com/api/client/my-rentals?search=apartment&status=active&sort_by=start_date&sort_order=desc" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Example 4: Get Rentals with Date Range
```bash
curl -X GET "https://your-domain.com/api/client/my-rentals?date_from=2024-01-01&date_to=2024-12-31" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### Example 5: Paginated Results
```bash
curl -X GET "https://your-domain.com/api/client/my-rentals?per_page=10&page=2" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

---

## JavaScript/Axios Examples

### Get All Rentals
```javascript
const axios = require('axios');

const getRentals = async () => {
  try {
    const response = await axios.get('https://your-domain.com/api/client/my-rentals', {
      headers: {
        'Authorization': `Bearer ${YOUR_ACCESS_TOKEN}`,
        'Accept': 'application/json'
      }
    });
    
    console.log('Rentals:', response.data.data.rentals);
    console.log('Statistics:', response.data.data.statistics);
  } catch (error) {
    console.error('Error:', error.response.data);
  }
};
```

### Filter Active Rentals
```javascript
const getActiveRentals = async () => {
  try {
    const response = await axios.get('https://your-domain.com/api/client/my-rentals', {
      params: {
        status: 'active',
        sort_by: 'start_date',
        sort_order: 'desc'
      },
      headers: {
        'Authorization': `Bearer ${YOUR_ACCESS_TOKEN}`,
        'Accept': 'application/json'
      }
    });
    
    return response.data.data;
  } catch (error) {
    throw error;
  }
};
```

---

## Rental Status Values

| Status | Description |
|--------|-------------|
| `active` | Currently active rental |
| `pending` | Rental pending approval or start date |
| `expired` | Rental contract has expired |
| `terminated` | Rental terminated before end date |
| `ended` | Rental ended naturally (not renewed) |
| `all` | All rental statuses (default) |

---

## Remarks Field Values

The `remarks` field is automatically calculated based on the rental's end date:

- **"Active"** - More than 5 days until end date
- **"Almost Due Date"** - Exactly 5 days until end date
- **"Due Soon"** - 1-4 days until end date
- **"Due Date Today"** - End date is today
- **"Over Due (X days)"** - Past the end date
- **"No end date set"** - No end date specified

---

## Features

### Security
- ✅ Authentication required (Sanctum)
- ✅ Active account validation
- ✅ Client can only see their own rentals
- ✅ Comprehensive error logging

### Performance
- ✅ Eager loading for related models (property, category, location)
- ✅ Optimized database queries
- ✅ Selective field loading to reduce payload size
- ✅ Pagination support

### Scalability
- ✅ Configurable pagination (1-100 items per page)
- ✅ Efficient database indexing support
- ✅ RESTful design
- ✅ Flexible filtering and sorting

### Data Completeness
- ✅ Full property details
- ✅ Category and location information
- ✅ Rental contract details
- ✅ Financial information (rent, deposit)
- ✅ Date calculations (remaining days, duration)
- ✅ Status and remarks
- ✅ Supporting documents
- ✅ Comprehensive statistics

---

## Production Considerations

1. **Error Handling**: All errors are logged and gracefully handled
2. **Validation**: All input parameters are validated
3. **Rate Limiting**: Consider adding rate limiting to prevent abuse
4. **Caching**: Consider caching statistics for better performance
5. **Monitoring**: Monitor query performance and optimize as needed

---

## Testing Checklist

- [ ] Test with authenticated client
- [ ] Test without authentication (should return 401)
- [ ] Test with deactivated account (should return 403)
- [ ] Test all status filters
- [ ] Test search functionality
- [ ] Test sorting by different fields
- [ ] Test date range filtering
- [ ] Test pagination
- [ ] Test with invalid parameters (should return 422)
- [ ] Test with client who has no rentals
- [ ] Test with client who has multiple rentals

---

## Notes

- All dates are returned in `Y-m-d` format
- All datetimes are returned in `Y-m-d H:i:s` format
- Monetary values are formatted with 2 decimal places
- The endpoint automatically handles soft-deleted properties
- Statistics are calculated in real-time
