# Quick Start Guide

## Current Status

✅ **Laravel Backend** - Running on http://127.0.0.1:8000
✅ **Flutter App** - Running on Chrome
✅ **CORS Enabled** - Cross-origin requests are now allowed
✅ **API Connected** - Flutter app is successfully fetching data

## Using the App

1. The Flutter app should be open in your Chrome browser
2. You'll see dropdown menus for:
   - **Origin** - Select your starting bus stop (should be populated with bus stops)
   - **Destination** - Select your destination bus stop (should be populated with bus stops)

3. Available bus stops include:
   - Kaneshie
   - Circle
   - Makola
   - Tema Station
   - Madina
   - Achimota
   - Lapaz
   - Kasoa
   - Spintex
   - East Legon

4. Click **"Search Routes"** to find available routes
5. View route details including:
   - Route name and description
   - Estimated travel time
   - Fare information (regular and student fares)

## Troubleshooting

### If dropdowns are empty:
- Ensure Laravel server is running on port 8000
- Check browser console for CORS errors
- Refresh the page (hot reload with 'r' in Flutter terminal)

### If no routes found:
- Select different origin and destination
- Ensure they are different stops
- Check that routes exist in the database

## Testing the API

You can also test the API directly using curl or a tool like Postman:

### Get all bus stops
```bash
curl http://127.0.0.1:8000/api/bus-stops
```

### Get all routes
```bash
curl http://127.0.0.1:8000/api/routes
```

### Search for routes (example: Kaneshie to Circle)
```bash
curl "http://127.0.0.1:8000/api/routes/search?origin_id=1&destination_id=2"
```

### Get fares for a specific route
```bash
curl http://127.0.0.1:8000/api/fares/route/1
```

## Stopping the Servers

To stop the Laravel server:
- Press Ctrl+C in the terminal where it's running

To stop the Flutter app:
- Press 'q' in the Flutter terminal, or
- Close the Chrome browser tab

## Next Steps

You can now:
1. Add more bus stops via the API
2. Create new routes between stops
3. Add custom fare structures
4. Extend the Flutter app with more features

See README.md for detailed documentation.
