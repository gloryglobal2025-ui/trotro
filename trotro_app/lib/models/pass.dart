class PassInfo {
  final int id;
  final String code;
  final String status;
  final String approvalChannel;
  final String name;
  final String? phone;
  final double balance;
  final int rideCount;
  final String? lastUsedAt;
  final bool pinRequired;
  final bool pinLocked;
  final int pinAttemptsLeft;

  PassInfo({
    required this.id,
    required this.code,
    required this.status,
    this.approvalChannel = 'device_pin',
    required this.name,
    this.phone,
    required this.balance,
    required this.rideCount,
    this.lastUsedAt,
    this.pinRequired = false,
    this.pinLocked = false,
    this.pinAttemptsLeft = 0,
  });

  bool get isActive => status == 'active';

  bool get usesPhoneApproval => approvalChannel == 'customer_phone';

  factory PassInfo.fromJson(Map<String, dynamic> json) {
    return PassInfo(
      id: json['id'],
      code: json['code'],
      status: json['status'] ?? 'active',
      approvalChannel: json['approval_channel'] ?? 'device_pin',
      name: json['name'] ?? '',
      phone: json['phone'],
      balance: (json['balance'] as num?)?.toDouble() ?? 0,
      rideCount: json['ride_count'] ?? 0,
      lastUsedAt: json['last_used_at'],
      pinRequired: json['pin_required'] == true,
      pinLocked: json['pin_locked'] == true,
      pinAttemptsLeft: (json['pin_attempts_left'] as num?)?.toInt() ?? 0,
    );
  }
}

class PassRouteOption {
  final int id;
  final String routeName;
  final String? origin;
  final String? destination;
  final double? fare;
  final int? fareId;

  PassRouteOption({
    required this.id,
    required this.routeName,
    this.origin,
    this.destination,
    this.fare,
    this.fareId,
  });

  String get label {
    if (origin != null && destination != null) {
      return '$origin → $destination';
    }
    return routeName;
  }

  factory PassRouteOption.fromJson(Map<String, dynamic> json) {
    return PassRouteOption(
      id: json['id'],
      routeName: json['route_name'] ?? '',
      origin: json['origin'],
      destination: json['destination'],
      fare: (json['fare'] as num?)?.toDouble(),
      fareId: json['fare_id'],
    );
  }
}

class PassRecentRide {
  final int id;
  final String? destination;
  final double fare;
  final String? createdAt;

  PassRecentRide({
    required this.id,
    this.destination,
    required this.fare,
    this.createdAt,
  });

  factory PassRecentRide.fromJson(Map<String, dynamic> json) {
    return PassRecentRide(
      id: json['id'],
      destination: json['destination_address'],
      fare: (json['fare_amount'] as num?)?.toDouble() ?? 0,
      createdAt: json['created_at'],
    );
  }
}

class PassLookupResult {
  final PassInfo pass;
  final List<PassRouteOption> routes;
  final List<PassRecentRide> recentRides;

  PassLookupResult({
    required this.pass,
    required this.routes,
    required this.recentRides,
  });

  factory PassLookupResult.fromJson(Map<String, dynamic> json) {
    return PassLookupResult(
      pass: PassInfo.fromJson(json['pass'] as Map<String, dynamic>),
      routes: (json['routes'] as List<dynamic>? ?? [])
          .map((e) => PassRouteOption.fromJson(e as Map<String, dynamic>))
          .toList(),
      recentRides: (json['recent_rides'] as List<dynamic>? ?? [])
          .map((e) => PassRecentRide.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class PassChargeResult {
  final String message;
  final PassInfo pass;
  final bool driverCredited;
  final int? rideId;

  PassChargeResult({
    required this.message,
    required this.pass,
    required this.driverCredited,
    this.rideId,
  });

  factory PassChargeResult.fromJson(Map<String, dynamic> json) {
    return PassChargeResult(
      message: json['message'] ?? 'Charged.',
      pass: PassInfo.fromJson(json['pass'] as Map<String, dynamic>),
      driverCredited: json['driver_credited'] == true,
      rideId: (json['ride'] as Map<String, dynamic>?)?['id'],
    );
  }
}

class PassChargeException implements Exception {
  final String message;
  final String? code;
  final int? attemptsLeft;
  final int? retryAfterMinutes;

  PassChargeException({
    required this.message,
    this.code,
    this.attemptsLeft,
    this.retryAfterMinutes,
  });

  bool get isWrongPin => code == 'pin_invalid';

  bool get isPinRequired => code == 'pin_required';

  bool get isLocked => code == 'pin_locked';

  @override
  String toString() => message;
}

class PassChargeRequestInfo {
  final int id;
  final String status;
  final String channel;
  final double amount;
  final String? destination;
  final String? customerPhone;
  final int secondsLeft;
  final int? rideId;
  final String? failureReason;

  PassChargeRequestInfo({
    required this.id,
    required this.status,
    required this.channel,
    required this.amount,
    this.destination,
    this.customerPhone,
    required this.secondsLeft,
    this.rideId,
    this.failureReason,
  });

  bool get isPending => status == 'pending';

  bool get isCompleted => status == 'completed';

  bool get isDeclined => status == 'declined';

  bool get isExpired => status == 'expired';

  bool get isCancelled => status == 'cancelled';

  bool get isFailed => status == 'failed';

  bool get isFinished => !isPending;

  factory PassChargeRequestInfo.fromJson(Map<String, dynamic> json) {
    return PassChargeRequestInfo(
      id: json['id'],
      status: json['status'] ?? 'pending',
      channel: json['channel'] ?? 'sms',
      amount: (json['amount'] as num?)?.toDouble() ?? 0,
      destination: json['destination'],
      customerPhone: json['customer_phone'],
      secondsLeft: (json['seconds_left'] as num?)?.toInt() ?? 0,
      rideId: json['ride_id'],
      failureReason: json['failure_reason'],
    );
  }
}

class PassChargeRequestStatus {
  final String message;
  final PassChargeRequestInfo request;
  final PassInfo? pass;

  PassChargeRequestStatus({
    required this.message,
    required this.request,
    this.pass,
  });

  factory PassChargeRequestStatus.fromJson(Map<String, dynamic> json) {
    return PassChargeRequestStatus(
      message: json['message'] ?? '',
      request: PassChargeRequestInfo.fromJson(
        json['request'] as Map<String, dynamic>,
      ),
      pass: json['pass'] is Map<String, dynamic>
          ? PassInfo.fromJson(json['pass'] as Map<String, dynamic>)
          : null,
    );
  }
}
