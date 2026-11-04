import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';

import '../app_scope.dart';
import '../l10n/app_strings.dart';
import '../models/bus_stop.dart';
import '../models/ride.dart';
import '../models/route_from_stop.dart';
import '../services/api_service.dart';
import '../services/app_settings.dart';
import '../services/realtime_service.dart';
import '../services/tts_service.dart';
import '../widgets/photo_tile.dart';
import 'payment_screen.dart';

class RideBookingScreen extends StatefulWidget {
  const RideBookingScreen({super.key});

  @override
  State<RideBookingScreen> createState() => _RideBookingScreenState();
}

class _RideBookingScreenState extends State<RideBookingScreen>
    with RealtimeMixin {
  static const _defaultLat = 5.6037;
  static const _defaultLng = -0.1870;

  List<BusStop> _stops = const [];
  List<RouteFromStop> _routeOptions = const [];
  BusStop? _origin;
  BusStop? _destination;
  int? _selectedRouteId;
  double? _selectedFare;
  String _paymentMethod = 'cash';
  bool _loadingStops = true;
  bool _loadingRoutes = false;
  bool _locating = false;
  bool _isLoading = false;
  Ride? _currentRide;
  bool _spokeIntro = false;

  AppSettings get _settings => AppSettings.instance;
  AppStrings get _s => _settings.strings;

  @override
  void initState() {
    super.initState();
    _loadStops();
    WidgetsBinding.instance.addPostFrameCallback((_) => _speakIntro());
  }

  Future<void> _speakIntro() async {
    if (_spokeIntro || !mounted || !_settings.voiceEnabled) return;
    _spokeIntro = true;
    await TtsService.instance.speak(
      _s.rideBookingHint,
      language: _settings.language,
    );
  }

  Future<void> _loadStops() async {
    setState(() => _loadingStops = true);
    try {
      final stops = await ApiService.getBusStops();
      if (!mounted) return;
      setState(() {
        _stops = stops;
        _loadingStops = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingStops = false);
      _showMessage(_s.errorLoadingStops);
    }
  }

  void _showMessage(String message) {
    if (!mounted) return;
    final messenger = ScaffoldMessenger.of(context);
    messenger.hideCurrentSnackBar();
    messenger.showSnackBar(
      SnackBar(behavior: SnackBarBehavior.floating, content: Text(message)),
    );
  }

  Future<void> _speak(String text) async {
    if (!_settings.voiceEnabled) return;
    await TtsService.instance.speak(text, language: _settings.language);
  }

  Future<void> _useMyLocation() async {
    if (_locating) return;
    setState(() => _locating = true);
    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        _showMessage(_s.locationUnavailable);
        return;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        _showMessage(_s.locationDenied);
        return;
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.medium,
          timeLimit: Duration(seconds: 12),
        ),
      );
      final nearest = _nearestStop(position);
      if (nearest == null) {
        _showMessage(_s.locationUnavailable);
        return;
      }

      if (!mounted) return;
      setState(() {
        _origin = nearest;
        _destination = null;
        _selectedRouteId = null;
        _selectedFare = null;
      });
      _loadRouteOptions(nearest.id);
      await _speak(_s.originSet(nearest.name));
    } catch (_) {
      _showMessage(_s.locationUnavailable);
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  BusStop? _nearestStop(Position position) {
    BusStop? best;
    var bestDistance = double.infinity;
    for (final stop in _stops) {
      if (stop.latitude == null || stop.longitude == null) continue;
      final distance = Geolocator.distanceBetween(
        position.latitude,
        position.longitude,
        stop.latitude!,
        stop.longitude!,
      );
      if (distance < bestDistance) {
        bestDistance = distance;
        best = stop;
      }
    }
    if (best == null || bestDistance > 50000) return null;
    return best;
  }

  Future<void> _pickStop({required bool pickup}) async {
    if (!pickup && _origin == null) {
      _showMessage(_s.tapPickup);
      await _speak(_s.tapPickup);
      return;
    }

    final stop = await _showStopPicker(pickup: pickup);
    if (stop == null || !mounted) return;

    if (pickup) {
      final changed = _origin?.id != stop.id;
      setState(() {
        _origin = stop;
        if (changed) {
          _destination = null;
          _selectedRouteId = null;
          _selectedFare = null;
        }
      });
      if (changed) {
        _loadRouteOptions(stop.id);
      }
      await _speak(_s.rideOriginSpeech(stop.name));
      return;
    }

    final option = _findDropoffOption(stop.id);
    setState(() {
      _destination = stop;
      _selectedRouteId = option?.routeId;
      _selectedFare = option?.fare;
    });
    await _speak(_s.destinationSet(stop.name));
    if (option != null) {
      await _speak(_s.rideFareSpeech(option.fare));
    }
  }

  Future<void> _loadRouteOptions(int stopId) async {
    setState(() => _loadingRoutes = true);
    try {
      final options = await ApiService.getRoutesFromStop(stopId);
      if (!mounted) return;
      setState(() {
        _routeOptions = options;
        _loadingRoutes = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _routeOptions = const [];
        _loadingRoutes = false;
      });
    }
  }

  List<_DropoffOption> get _dropoffOptions {
    final byStopId = <int, _DropoffOption>{};

    for (final route in _routeOptions) {
      for (final stopFare in route.stops) {
        if (stopFare.busStopId == _origin?.id) continue;

        BusStop? stop;
        for (final candidate in _stops) {
          if (candidate.id == stopFare.busStopId) {
            stop = candidate;
            break;
          }
        }
        if (stop == null) continue;

        final existing = byStopId[stop.id];
        if (existing == null) {
          byStopId[stop.id] = _DropoffOption(
            stop: stop,
            fare: stopFare.fare,
            routeId: route.routeId,
          );
        } else if (stopFare.fare < existing.fare) {
          byStopId[stop.id] = _DropoffOption(
            stop: stop,
            fare: stopFare.fare,
            routeId: route.routeId,
          );
        }
      }
    }

    return byStopId.values.toList()..sort((a, b) => a.fare.compareTo(b.fare));
  }

  _DropoffOption? _findDropoffOption(int stopId) {
    for (final option in _dropoffOptions) {
      if (option.stop.id == stopId) return option;
    }
    return null;
  }

  Future<BusStop?> _showStopPicker({required bool pickup}) async {
    final s = _s;
    return showModalBottomSheet<BusStop>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) {
        return DraggableScrollableSheet(
          initialChildSize: 0.7,
          minChildSize: 0.45,
          maxChildSize: 0.94,
          expand: false,
          builder: (sheetContext, controller) {
            return Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
              ),
              child: Column(
                children: [
                  const SizedBox(height: 10),
                  Container(
                    width: 44,
                    height: 4,
                    decoration: BoxDecoration(
                      color: const Color(0xFFE0D8CF),
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 14, 10, 6),
                    child: Row(
                      children: [
                        Expanded(
                          child: Text(
                            pickup
                                ? s.choosePickupTitle
                                : s.chooseDestinationTitle,
                            style: const TextStyle(
                              fontSize: 19,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF241C15),
                            ),
                          ),
                        ),
                        IconButton(
                          tooltip: s.cancel,
                          onPressed: () => Navigator.pop(sheetContext),
                          icon: const Icon(Icons.close_rounded),
                        ),
                      ],
                    ),
                  ),
                  if (pickup)
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
                      child: Material(
                        color: const Color(0xFFFFF1E6),
                        borderRadius: BorderRadius.circular(16),
                        child: InkWell(
                          borderRadius: BorderRadius.circular(16),
                          onTap: (_locating || _stops.isEmpty)
                              ? null
                              : () async {
                                  await _useMyLocation();
                                  if (sheetContext.mounted && _origin != null) {
                                    Navigator.pop(sheetContext, _origin);
                                  }
                                },
                          child: Padding(
                            padding: const EdgeInsets.all(14),
                            child: Row(
                              children: [
                                _locating
                                    ? const SizedBox(
                                        width: 24,
                                        height: 24,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                        ),
                                      )
                                    : const Icon(
                                        Icons.my_location_rounded,
                                        color: Color(0xFFE65100),
                                      ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Text(
                                    s.useMyLocation,
                                    style: const TextStyle(
                                      fontSize: 15,
                                      fontWeight: FontWeight.w800,
                                      color: Color(0xFF9A3E00),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                  if (!pickup && _dropoffOptions.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.fromLTRB(20, 2, 20, 8),
                      child: Row(
                        children: [
                          const Icon(
                            Icons.receipt_long_rounded,
                            size: 16,
                            color: Color(0xFF8A8178),
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              s.faresFromPickup,
                              style: const TextStyle(
                                fontSize: 12.5,
                                fontWeight: FontWeight.w600,
                                color: Color(0xFF8A8178),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  if (_loadingStops || (!pickup && _loadingRoutes))
                    const Expanded(
                      child: Center(child: CircularProgressIndicator()),
                    )
                  else if (!pickup && _dropoffOptions.isNotEmpty)
                    Expanded(
                      child: ListView.separated(
                        controller: controller,
                        padding: const EdgeInsets.fromLTRB(14, 0, 14, 24),
                        itemCount: _dropoffOptions.length,
                        separatorBuilder: (_, _) => const SizedBox(height: 8),
                        itemBuilder: (context, index) {
                          final option = _dropoffOptions[index];
                          final selected = _destination?.id == option.stop.id;
                          return Material(
                            color: selected
                                ? const Color(0xFF1B8A4C).withValues(alpha: 0.10)
                                : const Color(0xFFF7F4F0),
                            borderRadius: BorderRadius.circular(16),
                            child: InkWell(
                              borderRadius: BorderRadius.circular(16),
                              onTap: () =>
                                  Navigator.pop(sheetContext, option.stop),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 14,
                                  vertical: 12,
                                ),
                                child: Row(
                                  children: [
                                    PhotoTile(
                                      imageUrl: option.stop.imageUrl,
                                      size: 44,
                                      borderRadius: 12,
                                      fallbackIcon: Icons.place_rounded,
                                      fallbackColor: const Color(0xFF1B8A4C),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Text(
                                        option.stop.name,
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(
                                          fontSize: 15.5,
                                          fontWeight: FontWeight.w800,
                                          color: Color(0xFF241C15),
                                          height: 1.15,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 10),
                                    Text(
                                      'GHS ${option.fare.toStringAsFixed(2)}',
                                      style: const TextStyle(
                                        fontSize: 15,
                                        fontWeight: FontWeight.w800,
                                        color: Color(0xFF1B8A4C),
                                      ),
                                    ),
                                    const Icon(
                                      Icons.chevron_right_rounded,
                                      color: Color(0xFF9A9086),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    )
                  else
                    Expanded(
                      child: Builder(
                        builder: (context) {
                          final stops = pickup
                              ? _stops
                              : _stops
                                  .where((stop) => stop.id != _origin?.id)
                                  .toList();
                          return GridView.builder(
                            controller: controller,
                            padding: const EdgeInsets.fromLTRB(14, 4, 14, 24),
                            itemCount: stops.length,
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              mainAxisSpacing: 10,
                              crossAxisSpacing: 10,
                              childAspectRatio: 1.35,
                            ),
                            itemBuilder: (context, index) {
                              final stop = stops[index];
                              final selected = pickup
                                  ? _origin?.id == stop.id
                                  : _destination?.id == stop.id;
                              final color = pickup
                                  ? const Color(0xFFE65100)
                                  : const Color(0xFF1B8A4C);
                              return Material(
                                color: selected
                                    ? color.withValues(alpha: 0.10)
                                    : const Color(0xFFF7F4F0),
                                borderRadius: BorderRadius.circular(16),
                                child: InkWell(
                                  borderRadius: BorderRadius.circular(16),
                                  onTap: () =>
                                      Navigator.pop(sheetContext, stop),
                                  child: Padding(
                                    padding: const EdgeInsets.all(10),
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.stretch,
                                      children: [
                                        Expanded(
                                          child: PhotoTile(
                                            imageUrl: stop.imageUrl,
                                            width: double.infinity,
                                            height: double.infinity,
                                            borderRadius: 12,
                                            fallbackIcon: pickup
                                                ? Icons.trip_origin_rounded
                                                : Icons.place_rounded,
                                            fallbackColor: color,
                                          ),
                                        ),
                                        const SizedBox(height: 6),
                                        Text(
                                          stop.name,
                                          textAlign: TextAlign.center,
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(
                                            fontSize: 13.5,
                                            fontWeight: FontWeight.w800,
                                            color: Color(0xFF241C15),
                                            height: 1.15,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              );
                            },
                          );
                        },
                      ),
                    ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _requestRide() async {
    final origin = _origin;
    final destination = _destination;
    if (origin == null || destination == null) {
      _showMessage(_s.selectBothLocations);
      await _speak(_s.selectBothLocations);
      return;
    }
    if (origin.id == destination.id) {
      _showMessage(_s.sameStop);
      await _speak(_s.sameStop);
      return;
    }

    setState(() => _isLoading = true);

    try {
      final ride = await ApiService.requestRide(
        originLatitude: origin.latitude ?? _defaultLat,
        originLongitude: origin.longitude ?? _defaultLng,
        originAddress: origin.name,
        destinationLatitude: destination.latitude ?? _defaultLat,
        destinationLongitude: destination.longitude ?? _defaultLng,
        destinationAddress: destination.name,
        paymentMethod: _paymentMethod,
        routeId: _selectedRouteId,
        originStopId: _selectedRouteId == null ? null : origin.id,
        destinationStopId: _selectedRouteId == null ? null : destination.id,
      );

      if (!mounted) return;
      setState(() {
        _currentRide = ride;
        _isLoading = false;
      });

      await _speak(_s.rideRequested);

      startRealtimeUpdates(
        rideId: ride.id.toString(),
        onUpdate: _handleRideUpdate,
      );
    } catch (_) {
      if (!mounted) return;
      setState(() => _isLoading = false);
      _showMessage(_s.rideRequestFailed);
      await _speak(_s.rideRequestFailed);
    }
  }

  void _handleRideUpdate(Ride updatedRide) {
    if (!mounted) return;
    final previous = _currentRide?.status;
    setState(() => _currentRide = updatedRide);

    if (updatedRide.status != previous) {
      _speak(_s.rideStatusLabel(updatedRide.status));
    }

    if (updatedRide.status == 'completed' ||
        updatedRide.status == 'cancelled') {
      stopRealtimeUpdates();
    }
  }

  Future<void> _openPayment() async {
    final ride = _currentRide;
    if (ride == null) return;
    final paid = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (context) => PaymentScreen(ride: ride)),
    );
    if (paid == true) {
      await _refreshRide();
    }
  }

  Future<void> _refreshRide() async {
    try {
      final ride = await ApiService.getRide(_currentRide!.id);
      if (!mounted) return;
      setState(() => _currentRide = ride);
    } catch (_) {
      // Keep the last known ride on a failed refresh.
    }
  }

  @override
  Widget build(BuildContext context) {
    final settings = AppScope.of(context);
    final s = settings.strings;

    return Scaffold(
      backgroundColor: const Color(0xFFF7F4F0),
      body: ListView(
        padding: EdgeInsets.zero,
        children: [
          _buildHeader(s),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _locationCard(
                  pickup: true,
                  stop: _origin,
                  title: s.pickupLocation,
                  hint: s.tapPickup,
                  color: const Color(0xFFE65100),
                  icon: Icons.my_location_rounded,
                ),
                const SizedBox(height: 12),
                _locationCard(
                  pickup: false,
                  stop: _destination,
                  title: s.destinationLocation,
                  hint: s.tapDestination,
                  color: const Color(0xFF1B8A4C),
                  icon: Icons.place_rounded,
                  subtitle: _selectedFare == null
                      ? null
                      : 'GHS ${_selectedFare!.toStringAsFixed(2)}',
                ),
                const SizedBox(height: 20),
                Text(
                  s.paymentMethodTitle,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF241C15),
                  ),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: _paymentTile(
                        value: 'cash',
                        icon: Icons.payments_rounded,
                        label: s.cash,
                        hint: s.cashHint,
                        color: const Color(0xFF1B8A4C),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _paymentTile(
                        value: 'wallet',
                        icon: Icons.account_balance_wallet_rounded,
                        label: s.myWallet,
                        hint: s.walletHint,
                        color: const Color(0xFFE65100),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                FilledButton.icon(
                  onPressed: _isLoading ? null : _requestRide,
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFFE65100),
                    disabledBackgroundColor: const Color(0xFFE0A57E),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 18),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(18),
                    ),
                  ),
                  icon: _isLoading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.4,
                            valueColor:
                                AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      : const Icon(Icons.search_rounded, size: 24),
                  label: Text(
                    _isLoading ? s.requestingRide : s.requestRide,
                    style: const TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                if (_currentRide != null) ...[
                  const SizedBox(height: 20),
                  _statusCard(_currentRide!, s),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader(AppStrings s) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFFB33A0B), Color(0xFFE65100), Color(0xFFFB8C00)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(30)),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(8, 8, 20, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  IconButton(
                    tooltip: s.cancel,
                    onPressed: () => Navigator.maybePop(context),
                    icon: const Icon(
                      Icons.arrow_back_rounded,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      s.bookRide,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  IconButton(
                    tooltip: s.listen,
                    onPressed: () => _speak(s.rideBookingHint),
                    icon: const Icon(
                      Icons.volume_up_rounded,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Padding(
                padding: const EdgeInsets.only(left: 12),
                child: Text(
                  s.rideBookingHint,
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.86),
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    height: 1.4,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _locationCard({
    required bool pickup,
    required BusStop? stop,
    required String title,
    required String hint,
    required Color color,
    required IconData icon,
    String? subtitle,
  }) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        borderRadius: BorderRadius.circular(20),
        onTap: () => _pickStop(pickup: pickup),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: stop == null ? const Color(0xFFECE6DF) : color,
              width: stop == null ? 1 : 2,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 14,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Row(
            children: [
              PhotoTile(
                imageUrl: stop?.imageUrl,
                size: 52,
                borderRadius: 16,
                fallbackIcon: icon,
                fallbackColor: color,
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title.toUpperCase(),
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 1.2,
                        color: Color(0xFF9A9086),
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      stop?.name ?? hint,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: stop == null
                            ? const Color(0xFF8A8178)
                            : const Color(0xFF241C15),
                        height: 1.2,
                      ),
                    ),
                    if (subtitle != null) ...[
                      const SizedBox(height: 4),
                      Text(
                        subtitle,
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: color,
                        ),
                      ),
                    ],
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
      ),
    );
  }

  Widget _paymentTile({
    required String value,
    required IconData icon,
    required String label,
    required String hint,
    required Color color,
  }) {
    final selected = _paymentMethod == value;
    return Material(
      color: selected ? color.withValues(alpha: 0.10) : Colors.white,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: () => setState(() => _paymentMethod = value),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: selected ? color : const Color(0xFFECE6DF),
              width: selected ? 2 : 1,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(icon, color: color, size: 26),
                  const Spacer(),
                  Icon(
                    selected
                        ? Icons.check_circle_rounded
                        : Icons.circle_outlined,
                    color: selected ? color : const Color(0xFFC9C0B6),
                    size: 20,
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF241C15),
                ),
              ),
              const SizedBox(height: 2),
              Text(
                hint,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 11.5,
                  color: Color(0xFF8A8178),
                  height: 1.3,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _statusCard(Ride ride, AppStrings s) {
    final color = _statusColor(ride.status);
    final icon = _statusIcon(ride.status);
    final paymentPending =
        ride.status == 'completed' && ride.paymentStatus != 'paid';

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: color.withValues(alpha: 0.25)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(15),
                ),
                child: Icon(icon, color: color, size: 26),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      s.tripStatus.toUpperCase(),
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 1.2,
                        color: Color(0xFF9A9086),
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      s.rideStatusLabel(ride.status),
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: color,
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                tooltip: s.listen,
                onPressed: () => _speak(
                  '${s.rideStatusLabel(ride.status)}. '
                  '${s.rideRouteSpeech(ride.originAddress, ride.destinationAddress)}',
                ),
                icon: const Icon(Icons.volume_up_rounded),
                color: color,
              ),
            ],
          ),
          const SizedBox(height: 12),
          _routeRow(Icons.trip_origin_rounded, ride.originAddress),
          const SizedBox(height: 6),
          _routeRow(Icons.place_rounded, ride.destinationAddress),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              if (ride.fareAmount != null)
                _pill(
                  Icons.payments_outlined,
                  'GHS ${ride.fareAmount!.toStringAsFixed(2)}',
                  const Color(0xFF1B8A4C),
                ),
              if (ride.driver != null)
                _pill(
                  Icons.person_rounded,
                  ride.driver!.name,
                  const Color(0xFF1565C0),
                ),
            ],
          ),
          if (paymentPending) ...[
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: _openPayment,
                style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFF1B8A4C),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
                icon: const Icon(Icons.payments_rounded),
                label: Text(
                  s.payNow,
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
              ),
            ),
          ],
          if (ride.paymentStatus == 'paid') ...[
            const SizedBox(height: 12),
            Row(
              children: [
                const Icon(
                  Icons.check_circle_rounded,
                  color: Color(0xFF1B8A4C),
                  size: 20,
                ),
                const SizedBox(width: 8),
                Text(
                  s.paymentCompleted,
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF1B8A4C),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _routeRow(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, size: 18, color: const Color(0xFF8A8178)),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 13.5,
              fontWeight: FontWeight.w600,
              color: Color(0xFF6B5B4E),
            ),
          ),
        ),
      ],
    );
  }

  Widget _pill(IconData icon, String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: color),
          const SizedBox(width: 6),
          Text(
            text,
            style: TextStyle(
              fontSize: 12.5,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'accepted':
        return const Color(0xFF1565C0);
      case 'driver_arrived':
        return const Color(0xFFE65100);
      case 'in_progress':
        return const Color(0xFF1B8A4C);
      case 'completed':
        return const Color(0xFF1B8A4C);
      case 'cancelled':
        return const Color(0xFFD64545);
      default:
        return const Color(0xFF6B5B4E);
    }
  }

  IconData _statusIcon(String status) {
    switch (status) {
      case 'accepted':
        return Icons.directions_car_rounded;
      case 'driver_arrived':
        return Icons.person_pin_circle_rounded;
      case 'in_progress':
        return Icons.directions_bus_rounded;
      case 'completed':
        return Icons.check_circle_rounded;
      case 'cancelled':
        return Icons.cancel_rounded;
      default:
        return Icons.search_rounded;
    }
  }
}

class _DropoffOption {
  const _DropoffOption({
    required this.stop,
    required this.fare,
    required this.routeId,
  });

  final BusStop stop;
  final double fare;
  final int routeId;
}
