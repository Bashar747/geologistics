# GeoLogistics API Documentation

## Overview

The GeoLogistics API provides endpoints for authentication, shipment management, fleet management, drivers, geofences, payments, ratings, notifications, public shipment tracking, and audit logs.

The API is built with Laravel and uses Laravel Sanctum for authenticated API access.

---

## Base URL

For local development:

```text
http://127.0.0.1:8001/api
```

All examples below assume this base URL.

---

## Authentication

Authenticated endpoints use Laravel Sanctum bearer tokens.

Use the returned token in the `Authorization` header:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

### Register

```http
POST /register
```

Creates a new customer or driver account.

Request:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+123456789",
  "password": "password123",
  "role": "customer",
  "device_name": "web"
}
```

Allowed roles:

- `customer`
- `driver`

Returns HTTP `201` with the created user and Sanctum token.

### Login

```http
POST /login
```

Request:

```json
{
  "phone": "+123456789",
  "password": "password123",
  "device_name": "web"
}
```

Returns the authenticated user and Sanctum token.

Invalid credentials return HTTP `401`.

### Logout

```http
POST /logout
```

**Authentication required.**

Revokes the current Sanctum token.

### Current User

```http
GET /me
```

**Authentication required.**

Returns the currently authenticated user.

---

## Shipments

### List Shipments

```http
GET /shipments
```

**Authentication required.**

Results are scoped according to the authenticated user's role.

- Admin/dispatcher: general shipment access.
- Customer: own shipments.
- Driver: shipments assigned to their active vehicle.

### Create Shipment

```http
POST /shipments
```

Request example:

```json
{
  "pickup_lat": 52.0907,
  "pickup_lng": 5.1214,
  "dropoff_lat": 52.3676,
  "dropoff_lng": 4.9041,
  "items": [
    {
      "description": "Package",
      "weight_kg": 5,
      "dimensions": "30x20x15",
      "quantity": 1,
      "fragile": false
    }
  ]
}
```

A tracking number is generated automatically using the `GL-` prefix.

New shipments start with `pending`.

### Show Shipment

```http
GET /shipments/{shipment}
```

Returns shipment details, customer, vehicle, items, status history, payment, and rating according to authorization rules.

### Update Shipment

```http
PUT /shipments/{shipment}
```

Supported statuses:

```text
pending
assigned
picked_up
in_transit
delivered
cancelled
```

Driver delivery progression:

```text
assigned -> picked_up -> in_transit -> delivered
```

Each status change creates a status-history record.

### Assign Shipment

```http
POST /shipments/{shipment}/assign
```

Request:

```json
{
  "vehicle_id": 1
}
```

The shipment is assigned to the vehicle and moved to `assigned`.

### Delete Shipment

```http
DELETE /shipments/{shipment}
```

Only authorized admin/dispatcher users can delete shipments, and only `pending` shipments can be deleted.

### Shipment Payment

```http
GET /shipments/{shipment}/payment
```

Returns the shipment payment when available.

### Shipment Rating

```http
POST /shipments/{shipment}/rating
```

Customers can rate their own delivered shipment once.

```json
{
  "score": 5,
  "comment": "Great delivery service."
}
```

Score must be between `1` and `5`.

---

## Vehicles

### List Vehicles

```http
GET /vehicles
```

Returns vehicles according to the authenticated user's access.

Vehicle data can include:

- plate number
- model
- type
- status
- last location
- current driver

### Create Vehicle

```http
POST /vehicles
```

Request:

```json
{
  "plate_number": "428-PGN",
  "model": "Mercedes Sprinter",
  "type": "van"
}
```

New vehicles start with `idle`.

### Show Vehicle

```http
GET /vehicles/{vehicle}
```

Returns vehicle details, active assignment information, and recent shipments.

### Update Vehicle

```http
PUT /vehicles/{vehicle}
```

Supported statuses:

```text
idle
in_transit
maintenance
offline
```

### Delete Vehicle

```http
DELETE /vehicles/{vehicle}
```

Admin and dispatcher users can delete vehicles according to authorization rules.

### Update Vehicle Location

```http
POST /vehicles/{vehicle}/location
```

**Driver authentication required.**

A driver can update only their active assigned vehicle.

Request:

```json
{
  "lat": 44.3661,
  "lng": 33.3152,
  "speed": 45,
  "heading": 180
}
```

The location is stored as a PostGIS point, a location log is created, and a real-time `location.updated` event is broadcast.

---

## Drivers

### List Drivers

```http
GET /drivers
```

**Admin/dispatcher access.**

Returns driver profiles and active vehicle assignments.

### Show Driver

```http
GET /drivers/{driver}
```

Returns driver information according to role-based authorization.

### Assign Vehicle

```http
POST /drivers/assign
```

Request:

```json
{
  "driver_id": 65,
  "vehicle_id": 1
}
```

Creates an active vehicle assignment.

### Unassign Driver

```http
POST /drivers/unassign
```

Request:

```json
{
  "driver_id": 65
}
```

Deactivates the active vehicle assignment.

### Update Driver Status

```http
PUT /drivers/{driver}/status
```

Supported statuses:

```text
available
on_duty
suspended
```

---

## Geofences

### List Geofences

```http
GET /geofences
```

Returns paginated geofences.

### Create Geofence

```http
POST /geofences
```

Supported types:

```text
warehouse
restricted_zone
delivery_area
```

Request:

```json
{
  "name": "Main Warehouse",
  "type": "warehouse",
  "points": [
    {
      "lat": 52.0900,
      "lng": 5.1200
    },
    {
      "lat": 52.0910,
      "lng": 5.1220
    },
    {
      "lat": 52.0890,
      "lng": 5.1230
    }
  ]
}
```

The polygon is stored as PostGIS geometry.

### Show Geofence

```http
GET /geofences/{geofence}
```

Returns the geofence and stored polygon.

### Update Geofence

```http
PUT /geofences/{geofence}
```

Updates the geofence name/type. The current API operation does not update polygon geometry.

### Delete Geofence

```http
DELETE /geofences/{geofence}
```

Deletes the geofence according to authorization rules.

### Check Point

```http
POST /geofences/check-point
```

Request:

```json
{
  "lat": 52.0907,
  "lng": 5.1214
}
```

Returns matching geofences and their count.

### Check Vehicle Geofences

```http
POST /vehicles/{vehicle}/geofences/check
```

Checks the vehicle's current location against configured geofences.

Access is role-aware:

- Admin/dispatcher: any vehicle
- Driver: active assigned vehicle
- Customer: vehicle associated with their shipments

---

## Payments

### Create Payment

```http
POST /shipments/{shipment}/payment
```

Customers can create a payment for their own shipment.

Request:

```json
{
  "method": "cash"
}
```

Supported methods:

```text
cash
card
wallet
```

The amount comes from the shipment's `total_amount`.

- `cash`: paid immediately
- `card`: pending
- `wallet`: pending

### Confirm Payment

```http
POST /payments/{payment}/confirm
```

**Admin/dispatcher access.**

Confirms a pending payment.

### Refund Payment

```http
POST /payments/{payment}/refund
```

**Admin/dispatcher access.**

Refunds a paid payment.

---

## Ratings

### Get Rating

```http
GET /shipments/{shipment}/rating
```

Returns the shipment rating when available.

### Create Rating

```http
POST /shipments/{shipment}/rating
```

Request:

```json
{
  "score": 5,
  "comment": "Excellent service."
}
```

The driver's average rating is recalculated after a new rating.

---

## Notifications

### List Notifications

```http
GET /notifications
```

Admin/dispatcher users can access notifications generally. Other users see their own notifications.

### Show Notification

```http
GET /notifications/{notification}
```

Returns the notification when authorized.

### Create Notification

```http
POST /notifications
```

**Admin/dispatcher access.**

Request:

```json
{
  "user_id": 65,
  "channel": "push",
  "message": "Your shipment has been assigned."
}
```

Supported channels:

```text
sms
push
email
```

The current implementation simulates sending and stores the notification as sent.

### Retry Notification

```http
POST /notifications/{notification}/retry
```

**Admin/dispatcher access.**

Retries a failed notification.

---

## Public Tracking

### Track Shipment

```http
GET /track/{trackingNumber}
```

No authentication is required.

Returns:

- tracking number
- shipment status
- pickup location
- dropoff location
- estimated arrival
- shipment items
- status history
- current vehicle location when available

The endpoint is rate limited.

---

## Audit Logs

### List Audit Logs

```http
GET /audit-logs
```

**Admin access.**

Supported filters:

- `user_id`
- `action`
- `entity_type`
- `from_date`
- `to_date`

Results are ordered by newest records first and paginated.

### Show Audit Log

```http
GET /audit-logs/{auditLog}
```

**Admin access.**

Returns the audit log entry and related user.

---

## API Resources

### User

```text
id
name
email
phone
role
driver_profile
created_at
```

### Vehicle

```text
id
plate_number
model
type
status
last_location
current_driver
created_at
```

### Shipment

```text
id
tracking_number
status
pickup_location
dropoff_location
estimated_arrival
total_amount
customer
vehicle
items
status_history
payment
rating
created_at
```

### Shipment Item

```text
id
description
weight_kg
dimensions
quantity
fragile
```

### Shipment Status History

```text
status
note
changed_by
created_at
```

### Payment

```text
id
amount
method
status
paid_at
```

### Rating

```text
id
score
comment
rated_by
created_at
```

### Geofence

```text
id
name
type
area_polygon
```

### Notification

```text
id
channel
message
status
sent_at
```

### Audit Log

```text
id
action
entity_type
entity_id
metadata
user
created_at
```

---

## Authorization

The API uses Laravel Sanctum together with application policies and role checks.

Main roles:

```text
admin
dispatcher
driver
customer
```

Examples:

- Customers are restricted to their own shipments and related data.
- Drivers are restricted to shipments assigned to their active vehicle.
- Drivers can update location only for their active assigned vehicle.
- Admins and dispatchers have operational management access.
- Audit logs are restricted to administrators.
- Public shipment tracking does not require authentication.

---

## HTTP Status Codes

Common responses:

```text
200 OK
201 Created
401 Unauthorized
403 Forbidden
404 Not Found
422 Unprocessable Entity
```

Validation failures normally return `422`.

Authentication failures return `401`.

Authorization failures return `403`.

Missing resources return `404`.

---

## Real-Time Location Updates

Vehicle location updates use Laravel Reverb.

Event:

```text
location.updated
```

Channels:

```text
private-vehicle.{vehicleId}
private-fleet
```

Fleet-wide access is restricted to admin and dispatcher users.

The event payload can contain:

```text
vehicle_id
plate_number
driver_name
latitude
longitude
speed
status
current_shipment_number
updated_at
location
```

Laravel Echo subscribes to these private channels and updates the frontend map without a page refresh.

---

## Geospatial Data

GeoLogistics uses PostgreSQL with PostGIS.

Geospatial functionality includes:

- vehicle locations
- pickup locations
- dropoff locations
- polygon geofences
- point-in-polygon geofence checks

---

## Routing

GeoLogistics uses GraphHopper for route calculation.

The routing service accepts:

```text
from latitude
from longitude
to latitude
to longitude
```

and requests a car route with calculated points.

The GraphHopper API key is stored in environment configuration and is not exposed in API responses.

---

## Rate Limiting

The public authentication and tracking endpoints are rate limited.

Current limits:

```text
/register   10 requests per minute
/login      10 requests per minute
/track/*    30 requests per minute
```

---

## Security Notes

- Do not commit real API keys or application secrets.
- Store environment-specific secrets in `.env`.
- Use Sanctum bearer tokens for protected API requests.
- Public tracking exposes only public shipment information.
- Role and ownership checks protect private resources.
- Driver location updates require an active vehicle assignment.

---

## Example Authenticated Request

```bash
curl http://127.0.0.1:8001/api/shipments   -H "Authorization: Bearer YOUR_TOKEN"   -H "Accept: application/json"
```