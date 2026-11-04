import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:geolocator/geolocator.dart';
import '../models/ride.dart';
import '../services/api_service.dart';
import '../services/realtime_service.dart';
import '../widgets/photo_tile.dart';

class TripTrackingScreen extends StatefulWidget {
  final Ride ride;

  const TripTrackingScreen({super.key, required this.ride});

  @override
  State<TripTrackingScreen> createState() => _TripTrackingScreenState();
}

class _TripTrackingScreenState extends State<TripTrackingScreen> with RealtimeMixin {
  Ride? _currentRide;
  Position? _currentPosition;
  bool _isLoading = true;
  bool _isTracking = false;
  Timer? _locationTimer;

  @override
  void initState() {
    super.initState();
    _currentRide = widget.ride;
    _startTracking();
    _loadInitialData();
  }

  @override
  void dispose() {
    _locationTimer?.cancel();
    stopRealtimeUpdates();
    super.dispose();
  }

  Future<void> _loadInitialData() async {
    setState(() => _isLoading = false);
  }

  Future<void> _startTracking() async {
    // Check location permissions
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Location services are disabled. Please enable them.')),
        );
      }
      return;
    }

    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Location permissions are denied.')),
          );
        }
        return;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Location permissions are permanently denied.')),
        );
      }
      return;
    }

    setState(() => _isTracking = true);

    // Get initial position
    _getCurrentLocation();

    // Start periodic location updates
    _locationTimer = Timer.periodic(const Duration(seconds: 10), (timer) {
      _getCurrentLocation();
    });

    // Start real-time ride updates
    startRealtimeUpdates(
      rideId: _currentRide!.id.toString(),
      onUpdate: (ride) {
        setState(() {
          _currentRide = ride;
          if (ride.status == 'completed' || ride.status == 'cancelled') {
            _locationTimer?.cancel();
            stopRealtimeUpdates();
          }
        });
      },
    );
  }

  Future<void> _getCurrentLocation() async {
    try {
      Position position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
      );

      setState(() {
        _currentPosition = position;
      });

      // Update driver location on server
      if (_currentRide != null && _currentRide!.driverId != null) {
        try {
          await ApiService.updateDriverLocation(
            _currentRide!.id,
            position.latitude,
            position.longitude,
          );
        } catch (e) {
          // Silently fail location updates
        }
      }
    } catch (e) {
      // Handle location errors
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Trip Tracking'),
        backgroundColor: Colors.green,
        actions: [
          Icon(
            _isTracking ? Icons.location_on : Icons.location_off,
            color: _isTracking ? Colors.white : Colors.grey,
          ),
          const SizedBox(width: 16),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: _buildMap(),
          ),
          _buildRideInfo(),
        ],
      ),
    );
  }

  Widget _buildMap() {
    // Determine map center based on ride status
    LatLng mapCenter;
    double zoom = 13.0;

    if (_currentPosition != null && _currentRide!.status == 'in_progress') {
      mapCenter = LatLng(_currentPosition!.latitude, _currentPosition!.longitude);
      zoom = 15.0;
    } else if (_currentRide!.driverCurrentLatitude != null && _currentRide!.driverCurrentLongitude != null) {
      mapCenter = LatLng(_currentRide!.driverCurrentLatitude!, _currentRide!.driverCurrentLongitude!);
      zoom = 15.0;
    } else {
      mapCenter = LatLng(_currentRide!.originLatitude, _currentRide!.originLongitude);
      zoom = 13.0;
    }

    List<Marker> markers = [];

    // Origin marker
    markers.add(
      Marker(
        point: LatLng(_currentRide!.originLatitude, _currentRide!.originLongitude),
        width: 40,
        height: 40,
        child: const Icon(
          Icons.location_on,
          color: Colors.green,
          size: 40,
        ),
      ),
    );

    // Destination marker
    markers.add(
      Marker(
        point: LatLng(_currentRide!.destinationLatitude, _currentRide!.destinationLongitude),
        width: 40,
        height: 40,
        child: const Icon(
          Icons.flag,
          color: Colors.red,
          size: 40,
        ),
      ),
    );

    // Driver marker (if available)
    if (_currentRide!.driverCurrentLatitude != null && _currentRide!.driverCurrentLongitude != null) {
      markers.add(
        Marker(
          point: LatLng(_currentRide!.driverCurrentLatitude!, _currentRide!.driverCurrentLongitude!),
          width: 50,
          height: 50,
          child: Container(
            decoration: BoxDecoration(
              color: Colors.blue,
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 3),
            ),
            child: const Icon(
              Icons.directions_car,
              color: Colors.white,
              size: 24,
            ),
          ),
        ),
      );
    }

    // Current position marker (if tracking)
    if (_currentPosition != null) {
      markers.add(
        Marker(
          point: LatLng(_currentPosition!.latitude, _currentPosition!.longitude),
          width: 40,
          height: 40,
          child: Container(
            decoration: BoxDecoration(
              color: Colors.orange,
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 3),
            ),
            child: const Icon(
              Icons.my_location,
              color: Colors.white,
              size: 20,
            ),
          ),
        ),
      );
    }

    return FlutterMap(
      options: MapOptions(
        initialCenter: mapCenter,
        initialZoom: zoom,
      ),
      children: [
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'com.example.trotro_app',
        ),
        MarkerLayer(markers: markers),
      ],
    );
  }

  Widget _buildRideInfo() {
    return Container(
      padding: const EdgeInsets.all(16.0),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.3),
            spreadRadius: 2,
            blurRadius: 5,
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: _getStatusColor(_currentRide!.status),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  _currentRide!.status.toUpperCase(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                  ),
                ),
              ),
              const Spacer(),
              if (_currentRide!.fareAmount != null)
                Text(
                  'GHS ${_currentRide!.fareAmount!.toStringAsFixed(2)}',
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Colors.green,
                  ),
                ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              const Icon(Icons.location_on, color: Colors.green, size: 20),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  _currentRide!.originAddress,
                  style: const TextStyle(fontSize: 14),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              const Icon(Icons.flag, color: Colors.red, size: 20),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  _currentRide!.destinationAddress,
                  style: const TextStyle(fontSize: 14),
                ),
              ),
            ],
          ),
          if (_currentRide!.driver != null) ...[
            const SizedBox(height: 12),
            const Divider(),
            const SizedBox(height: 8),
            Row(
              children: [
                PhotoTile(
                  imageUrl: _currentRide!.driver!.profileImage,
                  size: 40,
                  circle: true,
                  fallbackIcon: Icons.person,
                  fallbackColor: Colors.blue.shade700,
                  fallbackBackground: Colors.blue.shade100,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _currentRide!.driver!.name,
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      Row(
                        children: [
                          const Icon(Icons.star, color: Colors.amber, size: 16),
                          const SizedBox(width: 4),
                          Text(_currentRide!.driver!.rating.toStringAsFixed(1)),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (_currentRide!.vehicle != null) ...[
              const SizedBox(height: 10),
              Row(
                children: [
                  PhotoTile(
                    imageUrl: _currentRide!.vehicle!.vehicleImage,
                    size: 40,
                    borderRadius: 10,
                    fallbackIcon: Icons.directions_bus,
                    fallbackColor: Colors.blue.shade700,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      '${_currentRide!.vehicle!.make} ${_currentRide!.vehicle!.model}',
                      style: const TextStyle(fontSize: 14),
                    ),
                  ),
                  Text(
                    _currentRide!.vehicle!.licensePlate,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.grey,
                    ),
                  ),
                ],
              ),
            ],
          ],
          if (_currentPosition != null) ...[
            const SizedBox(height: 12),
            const Divider(),
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.speed, color: Colors.orange, size: 20),
                const SizedBox(width: 8),
                Text(
                  'Tracking active: ${_currentPosition!.latitude.toStringAsFixed(6)}, ${_currentPosition!.longitude.toStringAsFixed(6)}',
                  style: const TextStyle(fontSize: 12, color: Colors.grey),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'pending':
        return Colors.grey;
      case 'accepted':
        return Colors.blue;
      case 'driver_arrived':
        return Colors.orange;
      case 'in_progress':
        return Colors.green;
      case 'completed':
        return Colors.green;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}
