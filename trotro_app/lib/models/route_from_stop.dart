class RouteStopFare {
  final int busStopId;
  final String name;
  final int sequence;
  final double fare;

  RouteStopFare({
    required this.busStopId,
    required this.name,
    required this.sequence,
    required this.fare,
  });

  factory RouteStopFare.fromJson(Map<String, dynamic> json) {
    return RouteStopFare(
      busStopId: json['bus_stop_id'],
      name: json['name'] ?? '',
      sequence: json['sequence'] ?? 0,
      fare: _asDouble(json['fare']) ?? 0,
    );
  }
}

class RouteFromStop {
  final int routeId;
  final String routeName;
  final String? originName;
  final int destinationStopId;
  final String? destinationName;
  final int boardingStopId;
  final List<RouteStopFare> stops;

  RouteFromStop({
    required this.routeId,
    required this.routeName,
    this.originName,
    required this.destinationStopId,
    this.destinationName,
    required this.boardingStopId,
    required this.stops,
  });

  factory RouteFromStop.fromJson(Map<String, dynamic> json) {
    final stops = (json['stops'] as List<dynamic>? ?? [])
        .map((item) => RouteStopFare.fromJson(item as Map<String, dynamic>))
        .toList();

    return RouteFromStop(
      routeId: json['route_id'],
      routeName: json['route_name'] ?? '',
      originName: json['origin_name'],
      destinationStopId: json['destination_stop_id'] ?? 0,
      destinationName: json['destination_name'],
      boardingStopId: json['boarding_stop_id'] ?? 0,
      stops: stops,
    );
  }
}

double? _asDouble(dynamic value) {
  if (value == null) return null;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString());
}
