# GeoLogistics

GeoLogistics is a logistics and fleet management platform built with Laravel.

It provides shipment management, vehicle and driver management, real-time fleet tracking, geofencing, payments, ratings, proof of delivery, smart dispatching, notifications, audit logs, and role-based access control.

## Tech Stack

- PHP 8.3+
- Laravel 13
- PostgreSQL
- PostGIS
- Livewire 4
- Tailwind CSS 4
- Leaflet
- Laravel Sanctum
- Laravel Reverb
- Laravel Echo
- Pusher JS
- Laravel Vite
- Scramble API Documentation
- PHPUnit

## Main Features

### Authentication & Authorization

- Customer and driver registration
- Phone/password authentication
- Laravel Sanctum API tokens
- Role-based access control
- Admin, dispatcher, driver, and customer roles
- Protected API resources
- Login and registration rate limiting
- Audit logging for authentication events

### Shipments

- Create and manage shipments
- Shipment tracking numbers
- Customer ownership protection
- Vehicle assignment
- Shipment status workflow
- Status history
- Shipment items
- Smart pricing
- Estimated arrival calculation
- Public shipment tracking

Supported shipment statuses:

```text
pending
assigned
picked_up
in_transit
delivered
cancelled