import 'user.dart';
import 'vehicle.dart';

class Ride {
  final int id;
  final int passengerId;
  final int? driverId;
  final int? vehicleId;
  
  // Origin
  final double originLatitude;
  final double originLongitude;
  final String originAddress;
  
  // Destination
  final double destinationLatitude;
  final double destinationLongitude;
  final String destinationAddress;
  
  // Ride details
  final String status;
  final DateTime? requestedAt;
  final DateTime? acceptedAt;
  final DateTime? pickupAt;
  final DateTime? dropoffAt;
  final DateTime? completedAt;
  final double? distanceKm;
  final int? durationMinutes;
  final double? fareAmount;
  final String paymentStatus;
  final String? paymentMethod;
  final String? transactionId;
  final String? paymentReference;
  final DateTime? paidAt;
  final Map<String, dynamic>? paymentMetadata;
  
  // Ratings
  final double? driverRating;
  final String? driverComment;
  final double? passengerRating;
  final String? passengerComment;

  // Driver location tracking
  final double? driverCurrentLatitude;
  final double? driverCurrentLongitude;
  final DateTime? driverLastLocationUpdate;
  
  // Relations
  final User? passenger;
  final User? driver;
  final Vehicle? vehicle;
  
  final String createdAt;
  final String updatedAt;

  Ride({
    required this.id,
    required this.passengerId,
    this.driverId,
    this.vehicleId,
    required this.originLatitude,
    required this.originLongitude,
    required this.originAddress,
    required this.destinationLatitude,
    required this.destinationLongitude,
    required this.destinationAddress,
    required this.status,
    this.requestedAt,
    this.acceptedAt,
    this.pickupAt,
    this.dropoffAt,
    this.completedAt,
    this.distanceKm,
    this.durationMinutes,
    this.fareAmount,
    required this.paymentStatus,
    this.paymentMethod,
    this.transactionId,
    this.paymentReference,
    this.paidAt,
    this.paymentMetadata,
    this.driverRating,
    this.driverComment,
    this.passengerRating,
    this.passengerComment,
    this.driverCurrentLatitude,
    this.driverCurrentLongitude,
    this.driverLastLocationUpdate,
    this.passenger,
    this.driver,
    this.vehicle,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Ride.fromJson(Map<String, dynamic> json) {
    return Ride(
      id: json['id'],
      passengerId: json['passenger_id'],
      driverId: json['driver_id'],
      vehicleId: json['vehicle_id'],
      originLatitude: _asDouble(json['origin_latitude']) ?? 0.0,
      originLongitude: _asDouble(json['origin_longitude']) ?? 0.0,
      originAddress: json['origin_address'],
      destinationLatitude: _asDouble(json['destination_latitude']) ?? 0.0,
      destinationLongitude: _asDouble(json['destination_longitude']) ?? 0.0,
      destinationAddress: json['destination_address'],
      status: json['status'],
      requestedAt: json['requested_at'] != null ? DateTime.parse(json['requested_at']) : null,
      acceptedAt: json['accepted_at'] != null ? DateTime.parse(json['accepted_at']) : null,
      pickupAt: json['pickup_at'] != null ? DateTime.parse(json['pickup_at']) : null,
      dropoffAt: json['dropoff_at'] != null ? DateTime.parse(json['dropoff_at']) : null,
      completedAt: json['completed_at'] != null ? DateTime.parse(json['completed_at']) : null,
      distanceKm: _asDouble(json['distance_km']),
      durationMinutes: json['duration_minutes'],
      fareAmount: _asDouble(json['fare_amount']),
      paymentStatus: json['payment_status'],
      paymentMethod: json['payment_method'],
      transactionId: json['transaction_id'],
      paymentReference: json['payment_reference'],
      paidAt: json['paid_at'] != null ? DateTime.parse(json['paid_at']) : null,
      paymentMetadata: json['payment_metadata'],
      driverRating: _asDouble(json['driver_rating']),
      driverComment: json['driver_comment'],
      passengerRating: _asDouble(json['passenger_rating']),
      passengerComment: json['passenger_comment'],
      driverCurrentLatitude: _asDouble(json['driver_current_latitude']),
      driverCurrentLongitude: _asDouble(json['driver_current_longitude']),
      driverLastLocationUpdate: json['driver_last_location_update'] != null ? DateTime.parse(json['driver_last_location_update']) : null,
      passenger: json['passenger'] != null ? User.fromJson(json['passenger']) : null,
      driver: json['driver'] != null ? User.fromJson(json['driver']) : null,
      vehicle: json['vehicle'] != null ? Vehicle.fromJson(json['vehicle']) : null,
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'passenger_id': passengerId,
      'driver_id': driverId,
      'vehicle_id': vehicleId,
      'origin_latitude': originLatitude,
      'origin_longitude': originLongitude,
      'origin_address': originAddress,
      'destination_latitude': destinationLatitude,
      'destination_longitude': destinationLongitude,
      'destination_address': destinationAddress,
      'status': status,
      'requested_at': requestedAt,
      'accepted_at': acceptedAt,
      'pickup_at': pickupAt,
      'dropoff_at': dropoffAt,
      'distance_km': distanceKm,
      'duration_minutes': durationMinutes,
      'fare_amount': fareAmount,
      'payment_status': paymentStatus,
      'payment_method': paymentMethod,
      'transaction_id': transactionId,
      'payment_reference': paymentReference,
      'paid_at': paidAt,
      'payment_metadata': paymentMetadata,
      'driver_rating': driverRating,
      'driver_comment': driverComment,
      'passenger_rating': passengerRating,
      'passenger_comment': passengerComment,
      'passenger': passenger?.toJson(),
      'driver': driver?.toJson(),
      'vehicle': vehicle?.toJson(),
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}

double? _asDouble(dynamic value) {
  if (value == null) return null;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString());
}
