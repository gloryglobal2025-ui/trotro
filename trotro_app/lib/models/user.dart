class User {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String role;
  final String? profileImage;
  final double rating;
  final int totalRides;
  final String createdAt;
  final String updatedAt;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    required this.role,
    this.profileImage,
    required this.rating,
    required this.totalRides,
    required this.createdAt,
    required this.updatedAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      phone: json['phone'],
      role: json['role'],
      profileImage: json['profile_image_url'] ?? json['profile_image'],
      rating: json['rating'] is num
          ? (json['rating'] as num).toDouble()
          : double.tryParse('${json['rating']}') ?? 5.0,
      totalRides: json['total_rides'] ?? 0,
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'phone': phone,
      'role': role,
      'profile_image': profileImage,
      'rating': rating,
      'total_rides': totalRides,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}
