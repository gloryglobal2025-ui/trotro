import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

class MapLocationPicker extends StatefulWidget {
  final String title;
  final double? initialLat;
  final double? initialLng;
  final Function(double lat, double lng, String address) onLocationSelected;

  const MapLocationPicker({
    super.key,
    required this.title,
    this.initialLat,
    this.initialLng,
    required this.onLocationSelected,
  });

  @override
  State<MapLocationPicker> createState() => _MapLocationPickerState();
}

class _MapLocationPickerState extends State<MapLocationPicker> {
  LatLng? _selectedLocation;
  final _addressController = TextEditingController();
  MapController? _mapController;
  List<Marker> _markers = [];

  @override
  void initState() {
    super.initState();
    if (widget.initialLat != null && widget.initialLng != null) {
      _selectedLocation = LatLng(widget.initialLat!, widget.initialLng!);
      _addressController.text = 'Selected Location';
      _markers.add(
        Marker(
          point: _selectedLocation!,
          width: 40,
          height: 40,
          child: const Icon(
            Icons.location_on,
            color: Colors.red,
            size: 40,
          ),
        ),
      );
    }
  }

  @override
  void dispose() {
    _addressController.dispose();
    _mapController?.dispose();
    super.dispose();
  }

  void _confirmLocation() {
    if (_selectedLocation != null && _addressController.text.trim().isNotEmpty) {
      widget.onLocationSelected(
        _selectedLocation!.latitude,
        _selectedLocation!.longitude,
        _addressController.text.trim(),
      );
      Navigator.pop(context);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a location and enter an address')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        backgroundColor: Colors.orange,
      ),
      body: Column(
        children: [
          Expanded(
            child: FlutterMap(
              mapController: _mapController,
              options: MapOptions(
                initialCenter: _selectedLocation ?? const LatLng(5.6037, -0.1870),
                initialZoom: 13.0,
                onTap: (tapPosition, point) {
                  setState(() {
                    _selectedLocation = point;
                    _addressController.text = 'Selected Location';
                    _markers = [
                      Marker(
                        point: point,
                        width: 40,
                        height: 40,
                        child: const Icon(
                          Icons.location_on,
                          color: Colors.red,
                          size: 40,
                        ),
                      ),
                    ];
                  });
                },
              ),
              children: [
                TileLayer(
                  urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                  userAgentPackageName: 'com.example.trotro_app',
                ),
                MarkerLayer(markers: _markers),
              ],
            ),
          ),
          Container(
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
                Text(
                  'Selected Location',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey.shade700,
                  ),
                ),
                const SizedBox(height: 8),
                if (_selectedLocation != null) ...[
                  Text(
                    'Latitude: ${_selectedLocation!.latitude.toStringAsFixed(6)}',
                    style: const TextStyle(fontSize: 12),
                  ),
                  Text(
                    'Longitude: ${_selectedLocation!.longitude.toStringAsFixed(6)}',
                    style: const TextStyle(fontSize: 12),
                  ),
                  const SizedBox(height: 8),
                ],
                TextField(
                  controller: _addressController,
                  decoration: const InputDecoration(
                    labelText: 'Address Description',
                    border: OutlineInputBorder(),
                    hintText: 'e.g., Circle, Accra',
                  ),
                ),
                const SizedBox(height: 8),
                Card(
                  color: Colors.blue.shade50,
                  child: Padding(
                    padding: const EdgeInsets.all(12.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.info_outline, size: 16, color: Colors.blue.shade700),
                            const SizedBox(width: 8),
                            Text(
                              'Quick Select',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Colors.blue.shade700,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            _buildQuickLocation('Circle', 5.6037, -0.1870),
                            _buildQuickLocation('Makola', 5.5716, -0.0254),
                            _buildQuickLocation('Kaneshie', 5.5704, -0.2468),
                            _buildQuickLocation('Achimota', 5.6438, -0.2334),
                            _buildQuickLocation('Tema', 5.6706, -0.0126),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: _confirmLocation,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.orange,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: const Text('Confirm Location'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickLocation(String name, double lat, double lng) {
    return InkWell(
      onTap: () {
        final location = LatLng(lat, lng);
        setState(() {
          _selectedLocation = location;
          _addressController.text = '$name, Accra';
          _markers = [
            Marker(
              point: location,
              width: 40,
              height: 40,
              child: const Icon(
                Icons.location_on,
                color: Colors.red,
                size: 40,
              ),
            ),
          ];
        });
        _mapController?.move(location, 15.0);
      },
      child: Chip(
        label: Text(name),
        backgroundColor: Colors.blue.shade100,
      ),
    );
  }
}
