import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/bus_stop.dart';
import '../models/route.dart' as trotro;
import '../models/route_from_stop.dart';
import '../models/fare.dart';
import '../models/user.dart';
import '../models/vehicle.dart';
import '../models/ride.dart';
import '../models/wallet.dart';
import '../models/pass.dart';
import 'session_store.dart';

class ApiService {
  // Override at build time:
  // flutter build apk --dart-define=API_BASE_URL=https://trotro.digitalsecuritymarket.com/api
  // 10.0.2.2 is the Android emulator's host machine.
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://trotro.digitalsecuritymarket.com/api',
  );

  static String? _authToken;
  static User? currentUser;

  static bool get hasToken => _authToken != null && _authToken!.isNotEmpty;

  static Future<void> restoreSession() async {
    _authToken = await SessionStore.readToken();
  }

  static Future<void> setAuthToken(String? token) async {
    _authToken = (token == null || token.isEmpty) ? null : token;
    if (_authToken == null) {
      currentUser = null;
      await SessionStore.clearToken();
      await SessionStore.clearRole();
    } else {
      await SessionStore.writeToken(_authToken!);
    }
  }

  static Map<String, String> get _headers {
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (_authToken != null) {
      headers['Authorization'] = 'Bearer $_authToken';
    }
    return headers;
  }

  // Auth APIs
  static Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    required String phone,
    required String role,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/register'),
      headers: _headers,
      body: jsonEncode({
        'name': name,
        'email': email,
        'password': password,
        'phone': phone,
        'role': role,
      }),
    );

    if (response.statusCode == 201) {
      final data = json.decode(response.body);
      await _applySession(data);
      return data;
    } else {
      throw Exception(_errorMessage(response, 'Registration failed'));
    }
  }

  static Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: _headers,
      body: jsonEncode({
        'email': email,
        'password': password,
      }),
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      await _applySession(data);
      return data;
    } else {
      throw Exception(_errorMessage(response, 'Login failed'));
    }
  }

  static Future<void> _applySession(Map<String, dynamic> data) async {
    await setAuthToken(data['token']?.toString());
    final userJson = data['user'];
    if (userJson is Map<String, dynamic>) {
      currentUser = User.fromJson(userJson);
      await SessionStore.writeRole(currentUser!.role);
    }
  }

  static Future<User> getCurrentUser() async {
    final response = await http.get(
      Uri.parse('$baseUrl/auth/me'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      final user = User.fromJson(json.decode(response.body));
      currentUser = user;
      await SessionStore.writeRole(user.role);
      return user;
    }

    if (response.statusCode == 401) {
      await setAuthToken(null);
    }

    throw Exception(_errorMessage(response, 'Failed to get user'));
  }

  static Future<void> logout() async {
    try {
      await http.post(
        Uri.parse('$baseUrl/auth/logout'),
        headers: _headers,
      );
    } catch (_) {
      // Local sign-out must still succeed offline.
    } finally {
      await setAuthToken(null);
    }
  }

  static Future<User> updateProfile({
    required String name,
    required String email,
    String? phone,
  }) async {
    final response = await http.patch(
      Uri.parse('$baseUrl/auth/profile'),
      headers: _headers,
      body: jsonEncode({
        'name': name,
        'email': email,
        'phone': phone,
      }),
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      final userJson = data is Map<String, dynamic> ? data['user'] : null;
      if (userJson is Map<String, dynamic>) {
        currentUser = User.fromJson(userJson);
        await SessionStore.writeRole(currentUser!.role);
        return currentUser!;
      }
    }

    throw Exception(_errorMessage(response, 'Could not update your profile'));
  }

  static Future<String> uploadProfilePhoto(
    List<int> bytes, {
    required String filename,
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('$baseUrl/auth/photo'),
    );
    request.headers['Accept'] = 'application/json';
    if (_authToken != null) {
      request.headers['Authorization'] = 'Bearer $_authToken';
    }
    request.files.add(
      http.MultipartFile.fromBytes('photo', bytes, filename: filename),
    );

    final streamed = await request.send();
    final response = await http.Response.fromStream(streamed);

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data is Map<String, dynamic> && data['profile_image_url'] is String) {
        return data['profile_image_url'] as String;
      }
    }

    throw Exception(_errorMessage(response, 'Could not upload your photo'));
  }

  // Bus Stops
  static Future<List<BusStop>> getBusStops() async {
    final response = await http.get(
      Uri.parse('$baseUrl/bus-stops'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      List<dynamic> data = json.decode(response.body);
      return data.map((json) => BusStop.fromJson(json)).toList();
    } else {
      throw Exception('Failed to load bus stops');
    }
  }

  // Routes
  static Future<List<trotro.BusRoute>> getRoutes() async {
    final response = await http.get(
      Uri.parse('$baseUrl/routes'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      List<dynamic> data = json.decode(response.body);
      return data.map((json) => trotro.BusRoute.fromJson(json)).toList();
    } else {
      throw Exception('Failed to load routes');
    }
  }

  static Future<List<trotro.BusRoute>> searchRoutes(int originId, int destinationId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/routes/search?origin_id=$originId&destination_id=$destinationId'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      List<dynamic> data = json.decode(response.body);
      return data.map((json) => trotro.BusRoute.fromJson(json)).toList();
    } else {
      throw Exception('Failed to search routes');
    }
  }

  static Future<List<RouteFromStop>> getRoutesFromStop(int stopId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/routes/from-stop/$stopId'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      List<dynamic> data = json.decode(response.body);
      return data
          .map((json) => RouteFromStop.fromJson(json as Map<String, dynamic>))
          .toList();
    } else {
      throw Exception('Failed to load route stops');
    }
  }

  // Fares
  static Future<List<Fare>> getFaresByRoute(int routeId) async {    final response = await http.get(
      Uri.parse('$baseUrl/fares/route/$routeId'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      List<dynamic> data = json.decode(response.body);
      return data.map((json) => Fare.fromJson(json)).toList();
    } else {
      throw Exception('Failed to load fares');
    }
  }

  // Rides
  static Future<Ride> requestRide({
    required double originLatitude,
    required double originLongitude,
    required String originAddress,
    required double destinationLatitude,
    required double destinationLongitude,
    required String destinationAddress,
    required String paymentMethod,
    int? routeId,
    int? originStopId,
    int? destinationStopId,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/rides'),
      headers: _headers,
      body: jsonEncode({
        'origin_latitude': originLatitude,
        'origin_longitude': originLongitude,
        'origin_address': originAddress,
        'destination_latitude': destinationLatitude,
        'destination_longitude': destinationLongitude,
        'destination_address': destinationAddress,
        'payment_method': paymentMethod,
        'route_id': ?routeId,
        'origin_stop_id': ?originStopId,
        'destination_stop_id': ?destinationStopId,
      }),
    );

    if (response.statusCode == 201) {
      return Ride.fromJson(json.decode(response.body));
    } else {
      throw Exception('Failed to request ride');
    }
  }

  static Future<List<Ride>> getRides() async {
    final response = await http.get(
      Uri.parse('$baseUrl/rides'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      List<dynamic> data = json.decode(response.body);
      return data.map((json) => Ride.fromJson(json)).toList();
    } else {
      throw Exception('Failed to load rides');
    }
  }

  static Future<List<Ride>> getAvailableRides() async {
    final response = await http.get(
      Uri.parse('$baseUrl/rides/available'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body) as List<dynamic>;
      return data.map((json) => Ride.fromJson(json)).toList();
    }

    throw Exception(_errorMessage(response, 'Failed to load available rides'));
  }

  static Future<Ride> getRide(int id) async {
    final response = await http.get(
      Uri.parse('$baseUrl/rides/$id'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      return Ride.fromJson(json.decode(response.body));
    } else {
      throw Exception('Failed to load ride');
    }
  }

  static Future<Ride> updateRide(int id, Map<String, dynamic> data) async {
    final response = await http.put(
      Uri.parse('$baseUrl/rides/$id'),
      headers: _headers,
      body: jsonEncode(data),
    );

    if (response.statusCode == 200) {
      return Ride.fromJson(json.decode(response.body));
    }

    throw Exception(_errorMessage(response, 'Failed to update ride'));
  }

  static String _errorMessage(http.Response response, String fallback) {
    try {
      final data = json.decode(response.body);
      if (data is Map<String, dynamic>) {
        if (data['message'] is String) {
          return data['message'];
        }
        if (data['errors'] is Map && (data['errors'] as Map).isNotEmpty) {
          final first = (data['errors'] as Map).values.first;
          if (first is List && first.isNotEmpty) {
            return first.first.toString();
          }
        }
      }
    } catch (_) {
      // Fall through to the generic message.
    }

    return fallback;
  }

  static Future<Ride> rateRide(int id, {required double rating, String? comment}) async {
    final response = await http.post(
      Uri.parse('$baseUrl/rides/$id/rate'),
      headers: _headers,
      body: jsonEncode({
        'rating': rating,
        'comment': comment,
      }),
    );

    if (response.statusCode == 200) {
      return Ride.fromJson(json.decode(response.body));
    } else {
      throw Exception('Failed to rate ride');
    }
  }

  static Future<Map<String, dynamic>> updateDriverLocation(int rideId, double latitude, double longitude) async {
    final response = await http.post(
      Uri.parse('$baseUrl/rides/$rideId/location'),
      headers: _headers,
      body: jsonEncode({
        'latitude': latitude,
        'longitude': longitude,
      }),
    );

    if (response.statusCode == 200) {
      return json.decode(response.body);
    } else {
      throw Exception('Failed to update driver location');
    }
  }

  // Vehicles
  static Future<List<Vehicle>> getVehicles() async {
    final response = await http.get(
      Uri.parse('$baseUrl/vehicles'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      List<dynamic> data = json.decode(response.body);
      return data.map((json) => Vehicle.fromJson(json)).toList();
    } else {
      throw Exception('Failed to load vehicles');
    }
  }

  static Future<Vehicle> createVehicle({
    required String make,
    required String model,
    required int year,
    required String licensePlate,
    required String color,
    required int capacity,
    required String vehicleType,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/vehicles'),
      headers: _headers,
      body: jsonEncode({
        'make': make,
        'model': model,
        'year': year,
        'license_plate': licensePlate,
        'color': color,
        'capacity': capacity,
        'vehicle_type': vehicleType,
      }),
    );

    if (response.statusCode == 201) {
      return Vehicle.fromJson(json.decode(response.body));
    } else {
      throw Exception('Failed to create vehicle');
    }
  }

  // Wallet
  static Future<WalletSummary> getWallet() async {
    final response = await http.get(
      Uri.parse('$baseUrl/wallet'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      return WalletSummary.fromJson(json.decode(response.body));
    }

    throw Exception(_errorMessage(response, 'Failed to load wallet'));
  }

  static Future<Map<String, dynamic>> requestDeposit({
    required double amount,
    required String method,
    String? reference,
    String? notes,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/wallet/deposits'),
      headers: _headers,
      body: jsonEncode({
        'amount': amount,
        'method': method,
        'reference': reference,
        'notes': notes,
      }),
    );

    if (response.statusCode == 201) {
      return json.decode(response.body);
    }

    throw Exception(_errorMessage(response, 'Failed to submit deposit request'));
  }

  static Future<Map<String, dynamic>> requestWithdrawal({
    required double amount,
    required String method,
    String? mobileNumber,
    String? bankName,
    String? accountNumber,
    String? accountName,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/wallet/withdrawals'),
      headers: _headers,
      body: jsonEncode({
        'amount': amount,
        'method': method,
        'mobile_number': mobileNumber,
        'bank_name': bankName,
        'account_number': accountNumber,
        'account_name': accountName,
      }),
    );

    if (response.statusCode == 201) {
      return json.decode(response.body);
    }

    throw Exception(_errorMessage(response, 'Failed to submit withdrawal request'));
  }

  // QR passes
  static Future<PassLookupResult> lookupPass(String code) async {
    final response = await http.get(
      Uri.parse('$baseUrl/passes/lookup?code=${Uri.encodeQueryComponent(code)}'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      return PassLookupResult.fromJson(json.decode(response.body));
    }

    throw Exception(_errorMessage(response, 'Could not find that pass'));
  }

  static Future<PassChargeResult> chargePass({
    required int passId,
    required int routeId,
    int? fareId,
    String? notes,
    String? pin,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/passes/$passId/charge'),
      headers: _headers,
      body: jsonEncode({
        'route_id': routeId,
        'fare_id': fareId,
        'notes': notes,
        'pin': ?pin,
      }),
    );

    if (response.statusCode == 201) {
      return PassChargeResult.fromJson(json.decode(response.body));
    }

    throw _passChargeException(response, 'Could not charge this pass');
  }

  static Future<PassChargeRequestStatus> createPassChargeRequest({
    required int passId,
    required int routeId,
    int? fareId,
    String? notes,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/passes/$passId/charge-requests'),
      headers: _headers,
      body: jsonEncode({
        'route_id': routeId,
        'fare_id': fareId,
        'notes': notes,
      }),
    );

    if (response.statusCode == 201) {
      return PassChargeRequestStatus.fromJson(json.decode(response.body));
    }

    throw _passChargeException(
      response,
      'Could not ask the customer to approve',
    );
  }

  static Future<PassChargeRequestStatus> getPassChargeRequest(
    int requestId,
  ) async {
    final response = await http.get(
      Uri.parse('$baseUrl/passes/charge-requests/$requestId'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      return PassChargeRequestStatus.fromJson(json.decode(response.body));
    }

    throw _passChargeException(response, 'Could not check the approval');
  }

  static Future<PassChargeRequestStatus> cancelPassChargeRequest(
    int requestId,
  ) async {
    final response = await http.post(
      Uri.parse('$baseUrl/passes/charge-requests/$requestId/cancel'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      return PassChargeRequestStatus.fromJson(json.decode(response.body));
    }

    throw _passChargeException(response, 'Could not cancel the approval');
  }

  static PassChargeException _passChargeException(
    http.Response response,
    String fallback,
  ) {
    Map<String, dynamic>? error;
    try {
      final decoded = json.decode(response.body);
      if (decoded is Map<String, dynamic>) {
        error = decoded;
      }
    } catch (_) {
      // Fall back to the friendly message below.
    }

    return PassChargeException(
      message: _errorMessage(response, fallback),
      code: error?['code']?.toString(),
      attemptsLeft: (error?['attempts_left'] as num?)?.toInt(),
      retryAfterMinutes: (error?['retry_after_minutes'] as num?)?.toInt(),
    );
  }

  // Payments
  static Future<Map<String, dynamic>> processPayment({
    required int rideId,
    String paymentMethod = 'cash',
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/payments/$rideId/process'),
      headers: _headers,
      body: jsonEncode({'payment_method': paymentMethod}),
    );

    if (response.statusCode == 200) {
      return json.decode(response.body);
    }

    throw Exception(_errorMessage(response, 'Failed to process payment'));
  }

  static Future<Map<String, dynamic>> getPaymentStatus(int rideId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/payments/$rideId/status'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      return json.decode(response.body);
    } else {
      throw Exception('Failed to get payment status');
    }
  }
}
