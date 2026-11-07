import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/realtime_service.dart';
import '../models/ride.dart';
import '../models/vehicle.dart';
import '../models/user.dart';
import '../models/wallet.dart';
import '../l10n/app_strings.dart';
import 'trip_tracking_screen.dart';
import 'wallet_screen.dart';
import 'qr_pass_screen.dart';
import 'add_vehicle_screen.dart';
import '../services/app_settings.dart';
import '../widgets/photo_tile.dart';
import '../services/tts_service.dart';

class DriverDashboard extends StatefulWidget {
  const DriverDashboard({super.key});

  @override
  State<DriverDashboard> createState() => _DriverDashboardState();
}

class _DriverDashboardState extends State<DriverDashboard> with RealtimeMixin {
  List<Ride> _availableRides = [];
  List<Ride> _completedRides = [];
  Ride? _currentRide;
  Vehicle? _myVehicle;
  User? _driver;
  bool _isLoading = true;
  bool _isOnline = false;
  int _selectedIndex = 0;
  Wallet? _wallet;
  double _todayEarnings = 0.0;
  int _totalRides = 0;
  double _rating = 0.0;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);

    try {
      final rides = await ApiService.getRides();
      final availableRides = await ApiService.getAvailableRides();
      final vehicles = await ApiService.getVehicles();
      final user = await ApiService.getCurrentUser();

      Wallet? wallet;
      try {
        wallet = (await ApiService.getWallet()).wallet;
      } catch (_) {
        // Keep the dashboard usable when the wallet endpoint is unavailable.
      }

      final completed = rides.where((r) => r.status == 'completed' && r.driverId == user.id).toList();
      final today = DateTime.now();
      final todayRides = completed.where((r) {
        final rideDate = r.completedAt != null ? r.completedAt! : DateTime.parse(r.updatedAt);
        return rideDate.year == today.year && rideDate.month == today.month && rideDate.day == today.day;
      }).toList();
      final paidToday = todayRides.where((r) => r.paymentStatus == 'paid');

      setState(() {
        _availableRides = availableRides;
        _completedRides = completed;
        _currentRide = rides.where((r) => r.status != 'pending' && r.status != 'completed' && r.status != 'cancelled').firstOrNull;
        _myVehicle = vehicles.isNotEmpty ? vehicles.first : null;
        _driver = user;
        _totalRides = completed.length;
        _todayEarnings = paidToday.fold(0.0, (sum, ride) => sum + (ride.fareAmount ?? 0.0));
        _wallet = wallet;
        _rating = user.rating;
        final vehicleApproved = _myVehicle?.requestStatus == 'approved';
        if (!user.isVerifiedDriver || !vehicleApproved) {
          _isOnline = false;
        }
        _isLoading = false;
      });

      // Start real-time updates if there's a current ride
      if (_currentRide != null) {
        startRealtimeUpdates(
          rideId: _currentRide!.id.toString(),
          onUpdate: (ride) {
            setState(() {
              _currentRide = ride;
              if (ride.status == 'completed' || ride.status == 'cancelled') {
                _currentRide = null;
                stopRealtimeUpdates();
                _loadData();
              }
            });
          },
        );
      }
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: ${e.toString()}')),
        );
      }
    }
  }

  bool get _verificationApproved => _driver?.isVerifiedDriver ?? false;

  bool get _vehicleApproved => _myVehicle?.requestStatus == 'approved';

  bool get _canTakeRides => _verificationApproved && _vehicleApproved;

  Future<void> _openVerification() async {
    final changed = await Navigator.pushNamed(context, '/driver-verification');
    if (changed == true && mounted) _loadData();
  }

  Future<void> _openVehicle() async {
    final changed = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => AddVehicleScreen(vehicle: _myVehicle),
      ),
    );
    if (changed == true && mounted) _loadData();
  }

  void _showSetupRequired() {
    final s = AppSettings.instance.strings;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text(s.onlineBlocked),
          action: SnackBarAction(
            label: s.completeSetup,
            onPressed: _openVerification,
          ),
        ),
      );
  }

  Future<void> _acceptRide(Ride ride) async {
    if (!_canTakeRides) {
      _showSetupRequired();
      return;
    }

    setState(() => _isLoading = true);

    try {
      final accepted = await ApiService.updateRide(ride.id, {'status': 'accepted'});

      if (!mounted) return;
      setState(() {
        _currentRide = accepted;
        _availableRides.removeWhere((r) => r.id == ride.id);
        _isLoading = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Ride accepted!')),
      );

      startRealtimeUpdates(
        rideId: accepted.id.toString(),
        onUpdate: (ride) {
          if (!mounted) return;
          setState(() {
            _currentRide = ride;
            if (ride.status == 'completed' || ride.status == 'cancelled') {
              _currentRide = null;
              stopRealtimeUpdates();
              _loadData();
            }
          });
        },
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: ${e.toString()}')),
      );
    }
  }

  Future<void> _updateRideStatus(String status) async {
    if (_currentRide == null) return;

    setState(() => _isLoading = true);

    try {
      final updated = await ApiService.updateRide(_currentRide!.id, {'status': status});

      if (!mounted) return;
      final isTerminal = status == 'completed' || status == 'cancelled';
      setState(() {
        _currentRide = isTerminal ? null : updated;
        _isLoading = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Ride status updated to $status')),
      );

      if (isTerminal) {
        _loadData();
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: ${e.toString()}')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = AppSettings.instance.strings;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Driver Dashboard'),
        backgroundColor: Colors.green,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
          Switch(
            value: _isOnline,
            onChanged: _canTakeRides
                ? (value) {
                    setState(() => _isOnline = value);
                  }
                : null,
          ),
          const SizedBox(width: 8),
          Text(
            _canTakeRides
                ? (_isOnline ? 'Online' : 'Offline')
                : s.completeSetup,
            style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
          ),
          const SizedBox(width: 16),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _buildContent(),
      bottomNavigationBar: BottomNavigationBar(
        type: BottomNavigationBarType.fixed,
        currentIndex: _selectedIndex,
        onTap: (index) {
          setState(() => _selectedIndex = index);
        },
        selectedItemColor: Colors.green,
        unselectedItemColor: Colors.grey,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.qr_code_scanner),
            label: 'Pass',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.account_balance_wallet),
            label: 'Wallet',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.history),
            label: 'Rides',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }

  Widget _buildContent() {
    switch (_selectedIndex) {
      case 0:
        return _buildHomeTab();
      case 1:
        return _buildPassTab();
      case 2:
        return _buildEarningsTab();
      case 3:
        return _buildRidesTab();
      case 4:
        return _buildProfileTab();
      default:
        return _buildHomeTab();
    }
  }

  Future<void> _openPasses() async {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const QrPassScreen()),
    );
    if (mounted) {
      _loadData();
    }
  }

  Widget _buildPassTab() {
    final s = AppSettings.instance.strings;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.all(22),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFE65100), Color(0xFFF59E0B)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(24),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.18),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: const Icon(
                    Icons.qr_code_scanner,
                    color: Colors.white,
                    size: 32,
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  s.passTitle,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  s.passSubtitle,
                  style: const TextStyle(
                    color: Colors.white70,
                    fontSize: 14,
                    height: 1.4,
                  ),
                ),
                const SizedBox(height: 18),
                FilledButton.icon(
                  onPressed: _openPasses,
                  icon: const Icon(Icons.qr_code_scanner, size: 20),
                  label: Text(s.scanCard),
                  style: FilledButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: const Color(0xFFE65100),
                    minimumSize: const Size(double.infinity, 52),
                    textStyle: const TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.help_outline, color: Color(0xFFE65100)),
                      const SizedBox(width: 8),
                      Text(
                        'How it works',
                        style: TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w700,
                          color: Colors.grey.shade800,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  _buildPassStep(
                    '1',
                    'Scan the customer card',
                    'Or type the code printed under the QR.',
                  ),
                  _buildPassStep(
                    '2',
                    'Choose where they are going',
                    'Pick the route and check the fare with them.',
                  ),
                  _buildPassStep(
                    '3',
                    'Customer approves',
                    'They enter their PIN here, or reply to the SMS on their own phone.',
                  ),
                  _buildPassStep(
                    '4',
                    'Charge the fare',
                    'Money comes from the pass wallet and your earnings are credited.',
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPassStep(String number, String title, String detail) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 14,
            backgroundColor: const Color(0xFFE65100).withValues(alpha: 0.12),
            child: Text(
              number,
              style: const TextStyle(
                color: Color(0xFFE65100),
                fontWeight: FontWeight.w800,
                fontSize: 13,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 15,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  detail,
                  style: TextStyle(
                    fontSize: 13,
                    color: Colors.grey.shade600,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSetupCard(AppStrings s) {
    if (_canTakeRides) {
      return Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: const Color(0xFFE8F5E9),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFA5D6A7)),
        ),
        child: Row(
          children: [
            const Icon(
              Icons.verified_rounded,
              color: Color(0xFF2E7D32),
              size: 26,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                s.readyToDrive,
                style: const TextStyle(
                  color: Color(0xFF2E7D32),
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                ),
              ),
            ),
            Switch(
              value: _isOnline,
              onChanged: (value) => setState(() => _isOnline = value),
            ),
          ],
        ),
      );
    }

    final verificationStatus = _driver?.verificationStatus ?? 'unverified';
    final vehicleStatus = _myVehicle?.requestStatus;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFF2C9A8)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(
                Icons.assignment_turned_in_outlined,
                color: Color(0xFFE65100),
                size: 24,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  s.completeSetup,
                  style: const TextStyle(
                    fontSize: 16.5,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF241C15),
                  ),
                ),
              ),
              Text(
                s.onlineBlocked,
                style: const TextStyle(
                  fontSize: 11,
                  color: Color(0xFF9A9086),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          _setupRow(
            icon: Icons.shield_outlined,
            title: s.verificationStep,
            status: s.verificationStatusLabel(verificationStatus),
            done: _verificationApproved,
            warning: verificationStatus == 'rejected',
            onTap: _openVerification,
          ),
          const Divider(height: 1),
          _setupRow(
            icon: Icons.directions_bus_rounded,
            title: s.vehicleStep,
            status: _vehicleStatusLabel(s, vehicleStatus),
            done: vehicleStatus == 'approved',
            warning: vehicleStatus == 'rejected',
            onTap: _openVehicle,
          ),
        ],
      ),
    );
  }

  String _vehicleStatusLabel(AppStrings s, String? status) {
    switch (status) {
      case 'approved':
        return s.vehicleStatusApproved;
      case 'pending':
        return s.vehicleStatusPending;
      case 'rejected':
        return s.vehicleStatusRejected;
      default:
        return s.addVehicle;
    }
  }

  Color _vehicleStatusColor(String? status) {
    switch (status) {
      case 'approved':
        return const Color(0xFF2E7D32);
      case 'pending':
        return const Color(0xFFEF6C00);
      case 'rejected':
        return const Color(0xFFC62828);
      default:
        return const Color(0xFF6F6459);
    }
  }

  Widget _setupRow({
    required IconData icon,
    required String title,
    required String status,
    required bool done,
    required bool warning,
    required VoidCallback onTap,
  }) {
    final color = done
        ? const Color(0xFF2E7D32)
        : (warning ? const Color(0xFFC62828) : const Color(0xFFEF6C00));

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 10),
        child: Row(
          children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(done ? Icons.check_rounded : icon, color: color, size: 21),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF241C15),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    status,
                    style: TextStyle(
                      fontSize: 12.5,
                      color: color,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(
              Icons.chevron_right_rounded,
              color: Color(0xFF9A9086),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHomeTab() {
    final s = AppSettings.instance.strings;

    return RefreshIndicator(
      onRefresh: _loadData,
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildSetupCard(s),
            const SizedBox(height: 16),
            Card(
              margin: const EdgeInsets.only(bottom: 16),
              color: const Color(0xFFE65100),
              child: InkWell(
                onTap: _openPasses,
                borderRadius: BorderRadius.circular(12),
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(
                          Icons.qr_code_scanner,
                          color: Colors.white,
                          size: 28,
                        ),
                      ),
                      const SizedBox(width: 14),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Tap & Ride pass',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 17,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            SizedBox(height: 2),
                            Text(
                              'Scan a customer card and charge their trip',
                              style: TextStyle(
                                color: Colors.white70,
                                fontSize: 13,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Icon(Icons.chevron_right, color: Colors.white),
                    ],
                  ),
                ),
              ),
            ),
            if (_myVehicle != null) ...[
              Card(
                color: Colors.blue.shade50,
                child: InkWell(
                  onTap: _openVehicle,
                  borderRadius: BorderRadius.circular(12),
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        PhotoTile(
                          imageUrl: _myVehicle!.vehicleImage,
                          size: 64,
                          borderRadius: 14,
                          fallbackIcon: Icons.directions_bus,
                          fallbackColor: const Color(0xFF1565C0),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      s.myVehicle,
                                      style: const TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 8,
                                      vertical: 3,
                                    ),
                                    decoration: BoxDecoration(
                                      color: _vehicleStatusColor(
                                        _myVehicle!.requestStatus,
                                      ).withValues(alpha: 0.14),
                                      borderRadius: BorderRadius.circular(999),
                                    ),
                                    child: Text(
                                      _vehicleStatusLabel(
                                        s,
                                        _myVehicle!.requestStatus,
                                      ),
                                      style: TextStyle(
                                        fontSize: 10.5,
                                        fontWeight: FontWeight.w700,
                                        color: _vehicleStatusColor(
                                          _myVehicle!.requestStatus,
                                        ),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text(
                                '${_myVehicle!.make} ${_myVehicle!.model}',
                              ),
                              Text('Plate: ${_myVehicle!.licensePlate}'),
                              Text('Color: ${_myVehicle!.color}'),
                              if (_myVehicle!.requestStatus == 'rejected' &&
                                  (_myVehicle!.rejectionReason ?? '')
                                      .isNotEmpty) ...[
                                const SizedBox(height: 6),
                                Text(
                                  '${s.adminNote}: ${_myVehicle!.rejectionReason}',
                                  style: const TextStyle(
                                    fontSize: 12,
                                    color: Color(0xFFC62828),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                        const Icon(Icons.chevron_right, color: Colors.black38),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],
            if (_currentRide != null) ...[
              Card(
                color: Colors.orange.shade50,
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.live_help, color: Colors.red),
                          const SizedBox(width: 8),
                          const Text(
                            'LIVE RIDE',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.red,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Text('Status: ${_currentRide!.status.toUpperCase()}'),
                      Text('From: ${_currentRide!.originAddress}'),
                      Text('To: ${_currentRide!.destinationAddress}'),
                      if (_currentRide!.fareAmount != null)
                        Text('Fare: GHS ${_currentRide!.fareAmount!.toStringAsFixed(2)}'),
                      if (_currentRide!.passenger != null) ...[
                        const SizedBox(height: 8),
                        Text('Passenger: ${_currentRide!.passenger!.name}'),
                        Text('Rating: ${_currentRide!.passenger!.rating}'),
                      ],
                      const SizedBox(height: 16),
                      if (_currentRide!.status == 'accepted') ...[
                        ElevatedButton(
                          onPressed: () => _updateRideStatus('driver_arrived'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green,
                          ),
                          child: const Text('Arrived at Pickup'),
                        ),
                      ] else if (_currentRide!.status == 'driver_arrived') ...[
                        ElevatedButton(
                          onPressed: () => _updateRideStatus('in_progress'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.blue,
                          ),
                          child: const Text('Start Trip'),
                        ),
                      ] else if (_currentRide!.status == 'in_progress') ...[
                        Row(
                          children: [
                            Expanded(
                              child: ElevatedButton(
                                onPressed: () => _updateRideStatus('completed'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.green,
                                ),
                                child: const Text('Complete Trip'),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: ElevatedButton.icon(
                                onPressed: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) => TripTrackingScreen(ride: _currentRide!),
                                    ),
                                  );
                                },
                                icon: const Icon(Icons.map),
                                label: const Text('Track'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.blue,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                      const SizedBox(height: 8),
                      OutlinedButton(
                        onPressed: () => _updateRideStatus('cancelled'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.red,
                        ),
                        child: const Text('Cancel Ride'),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],
            const Text(
              'Available Rides',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            _availableRides.isEmpty
                ? Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: Text(
                        !_canTakeRides
                            ? s.onlineBlocked
                            : _isOnline
                                ? 'No rides available at the moment'
                                : 'Go online to receive ride requests',
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Colors.grey),
                      ),
                    ),
                  )
                : ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _availableRides.length,
                    itemBuilder: (context, index) {
                      final ride = _availableRides[index];
                      return RideRequestCard(
                        ride: ride,
                        onAccept: () => _acceptRide(ride),
                      );
                    },
                  ),
          ],
        ),
      ),
    );
  }

  Future<void> _openWallet() async {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const WalletScreen(isDriver: true)),
    );
    if (mounted) {
      _loadData();
    }
  }

  Widget _buildEarningsTab() {
    final wallet = _wallet;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            clipBehavior: Clip.hardEdge,
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF0E5A34), Color(0xFF2E9E5B)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(24),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF1B8A4C).withValues(alpha: 0.28),
                  blurRadius: 20,
                  offset: const Offset(0, 8),
                ),
              ],
            ),
            child: Stack(
              children: [
                Positioned(
                  top: -50,
                  right: -30,
                  child: Container(
                    width: 150,
                    height: 150,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white.withValues(alpha: 0.07),
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.16),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Icon(
                              Icons.account_balance_wallet,
                              color: Colors.white,
                              size: 20,
                            ),
                          ),
                          const SizedBox(width: 10),
                          const Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Wallet balance',
                                style: TextStyle(
                                  color: Colors.white70,
                                  fontSize: 12,
                                ),
                              ),
                              Text(
                                'Available to cash out',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      FittedBox(
                        fit: BoxFit.scaleDown,
                        alignment: Alignment.centerLeft,
                        child: Text(
                          'GHS ${(wallet?.availableBalance ?? 0).toStringAsFixed(2)}',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 34,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                      const SizedBox(height: 14),
                      Container(
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.14),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Row(
                          children: [
                            _buildWalletStat('Main', wallet?.balance ?? 0),
                            _buildWalletDivider(),
                            _buildWalletStat('Bonus', wallet?.bonusBalance ?? 0),
                            _buildWalletDivider(),
                            _buildWalletStat(
                              'Pending',
                              wallet?.pendingBalance ?? 0,
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: _openWallet,
                          icon: const Icon(Icons.tune_rounded, size: 18),
                          label: const Text('Manage wallet'),
                          style: FilledButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: const Color(0xFF0E5A34),
                            padding: const EdgeInsets.symmetric(vertical: 12),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      children: [
                        const Icon(Icons.today, color: Colors.blue),
                        const SizedBox(height: 8),
                        const Text('Today (paid)'),
                        const SizedBox(height: 4),
                        Text(
                          'GHS ${_todayEarnings.toStringAsFixed(2)}',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              Expanded(
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      children: [
                        const Icon(Icons.directions_car, color: Colors.orange),
                        const SizedBox(height: 8),
                        const Text('Total Rides'),
                        const SizedBox(height: 4),
                        Text(
                          '$_totalRides',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Earnings Breakdown',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  _buildEarningRow('Paid today', _todayEarnings, Colors.blue),
                  _buildEarningRow('Completed rides', _totalRides.toDouble(), Colors.orange, isMoney: false),
                  _buildEarningRow('Bonus balance', wallet?.bonusBalance ?? 0, Colors.green),
                  _buildEarningRow('Pending withdrawal', wallet?.pendingBalance ?? 0, Colors.deepOrange),
                  _buildEarningRow('Rating', _rating, Colors.amber, isMoney: false, isRating: true),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEarningRow(
    String label,
    double value,
    Color color, {
    bool isMoney = true,
    bool isRating = false,
  }) {
    final display = isRating
        ? value.toStringAsFixed(1)
        : isMoney
            ? 'GHS ${value.toStringAsFixed(2)}'
            : value.toInt().toString();

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label),
          Text(
            display,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildWalletStat(String label, double value) {
    return Expanded(
      child: Column(
        children: [
          Text(
            label,
            style: const TextStyle(color: Colors.white70, fontSize: 11),
          ),
          const SizedBox(height: 4),
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(
              'GHS ${value.toStringAsFixed(2)}',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 13,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildWalletDivider() {
    return Container(
      width: 1,
      height: 28,
      color: Colors.white.withValues(alpha: 0.18),
    );
  }

  Widget _buildRidesTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Completed Rides',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          _completedRides.isEmpty
              ? Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Text(
                      'No completed rides yet',
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: Colors.grey),
                    ),
                  ),
                )
              : ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: _completedRides.length,
                  itemBuilder: (context, index) {
                    final ride = _completedRides[index];
                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'Ride #${ride.id}',
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                                if (ride.completedAt != null)
                                  Text(
                                    _formatDate(ride.completedAt!),
                                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Text('From: ${ride.originAddress}'),
                            Text('To: ${ride.destinationAddress}'),
                            const SizedBox(height: 8),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'GHS ${ride.fareAmount?.toStringAsFixed(2) ?? '0.00'}',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    color: Colors.green,
                                  ),
                                ),
                                if (ride.driverRating != null)
                                  Row(
                                    children: [
                                      const Icon(Icons.star, color: Colors.amber, size: 16),
                                      const SizedBox(width: 4),
                                      Text(ride.driverRating!.toStringAsFixed(1)),
                                    ],
                                  ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
        ],
      ),
    );
  }

  Widget _buildProfileTab() {
    final s = AppSettings.instance.strings;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(20.0),
              child: Column(
                children: [
                  PhotoTile(
                    imageUrl: _driver?.profileImage,
                    size: 100,
                    circle: true,
                    fallbackIcon: Icons.person,
                    fallbackColor: Colors.green.shade700,
                    fallbackBackground: Colors.green.shade100,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    _driver?.name ?? 'Driver',
                    style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    _driver?.email ?? '',
                    style: const TextStyle(color: Colors.grey),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.star, color: Colors.amber),
                      const SizedBox(width: 8),
                      Text(
                        _rating.toStringAsFixed(1),
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        '($_totalRides rides)',
                        style: const TextStyle(color: Colors.grey),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: _openProfile,
                      icon: const Icon(Icons.edit_outlined),
                      label: Text(s.editProfile),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: const Color(0xFFE65100),
                        side: const BorderSide(color: Color(0xFFE65100)),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                        padding: const EdgeInsets.symmetric(vertical: 13),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          Card(
            child: ListTile(
              leading: Icon(
                _verificationApproved
                    ? Icons.verified_user_rounded
                    : Icons.shield_outlined,
                color: _verificationApproved
                    ? const Color(0xFF2E7D32)
                    : const Color(0xFFE65100),
              ),
              title: Text(
                s.driverVerification,
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
              subtitle: Text(
                s.verificationStatusLabel(
                  _driver?.verificationStatus ?? 'unverified',
                ),
                style: const TextStyle(
                  fontSize: 12.5,
                  color: Color(0xFF9A9086),
                ),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: _openVerification,
            ),
          ),
          const SizedBox(height: 16),
          if (_myVehicle != null) ...[
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        PhotoTile(
                          imageUrl: _myVehicle!.vehicleImage,
                          size: 72,
                          borderRadius: 14,
                          fallbackIcon: Icons.directions_bus,
                          fallbackColor: const Color(0xFF1565C0),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Vehicle Information',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                _vehicleStatusLabel(
                                  s,
                                  _myVehicle!.requestStatus,
                                ),
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                  color: _vehicleStatusColor(
                                    _myVehicle!.requestStatus,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    _buildProfileRow('Make', _myVehicle!.make),
                    _buildProfileRow('Model', _myVehicle!.model),
                    _buildProfileRow('License Plate', _myVehicle!.licensePlate),
                    _buildProfileRow('Color', _myVehicle!.color),
                    _buildProfileRow('Year', _myVehicle!.year),
                    if (_myVehicle!.requestStatus == 'rejected' &&
                        (_myVehicle!.rejectionReason ?? '').isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        '${s.adminNote}: ${_myVehicle!.rejectionReason}',
                        style: const TextStyle(
                          fontSize: 12.5,
                          color: Color(0xFFC62828),
                        ),
                      ),
                    ],
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        onPressed: _openVehicle,
                        icon: const Icon(Icons.edit_outlined, size: 18),
                        label: Text(s.editVehicle),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: const Color(0xFFE65100),
                          side: const BorderSide(color: Color(0xFFE65100)),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                          padding: const EdgeInsets.symmetric(vertical: 12),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
          ] else ...[
            Card(
              child: ListTile(
                leading: const Icon(
                  Icons.directions_bus_rounded,
                  color: Color(0xFFE65100),
                ),
                title: Text(
                  s.addVehicle,
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
                subtitle: Text(
                  s.vehicleNeeded,
                  style: const TextStyle(fontSize: 12.5),
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: _openVehicle,
              ),
            ),
            const SizedBox(height: 16),
          ],
          Card(
            child: ListTile(
              leading: const Icon(Icons.logout, color: Colors.red),
              title: const Text('Logout'),
              onTap: () async {
                await TtsService.instance.stop();
                await ApiService.logout();
                if (mounted) {
                  Navigator.of(context).pushReplacementNamed('/');
                }
              },
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _openProfile() async {
    await Navigator.of(context).pushNamed('/profile');
    if (!mounted) return;
    try {
      final user = await ApiService.getCurrentUser();
      if (!mounted) return;
      setState(() {
        _driver = user;
        _rating = user.rating;
      });
    } catch (_) {
      // Keep the cached profile when the refresh fails offline.
    }
  }

  Widget _buildProfileRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  String _formatDate(DateTime date) {
    return '${date.day}/${date.month}/${date.year} ${date.hour}:${date.minute.toString().padLeft(2, '0')}';
  }
}

class RideRequestCard extends StatelessWidget {
  final Ride ride;
  final VoidCallback onAccept;

  const RideRequestCard({
    super.key,
    required this.ride,
    required this.onAccept,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.location_on, color: Colors.orange),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    ride.originAddress,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                const Icon(Icons.flag, color: Colors.green),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(ride.destinationAddress),
                ),
              ],
            ),
            if (ride.fareAmount != null) ...[
              const SizedBox(height: 8),
              Text(
                'Fare: GHS ${ride.fareAmount!.toStringAsFixed(2)}',
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Colors.green,
                ),
              ),
            ],
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: onAccept,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.green,
                minimumSize: const Size(double.infinity, 40),
              ),
              child: const Text('Accept Ride'),
            ),
          ],
        ),
      ),
    );
  }
}
