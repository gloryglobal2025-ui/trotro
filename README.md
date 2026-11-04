# Trotro System

A bus transportation system with point-to-point destination tracking and fare calculation, built with Laravel (backend) and Flutter (frontend).

## Features

- **Bus Stop Management**: Add, view, and manage bus stops with location data
- **Route Planning**: Search for routes between any two bus stops
- **Fare Calculation**: Automatic fare calculation based on route duration
- **Multiple Fare Types**: Support for regular, student, and other fare categories
- **Mobile App**: Flutter app for easy route planning and fare checking
- **Admin Panel**: Web-based admin interface for managing all data

## Project Structure

```
trotro/
├── backend/              # Laravel API & Admin Panel
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── Api/           # API Controllers
│   │   │   │   ├── BusStopController.php
│   │   │   │   ├── RouteController.php
│   │   │   │   └── FareController.php
│   │   │   └── Admin/         # Admin Controllers
│   │   │       ├── DashboardController.php
│   │   │       ├── BusStopController.php
│   │   │       ├── RouteController.php
│   │   │       └── FareController.php
│   │   └── Models/
│   │       ├── BusStop.php
│   │       ├── Route.php
│   │       └── Fare.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── resources/views/admin/  # Admin Panel Views
│   │   ├── bus-stops/
│   │   ├── routes/
│   │   └── fares/
│   └── routes/
│       ├── api.php          # API Routes
│       └── web.php          # Admin Panel Routes
└── trotro_app/           # Flutter Mobile App
    ├── lib/
    │   ├── models/
    │   │   ├── bus_stop.dart
    │   │   ├── route.dart
    │   │   └── fare.dart
    │   ├── services/
    │   │   └── api_service.dart
    │   └── main.dart
    └── pubspec.yaml
```

## Prerequisites

- PHP 8.5+ (installed at C:\PHP)
- Composer
- Laravel 13.x
- Flutter 3.47+
- SQLite (included with Laravel)

## Installation

### Backend Setup

1. Navigate to the backend directory:
```bash
cd backend
```

2. Install dependencies (already done):
```bash
composer install
```

3. Run migrations and seeders:
```bash
php artisan migrate:fresh --seed
```

4. Start the Laravel server:
```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`

### Frontend Setup

1. Navigate to the Flutter app directory:
```bash
cd trotro_app
```

2. Install dependencies (already done):
```bash
flutter pub get
```

3. Run the app:
```bash
flutter run
```

### Admin Panel

The admin panel is accessible at http://127.0.0.1:8000/admin when the Laravel server is running.

Features:
- Dashboard with statistics
- Manage bus stops (CRUD operations)
- Manage routes (CRUD operations)
- Manage fares (CRUD operations)

See ADMIN_GUIDE.md for detailed admin panel documentation.

## API Endpoints

### Bus Stops
- `GET /api/bus-stops` - List all bus stops
- `POST /api/bus-stops` - Create a new bus stop
- `GET /api/bus-stops/{id}` - Get a specific bus stop
- `PUT /api/bus-stops/{id}` - Update a bus stop
- `DELETE /api/bus-stops/{id}` - Delete a bus stop

### Routes
- `GET /api/routes` - List all routes
- `POST /api/routes` - Create a new route
- `GET /api/routes/{id}` - Get a specific route
- `PUT /api/routes/{id}` - Update a route
- `DELETE /api/routes/{id}` - Delete a route
- `GET /api/routes/search?origin_id={id}&destination_id={id}` - Search routes between stops

### Fares
- `GET /api/fares` - List all fares
- `POST /api/fares` - Create a new fare
- `GET /api/fares/{id}` - Get a specific fare
- `PUT /api/fares/{id}` - Update a fare
- `DELETE /api/fares/{id}` - Delete a fare
- `GET /api/fares/route/{routeId}` - Get fares for a specific route

## Database Schema

### Bus Stops
- id
- name
- location (optional)
- latitude (optional)
- longitude (optional)
- area (optional)
- timestamps

### Routes
- id
- origin_stop_id (foreign key to bus_stops)
- destination_stop_id (foreign key to bus_stops)
- route_name
- description (optional)
- estimated_duration (in minutes, optional)
- timestamps

### Fares
- id
- route_id (foreign key to routes)
- amount
- fare_type (regular, student, etc.)
- description (optional)
- is_active
- effective_date (optional)
- timestamps

## Sample Data

The application comes pre-seeded with:
- 10 bus stops in Accra area (Kaneshie, Circle, Makola, Tema Station, Madina, etc.)
- 6 routes connecting these stops
- Fares calculated based on route duration (2 GHS base + 0.10 GHS per minute)

## Usage

1. Start the Laravel backend server
2. Run the Flutter app
3. Select origin and destination bus stops
4. Click "Search Routes" to find available routes
5. View route details including fare information

## Development

### Adding New Bus Stops

```php
use App\Models\BusStop;

BusStop::create([
    'name' => 'New Location',
    'location' => 'Description',
    'latitude' => 5.6000,
    'longitude' => -0.2000,
    'area' => 'Accra',
]);
```

### Adding New Routes

```php
use App\Models\Route;

Route::create([
    'origin_stop_id' => 1,
    'destination_stop_id' => 2,
    'route_name' => 'Route Name',
    'description' => 'Route description',
    'estimated_duration' => 20,
]);
```

## Troubleshooting

### Laravel Server Issues
- Ensure PHP is in your PATH
- Check that port 8000 is not in use
- Verify database permissions

### Flutter Connection Issues
- Ensure Laravel server is running on `http://127.0.0.1:8000`
- Check API base URL in `lib/services/api_service.dart`
- Verify network permissions

## License

This project is open source and available for educational purposes.
