# Trotro Admin Panel Guide

## Overview

The Trotro system includes a web-based admin panel for managing bus stops, routes, and fares. The admin panel is built with Laravel and uses Tailwind CSS for styling.

## Access

- **URL**: http://127.0.0.1:8000/admin
- **Prerequisites**: Laravel server must be running (`php artisan serve`)
- **No authentication required** (for development purposes)

## Features

### Dashboard
- Overview statistics (total bus stops, routes, fares)
- Recent routes list
- Quick navigation to all management sections

### Bus Stops Management
- **View all bus stops** with pagination
- **Create new bus stops** with location, GPS coordinates, and area
- **Edit existing bus stops**
- **Delete bus stops** (with confirmation)
- **View detailed bus stop information**

### Routes Management
- **View all routes** with origin, destination, and duration
- **Create new routes** by selecting origin and destination bus stops
- **Edit existing routes**
- **Delete routes** (with confirmation)
- **View route details** including associated fares

### Fares Management
- **View all fares** with route, type, amount, and status
- **Create new fares** for any route
- **Edit existing fares**
- **Delete fares** (with confirmation)
- **View fare details**

## Navigation

The admin panel has a sidebar with the following sections:
- **Dashboard** - Overview and statistics
- **Bus Stops** - Manage bus stop locations
- **Routes** - Manage route connections
- **Fares** - Manage fare pricing

## Adding a New Bus Stop

1. Navigate to **Bus Stops** > **Add New Bus Stop**
2. Fill in the form:
   - **Name** (required): Bus stop name (e.g., "Kaneshie")
   - **Location**: Description of the location (e.g., "Kaneshie Main Market")
   - **Latitude**: GPS latitude coordinate
   - **Longitude**: GPS longitude coordinate
   - **Area**: Area or region (e.g., "Accra")
3. Click **Create Bus Stop**

## Adding a New Route

1. Navigate to **Routes** > **Add New Route**
2. Fill in the form:
   - **Origin** (required): Select starting bus stop
   - **Destination** (required): Select ending bus stop (must be different from origin)
   - **Route Name** (required): Name for the route (e.g., "Kaneshie to Circle")
   - **Description**: Optional description of the route
   - **Estimated Duration**: Travel time in minutes
3. Click **Create Route**

## Adding a New Fare

1. Navigate to **Fares** > **Add New Fare**
2. Fill in the form:
   - **Route** (required): Select the route this fare applies to
   - **Amount** (required): Fare amount in GHS
   - **Fare Type**: Type of fare (e.g., "regular", "student", "senior")
   - **Description**: Optional description
   - **Active**: Checkbox to enable/disable the fare
   - **Effective Date**: Date when the fare becomes effective
3. Click **Create Fare**

## Tips

- **Route Validation**: You cannot create a route where origin and destination are the same
- **Cascade Delete**: Deleting a route will also delete all associated fares
- **GPS Coordinates**: Optional but recommended for future mapping features
- **Fare Types**: Use consistent naming (e.g., "regular", "student", "senior")

## Sample Data

The system comes pre-seeded with:
- 10 bus stops in Accra area
- 6 routes connecting these stops
- 12 fares (regular and student for each route)

## Troubleshooting

### 404 Errors
- Ensure Laravel server is running on port 8000
- Check that routes are defined in `routes/web.php`

### Form Validation Errors
- All required fields must be filled
- Origin and destination must be different bus stops
- Amount must be a positive number

### Database Errors
- Run `php artisan migrate:fresh --seed` to reset the database
- Check database permissions

## Next Steps

After setting up the admin panel:
1. Add your bus stops
2. Create routes between stops
3. Set up fare structures
4. Test with the Flutter app to verify data sync
