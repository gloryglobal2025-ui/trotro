class User {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String role;
  final String? profileImage;
  final String? licenseNumber;
  final String? licenseExpiry;
  final String? licenseImage;
  final String? ghanaCardNumber;
  final String? ghanaCardImage;
  final String verificationStatus;
  final String? verificationNotes;
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
    this.licenseNumber,
    this.licenseExpiry,
    this.licenseImage,
    this.ghanaCardNumber,
    this.ghanaCardImage,
    this.verificationStatus = 'unverified',
    this.verificationNotes,
    required this.rating,
    required this.totalRides,
    required this.createdAt,
    required this.updatedAt,
  });

  bool get isDriver => role == 'driver';

  bool get isVerifiedDriver => isDriver && verificationStatus == 'approved';

  bool get hasSubmittedVerification =>
      licenseNumber != null &&
      licenseNumber!.isNotEmpty &&
      ghanaCardNumber != null &&
      ghanaCardNumber!.isNotEmpty;

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      phone: json['phone'],
      role: json['role'],
      profileImage: json['profile_image_url'] ?? json['profile_image'],
      licenseNumber: json['license_number'],
      licenseExpiry: json['license_expiry'],
      licenseImage: json['license_image_url'] ?? json['license_image'],
      ghanaCardNumber: json['ghana_card_number'],
      ghanaCardImage: json['ghana_card_image_url'] ?? json['ghana_card_image'],
      verificationStatus: json['verification_status'] ?? 'unverified',
      verificationNotes: json['verification_notes'],
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
      'license_number': licenseNumber,
      'license_expiry': licenseExpiry,
      'license_image': licenseImage,
      'ghana_card_number': ghanaCardNumber,
      'ghana_card_image': ghanaCardImage,
      'verification_status': verificationStatus,
      'verification_notes': verificationNotes,
      'rating': rating,
      'total_rides': totalRides,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }
}
