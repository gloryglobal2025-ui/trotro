import 'bus_stop.dart';
import 'fare.dart';

class BusRoute {
  final int id;
  final int originStopId;
  final int destinationStopId;
  final String routeName;
  final String? description;
  final int? estimatedDuration;
  final BusStop? origin;
  final BusStop? destination;
  final List<Fare>? fares;
  final String createdAt;
  final String updatedAt;

  BusRoute({
    required this.id,
    required this.originStopId,
    required this.destinationStopId,
    required this.routeName,
    this.description,
    this.estimatedDuration,
    this.origin,
    this.destination,
    this.fares,
    required this.createdAt,
    required this.updatedAt,
  });

  factory BusRoute.fromJson(Map<String, dynamic> json) {
    return BusRoute(
      id: json['id'],
      originStopId: json['origin_stop_id'],
      destinationStopId: json['destination_stop_id'],
      routeName: json['route_name'],
      description: json['description'],
      estimatedDuration: json['estimated_duration'],
      origin: json['origin'] != null ? BusStop.fromJson(json['origin']) : null,
      destination: json['destination'] != null ? BusStop.fromJson(json['destination']) : null,
      fares: json['fares'] != null
          ? (json['fares'] as List).map((e) => Fare.fromJson(e)).toList()
          : null,
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'origin_stop_id': originStopId,
      'destination_stop_id': destinationStopId,
      'route_name': routeName,
      'description': description,
      'estimated_duration': estimatedDuration,
      'origin': origin?.toJson(),
      'destination': destination?.toJson(),
      'fares': fares?.map((e) => e.toJson()).toList(),
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}
