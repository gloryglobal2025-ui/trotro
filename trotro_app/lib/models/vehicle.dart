import 'user.dart';

class Vehicle {
  final int id;
  final int driverId;
  final String make;
  final String model;
  final String year;
  final String licensePlate;
  final String color;
  final int capacity;
  final String vehicleType;
  final bool isActive;
  final String? vehicleImage;
  final User? driver;
  final String createdAt;
  final String updatedAt;

  Vehicle({
    required this.id,
    required this.driverId,
    required this.make,
    required this.model,
    required this.year,
    required this.licensePlate,
    required this.color,
    required this.capacity,
    required this.vehicleType,
    required this.isActive,
    this.vehicleImage,
    this.driver,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Vehicle.fromJson(Map<String, dynamic> json) {
    return Vehicle(
      id: json['id'],
      driverId: json['driver_id'],
      make: json['make'],
      model: json['model'],
      year: json['year'],
      licensePlate: json['license_plate'],
      color: json['color'],
      capacity: json['capacity'],
      vehicleType: json['vehicle_type'],
      isActive: json['is_active'],
      vehicleImage: json['vehicle_image_url'] ?? json['vehicle_image'],
      driver: json['driver'] != null ? User.fromJson(json['driver']) : null,
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'driver_id': driverId,
      'make': make,
      'model': model,
      'year': year,
      'license_plate': licensePlate,
      'color': color,
      'capacity': capacity,
      'vehicle_type': vehicleType,
      'is_active': isActive,
      'vehicle_image': vehicleImage,
      'driver': driver?.toJson(),
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}
