class Fare {
  final int id;
  final int routeId;
  final double amount;
  final String fareType;
  final String? description;
  final bool isActive;
  final String? effectiveDate;
  final String createdAt;
  final String updatedAt;

  Fare({
    required this.id,
    required this.routeId,
    required this.amount,
    required this.fareType,
    this.description,
    required this.isActive,
    this.effectiveDate,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Fare.fromJson(Map<String, dynamic> json) {
    return Fare(
      id: json['id'],
      routeId: json['route_id'],
      amount: json['amount'] != null ? double.tryParse(json['amount'].toString()) ?? 0.0 : 0.0,
      fareType: json['fare_type'],
      description: json['description'],
      isActive: json['is_active'],
      effectiveDate: json['effective_date'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'route_id': routeId,
      'amount': amount,
      'fare_type': fareType,
      'description': description,
      'is_active': isActive,
      'effective_date': effectiveDate,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}
