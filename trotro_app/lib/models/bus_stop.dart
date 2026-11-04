class BusStop {
  final int id;
  final String name;
  final String? location;
  final double? latitude;
  final double? longitude;
  final String? area;
  final String? imageUrl;
  final String createdAt;
  final String updatedAt;

  BusStop({
    required this.id,
    required this.name,
    this.location,
    this.latitude,
    this.longitude,
    this.area,
    this.imageUrl,
    required this.createdAt,
    required this.updatedAt,
  });

  factory BusStop.fromJson(Map<String, dynamic> json) {
    return BusStop(
      id: json['id'],
      name: json['name'],
      location: json['location'],
      latitude: json['latitude'] != null ? double.tryParse(json['latitude'].toString()) : null,
      longitude: json['longitude'] != null ? double.tryParse(json['longitude'].toString()) : null,
      area: json['area'],
      imageUrl: json['image_url'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'location': location,
      'latitude': latitude,
      'longitude': longitude,
      'area': area,
      'image_url': imageUrl,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}
