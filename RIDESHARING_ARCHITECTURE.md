# Trotro Ride-Hailing System - Comprehensive Architecture

## System Overview

Transforming the basic trotro system into a full ride-hailing platform similar to Yango/Uber/Bolt with maps, real-time tracking, drivers, and passengers.

## Core Components

### 1. User Management
- **Passengers**: Can book rides, track drivers, rate trips
- **Drivers**: Can accept rides, update location, earn money
- **Admins**: Manage all users, vehicles, and monitor system

### 2. Vehicle Management
- Driver vehicle registration
- Vehicle types (sedan, SUV, minivan, bus)
- Vehicle verification and approval
- Real-time availability status

### 3. Ride Booking System
- **Request Flow**:
  1. Passenger enters pickup and dropoff locations
  2. System calculates fare and distance
  3. Ride request broadcast to nearby drivers
  4. Driver accepts ride
  5. Passenger sees driver approaching
  6. Ride in progress with real-time tracking
  7. Ride completion and payment
  8. Mutual rating system

### 4. Real-Time Features
- **Location Tracking**: WebSocket/Pusher for live updates
- **Driver Matching**: Find nearest available driver
- **ETA Calculation**: Real-time estimated arrival
- **Status Updates**: Live ride status updates

### 5. Payment System
- Multiple payment methods (cash, mobile money, card)
- Fare calculation based on distance/time
- Driver earnings tracking
- Transaction history

### 6. Rating System
- Passenger rates driver (1-5 stars)
- Driver rates passenger (1-5 stars)
- Comments and feedback
- Rating affects future matching

## Database Schema

### Users Table
- id, name, email, password, phone
- role (passenger/driver/admin)
- profile_image, rating, total_rides
- timestamps

### Vehicles Table
- id, driver_id (FK to users)
- make, model, year, license_plate
- color, capacity, vehicle_type
- is_active, vehicle_image
- timestamps

### Rides Table
- id, passenger_id (FK to users)
- driver_id (FK to users, nullable)
- vehicle_id (FK to vehicles, nullable)
- origin_latitude, origin_longitude, origin_address
- destination_latitude, destination_longitude, destination_address
- status (pending/accepted/driver_arrived/in_progress/completed/cancelled)
- requested_at, accepted_at, pickup_at, dropoff_at
- distance_km, duration_minutes, fare_amount
- payment_status (pending/paid/refunded), payment_method
- driver_rating, driver_comment
- passenger_rating, passenger_comment
- timestamps

### BusStops Table (Existing)
- id, name, location, latitude, longitude, area
- Used for predefined pickup/dropoff points

### Routes Table (Existing)
- id, origin_stop_id, destination_stop_id
- route_name, description, estimated_duration
- Used for fixed-route trotro planning

### Fares Table (Existing)
- id, route_id, amount, fare_type
- Fixed fares for predefined routes

## API Endpoints

### Authentication
- POST /api/auth/register - Register new user
- POST /api/auth/login - Login user
- POST /api/auth/logout - Logout user
- GET /api/auth/me - Get current user

### Passenger APIs
- POST /api/rides/request - Request a ride
- GET /api/rides/{id} - Get ride details
- PUT /api/rides/{id}/cancel - Cancel ride
- POST /api/rides/{id}/rate - Rate driver
- GET /api/rides/history - Ride history

### Driver APIs
- POST /api/driver/available - Set availability status
- PUT /api/driver/location - Update current location
- GET /api/driver/rides - Get available ride requests
- POST /api/driver/rides/{id}/accept - Accept ride
- PUT /api/driver/rides/{id}/status - Update ride status
- POST /api/driver/rides/{id}/rate - Rate passenger
- GET /api/driver/earnings - View earnings

### Admin APIs
- GET /api/admin/users - List all users
- GET /api/admin/vehicles - List all vehicles
- GET /api/admin/rides - List all rides
- PUT /api/admin/vehicles/{id}/approve - Approve vehicle

## Mobile Apps

### Passenger App (Flutter)
- **Features**:
  - Google Maps integration
  - Location picker (pickup/dropoff)
  - Real-time driver tracking
  - Ride status updates
  - In-app payment
  - Rating system
  - Ride history
  - Profile management

### Driver App (Flutter)
- **Features**:
  - Toggle online/offline status
  - Receive ride requests
  - Accept/reject rides
  - Navigation to pickup
  - Real-time passenger tracking
  - Earnings dashboard
  - Rating system
  - Vehicle management

## Technology Stack

### Backend
- Laravel 13.x
- Laravel Sanctum (Authentication)
- MySQL/SQLite (Database)
- Pusher/WebSocket (Real-time)
- Google Maps API (Geocoding, Directions)

### Frontend
- Flutter 3.x
- Google Maps Flutter Plugin
- Provider/GetIt (State Management)
- HTTP (API calls)
- Shared Preferences (Local storage)

### Admin Panel
- Laravel Blade + Tailwind CSS
- Existing admin panel extended

## Key Features Comparison

| Feature | Basic Trotro | Ride-Hailing System |
|---------|--------------|---------------------|
| Bus Stops | ✓ | ✓ (Predefined points) |
| Routes | ✓ | ✓ (Dynamic + Fixed) |
| Fares | ✓ | ✓ (Dynamic calculation) |
| Real-time | ✗ | ✓ (Driver tracking) |
| Maps | ✗ | ✓ (Google Maps) |
| Users | ✗ | ✓ (Passengers + Drivers) |
| Vehicles | ✗ | ✓ (Driver vehicles) |
| Ride Booking | ✗ | ✓ (On-demand) |
| Payment | ✗ | ✓ (Multiple methods) |
| Ratings | ✗ | ✓ (Mutual rating) |
| ETA | Fixed | ✓ (Real-time) |

## Implementation Phases

### Phase 1: Core Infrastructure (Current)
- ✅ Database schema (users, vehicles, rides)
- ✅ Models and relationships
- ✅ Authentication system
- ✅ Basic API structure

### Phase 2: Backend APIs
- Ride booking API
- Driver matching algorithm
- Location updates
- Status management
- Rating system

### Phase 3: Maps Integration
- Google Maps API setup
- Geocoding (address to coordinates)
- Distance/duration calculation
- Route optimization

### Phase 4: Real-Time
- WebSocket/Pusher setup
- Live location updates
- Ride status broadcasts
- Driver availability

### Phase 5: Passenger App
- Maps integration
- Ride booking flow
- Driver tracking
- Payment integration
- Rating system

### Phase 6: Driver App
- Availability toggle
- Ride request notifications
- Navigation
- Earnings tracking
- Rating system

### Phase 7: Admin Panel
- User management
- Vehicle approval
- Ride monitoring
- Analytics dashboard

## Cost Estimation

### Google Maps API
- Maps JavaScript API: $200/month credit free
- Directions API: $200/month credit free
- Geocoding API: $200/month credit free
- Dynamic Maps: $200/month credit free

### Real-Time (Pusher)
- Free tier: 100 connections/day
- Paid: $25/month for 100K messages

### Hosting
- Backend: $10-50/month (DigitalOcean, Linode)
- Database: $15/month (managed MySQL)
- CDN: $5-10/month (Cloudflare)

## Next Steps

1. Complete backend API controllers
2. Set up Google Maps API key
3. Implement fare calculation algorithm
4. Create driver matching logic
5. Build passenger app with maps
6. Build driver app
7. Integrate payment system
8. Test end-to-end flow

## Challenges & Solutions

### Challenge 1: Real-time updates
**Solution**: Use Pusher or Laravel Echo with WebSocket

### Challenge 2: Driver matching
**Solution**: Geospatial queries to find nearest available driver

### Challenge 3: GPS accuracy
**Solution**: Use multiple location updates and average them

### Challenge 4: Fare calculation
**Solution**: Base fare + distance rate + time rate + surge pricing

### Challenge 5: Security
**Solution**: API authentication, HTTPS, secure payment processing

This architecture provides a complete ride-hailing platform similar to Yango/Uber/Bolt, specifically tailored for the trotro context in Ghana.
