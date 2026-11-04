import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../app_scope.dart';
import '../l10n/app_strings.dart';
import '../models/bus_stop.dart';
import '../models/route.dart' as trotro;
import '../services/api_service.dart';
import '../services/app_settings.dart';
import '../services/tts_service.dart';
import '../widgets/photo_tile.dart';

class TrotroHomePage extends StatefulWidget {
  const TrotroHomePage({super.key});

  @override
  State<TrotroHomePage> createState() => _TrotroHomePageState();
}

class _TrotroHomePageState extends State<TrotroHomePage> {
  static const _recentKey = 'recent_destination_ids';
  static const _tileColors = <Color>[
    Color(0xFFE65100),
    Color(0xFF1B8A4C),
    Color(0xFF1565C0),
    Color(0xFF7B1FA2),
    Color(0xFF00838F),
    Color(0xFFC62828),
  ];
  static const _tileIcons = <IconData>[
    Icons.location_city_rounded,
    Icons.directions_bus_rounded,
    Icons.storefront_rounded,
    Icons.park_rounded,
    Icons.school_rounded,
    Icons.local_hospital_rounded,
    Icons.shopping_bag_rounded,
    Icons.train_rounded,
    Icons.home_work_rounded,
    Icons.sports_soccer_rounded,
  ];

  List<BusStop> _stops = const [];
  List<trotro.BusRoute> _routes = const [];
  List<int> _recentIds = const [];
  BusStop? _origin;
  BusStop? _destination;
  bool _loadingStops = true;
  bool _stopsFailed = false;
  bool _searching = false;
  bool _searchFailed = false;
  bool _hasSearched = false;
  bool _locating = false;
  int _searchSeq = 0;
  bool _spokeWelcome = false;

  AppSettings get _settings => AppSettings.instance;
  AppStrings get _s => _settings.strings;

  List<BusStop> get _recentStops {
    final result = <BusStop>[];
    for (final id in _recentIds) {
      for (final stop in _stops) {
        if (stop.id == id) {
          result.add(stop);
          break;
        }
      }
      if (result.length >= 4) break;
    }
    return result;
  }

  @override
  void initState() {
    super.initState();
    _loadRecents();
    _loadStops();
    WidgetsBinding.instance.addPostFrameCallback((_) => _speakWelcome());
  }

  Future<void> _speakWelcome() async {
    if (_spokeWelcome || !mounted || !_settings.voiceEnabled) return;
    _spokeWelcome = true;
    await TtsService.instance.speak(
      _s.homeHelpSpeech,
      language: _settings.language,
    );
  }

  Future<void> _loadRecents() async {
    final prefs = await SharedPreferences.getInstance();
    final stored = prefs.getStringList(_recentKey) ?? const [];
    final ids = stored.map(int.tryParse).whereType<int>().toList();
    if (!mounted) return;
    setState(() => _recentIds = ids);
  }

  Future<void> _rememberDestination(BusStop stop) async {
    final ids = [stop.id, ..._recentIds.where((id) => id != stop.id)]
        .take(4)
        .toList();
    if (mounted) setState(() => _recentIds = ids);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(
      _recentKey,
      ids.map((id) => id.toString()).toList(),
    );
  }

  Future<void> _loadStops() async {
    setState(() {
      _loadingStops = true;
      _stopsFailed = false;
    });
    try {
      final stops = await ApiService.getBusStops();
      if (!mounted) return;
      setState(() {
        _stops = stops;
        _loadingStops = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loadingStops = false;
        _stopsFailed = true;
      });
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

  Future<bool> _useMyLocation() async {
    if (_locating) return false;
    if (_stops.isEmpty) {
      _showMessage(_s.locationUnavailable);
      return false;
    }

    setState(() => _locating = true);
    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        _showMessage(_s.locationUnavailable);
        return false;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        _showMessage(_s.locationDenied);
        return false;
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
        return false;
      }

      if (!mounted) return false;
      setState(() => _origin = nearest);
      if (_settings.voiceEnabled) {
        await TtsService.instance.speak(
          _s.originSet(nearest.name),
          language: _settings.language,
        );
      }
      return true;
    } catch (_) {
      _showMessage(_s.locationUnavailable);
      return false;
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  Future<void> _selectDestination(BusStop stop) async {
    if (_origin?.id == stop.id) {
      _showMessage(_s.sameStop);
      return;
    }

    setState(() => _destination = stop);

    if (_origin != null) {
      await _searchRoutes();
      return;
    }

    final located = await _useMyLocation();
    if (!mounted) return;
    if (located) {
      await _searchRoutes();
      return;
    }

    await _pickOrigin(thenSearch: true);
  }

  Future<void> _pickOrigin({required bool thenSearch}) async {
    final picked = await _showStopPicker();
    if (picked == null || !mounted) return;
    setState(() => _origin = picked);
    if (thenSearch && _destination != null) {
      await _searchRoutes();
    }
  }

  Future<void> _searchRoutes() async {
    final origin = _origin;
    final destination = _destination;
    if (origin == null) {
      _showMessage(_s.chooseWhereYouAre);
      return;
    }
    if (destination == null) return;
    if (origin.id == destination.id) {
      _showMessage(_s.sameStop);
      return;
    }

    final seq = ++_searchSeq;
    setState(() {
      _searching = true;
      _searchFailed = false;
      _hasSearched = false;
      _routes = const [];
    });

    try {
      final found = await ApiService.searchRoutes(origin.id, destination.id);
      if (!mounted || seq != _searchSeq) return;
      setState(() {
        _routes = found;
        _searching = false;
        _hasSearched = true;
      });
      await _rememberDestination(destination);
      if (_settings.voiceEnabled) {
        await _announceResults();
      }
    } catch (_) {
      if (!mounted || seq != _searchSeq) return;
      setState(() {
        _searching = false;
        _searchFailed = true;
      });
      if (_settings.voiceEnabled) {
        await TtsService.instance.speak(
          _s.searchFailed,
          language: _settings.language,
        );
      }
    }
  }

  Future<void> _announceResults() async {
    final destination = _destination;
    if (destination == null) return;

    final parts = <String>[_s.searchSummary(_routes.length, destination.name)];
    if (_routes.isNotEmpty) {
      final route = _routes.first;
      parts.add(
        _s.routeSpeech(
          name: route.routeName,
          from: route.origin?.name ?? _origin?.name,
          to: route.destination?.name ?? destination.name,
          minutes: route.estimatedDuration,
          fare: _fareOf(route),
        ),
      );
    }

    await TtsService.instance.speak(
      parts.join(' '),
      language: _settings.language,
    );
  }

  Future<void> _speakRoute(trotro.BusRoute route) async {
    await TtsService.instance.speak(
      _s.routeSpeech(
        name: route.routeName,
        from: route.origin?.name ?? _origin?.name,
        to: route.destination?.name ?? _destination?.name,
        minutes: route.estimatedDuration,
        fare: _fareOf(route),
      ),
      language: _settings.language,
    );
  }

  double? _fareOf(trotro.BusRoute route) {
    if (route.fares == null || route.fares!.isEmpty) return null;
    return route.fares!.first.amount;
  }

  Future<void> _toggleLanguage(AppSettings settings) async {
    final next = settings.language.next;
    await settings.setLanguage(next);
    if (!mounted) return;
    if (settings.voiceEnabled) {
      await TtsService.instance.speak(
        settings.strings.languageChanged,
        language: next,
      );
    }
  }

  Future<void> _toggleVoice(AppSettings settings) async {
    final enabled = !settings.voiceEnabled;
    await settings.setVoiceEnabled(enabled);
    if (!mounted) return;
    if (enabled) {
      await TtsService.instance.speak(
        settings.strings.voiceOn,
        language: settings.language,
      );
    }
    _showMessage(enabled ? settings.strings.voiceOn : settings.strings.voiceOff);
  }

  Future<void> _confirmLogout(AppSettings settings) async {
    final s = settings.strings;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(s.logOutConfirmTitle),
        content: Text(s.logOutConfirmBody),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(s.cancel),
          ),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFFD64545),
            ),
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(s.logOut),
          ),
        ],
      ),
    );

    if (confirmed != true) return;
    await TtsService.instance.stop();
    await ApiService.logout();
    if (!mounted) return;
    Navigator.of(context).pushReplacementNamed('/login');
  }

  void _showMessage(String message) {
    if (!mounted) return;
    final messenger = ScaffoldMessenger.of(context);
    messenger.hideCurrentSnackBar();
    messenger.showSnackBar(
      SnackBar(
        behavior: SnackBarBehavior.floating,
        content: Text(message),
      ),
    );
  }

  Future<BusStop?> _showStopPicker() async {
    final s = _s;
    return showModalBottomSheet<BusStop>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) {
        return DraggableScrollableSheet(
          initialChildSize: 0.62,
          minChildSize: 0.4,
          maxChildSize: 0.92,
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
                            s.chooseWhereYouAre,
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
                  Expanded(
                    child: ListView.builder(
                      controller: controller,
                      padding: const EdgeInsets.fromLTRB(12, 4, 12, 24),
                      itemCount: _stops.length,
                      itemBuilder: (context, index) {
                        final stop = _stops[index];
                        return Padding(
                          padding: const EdgeInsets.symmetric(vertical: 3),
                          child: ListTile(
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                            tileColor: _origin?.id == stop.id
                                ? const Color(0xFFFFF1E6)
                                : null,
                            leading: PhotoTile(
                              imageUrl: stop.imageUrl,
                              size: 42,
                              borderRadius: 12,
                              fallbackIcon:
                                  _tileIcons[index % _tileIcons.length],
                              fallbackColor:
                                  _tileColors[index % _tileColors.length],
                            ),
                            title: Text(
                              stop.name,
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 15,
                              ),
                            ),
                            subtitle: stop.area == null || stop.area!.isEmpty
                                ? null
                                : Text(stop.area!),
                            trailing: const Icon(Icons.chevron_right_rounded),
                            onTap: () => Navigator.pop(sheetContext, stop),
                          ),
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

  @override
  Widget build(BuildContext context) {
    final settings = AppScope.of(context);
    final s = settings.strings;
    final user = ApiService.currentUser;
    final firstName = user == null || user.name.trim().isEmpty
        ? null
        : user.name.trim().split(RegExp(r'\s+')).first;

    return Scaffold(
      backgroundColor: const Color(0xFFF7F4F0),
      body: RefreshIndicator(
        onRefresh: _loadStops,
        color: const Color(0xFFE65100),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: EdgeInsets.zero,
          children: [
            _buildHeader(settings, s, firstName),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _buildOriginCard(s),
                  const SizedBox(height: 12),
                  _buildHelpCard(s),
                  const SizedBox(height: 16),
                  _buildActionRow(s),
                  if (_recentStops.isNotEmpty) ...[
                    const SizedBox(height: 24),
                    _buildRecent(s),
                  ],
                  const SizedBox(height: 24),
                  _sectionTitle(s.popularStops),
                  const SizedBox(height: 12),
                  if (_loadingStops)
                    _buildLoadingStops(s)
                  else if (_stopsFailed || _stops.isEmpty)
                    _buildStopsError(s)
                  else
                    _buildStopGrid(),
                  if (_searching) ...[
                    const SizedBox(height: 20),
                    _buildSearching(s),
                  ],
                  if (_searchFailed) ...[
                    const SizedBox(height: 20),
                    _buildSearchError(s),
                  ],
                  if (_routes.isNotEmpty) ...[
                    const SizedBox(height: 24),
                    _buildResults(s),
                  ] else if (_hasSearched && !_searching) ...[
                    const SizedBox(height: 20),
                    _buildNoRoutes(s),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader(
    AppSettings settings,
    AppStrings s,
    String? firstName,
  ) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFFB33A0B), Color(0xFFE65100), Color(0xFFFB8C00)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(30)),
      ),
      child: Stack(
        children: [
          Positioned(
            top: -70,
            right: -40,
            child: _decorCircle(180, 0.10),
          ),
          Positioned(
            bottom: -40,
            left: -30,
            child: _decorCircle(130, 0.07),
          ),
          SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 10, 28),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      _buildProfileAvatar(s),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              s.greeting(firstName),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.85),
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            const SizedBox(height: 1),
                            Text(
                              s.appName,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 19,
                                fontWeight: FontWeight.w800,
                                letterSpacing: -0.3,
                              ),
                            ),
                          ],
                        ),
                      ),
                      _buildLanguageChip(settings, s),
                      IconButton(
                        tooltip: settings.voiceEnabled ? s.voiceOn : s.voiceOff,
                        onPressed: () => _toggleVoice(settings),
                        icon: Icon(
                          settings.voiceEnabled
                              ? Icons.volume_up_rounded
                              : Icons.volume_off_rounded,
                          color: Colors.white,
                        ),
                      ),
                      IconButton(
                        tooltip: s.logOut,
                        onPressed: () => _confirmLogout(settings),
                        icon: const Icon(
                          Icons.logout_rounded,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  Text(
                    s.whereTo,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 27,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.6,
                      height: 1.15,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    s.whereToHint,
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.84),
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                      height: 1.4,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildProfileAvatar(AppStrings s) {
    return Tooltip(
      message: s.editProfile,
      child: Material(
        color: Colors.white,
        shape: const CircleBorder(),
        child: InkWell(
          customBorder: const CircleBorder(),
          onTap: _openProfile,
          child: Padding(
            padding: const EdgeInsets.all(3),
            child: PhotoTile(
              imageUrl: ApiService.currentUser?.profileImage,
              size: 38,
              circle: true,
              fallbackIcon: Icons.person_rounded,
              fallbackColor: const Color(0xFFE65100),
              fallbackBackground: const Color(0xFFFDEBDD),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _openProfile() async {
    await Navigator.of(context).pushNamed('/profile');
    if (!mounted) return;
    try {
      await ApiService.getCurrentUser();
    } catch (_) {
      // Keep the cached profile when the refresh fails offline.
    }
    if (mounted) setState(() {});
  }

  Widget _decorCircle(double size, double opacity) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: Colors.white.withValues(alpha: opacity),
      ),
    );
  }

  Widget _buildLanguageChip(AppSettings settings, AppStrings s) {
    return Tooltip(
      message: s.languageLabel,
      child: Material(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(999),
        child: InkWell(
          borderRadius: BorderRadius.circular(999),
          onTap: () => _toggleLanguage(settings),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(
                  Icons.translate_rounded,
                  color: Colors.white,
                  size: 16,
                ),
                const SizedBox(width: 5),
                Text(
                  settings.language.shortLabel,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.5,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildOriginCard(AppStrings s) {
    final origin = _origin;
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        borderRadius: BorderRadius.circular(20),
        onTap: _locating
            ? null
            : () => _pickOrigin(thenSearch: _destination != null),
        child: Container(
          padding: const EdgeInsets.fromLTRB(14, 12, 6, 12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.05),
                blurRadius: 16,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Row(
            children: [
              PhotoTile(
                imageUrl: origin?.imageUrl,
                size: 48,
                borderRadius: 14,
                fallbackIcon: Icons.my_location_rounded,
                fallbackColor: const Color(0xFFE65100),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      s.from.toUpperCase(),
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 1.2,
                        color: Color(0xFF9A9086),
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      origin?.name ?? s.chooseWhereYouAre,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF241C15),
                      ),
                    ),
                  ],
                ),
              ),
              TextButton(
                onPressed: _locating
                    ? null
                    : () => _pickOrigin(thenSearch: _destination != null),
                child: Text(s.change),
              ),
              IconButton(
                tooltip: s.useMyLocation,
                onPressed: _locating
                    ? null
                    : () async {
                        final located = await _useMyLocation();
                        if (located && _destination != null) {
                          await _searchRoutes();
                        }
                      },
                icon: _locating
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(
                        Icons.gps_fixed_rounded,
                        color: Color(0xFFE65100),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHelpCard(AppStrings s) {
    return Material(
      color: const Color(0xFFFFF1E6),
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: () => TtsService.instance.speak(
          s.homeHelpSpeech,
          language: _settings.language,
        ),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: const Color(0xFFE65100).withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.hearing_rounded,
                  color: Color(0xFFE65100),
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  s.homeHelpSpeech,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF7A3A0B),
                    height: 1.35,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              const Icon(
                Icons.volume_up_rounded,
                color: Color(0xFFE65100),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildActionRow(AppStrings s) {
    return Row(
      children: [
        Expanded(
          child: _actionTile(
            icon: Icons.local_taxi_rounded,
            label: s.bookRide,
            color: const Color(0xFF1B8A4C),
            onTap: () => Navigator.pushNamed(context, '/ride-booking'),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _actionTile(
            icon: Icons.account_balance_wallet_rounded,
            label: s.myWallet,
            color: const Color(0xFFE65100),
            onTap: () => Navigator.pushNamed(context, '/wallet'),
          ),
        ),
      ],
    );
  }

  Widget _actionTile({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Semantics(
      button: true,
      label: label,
      child: Material(
        color: color,
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: onTap,
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(18),
              boxShadow: [
                BoxShadow(
                  color: color.withValues(alpha: 0.28),
                  blurRadius: 14,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.20),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, color: Colors.white, size: 21),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    label,
                    maxLines: 2,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 13.5,
                      fontWeight: FontWeight.w700,
                      height: 1.2,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _sectionTitle(String text) {
    return Text(
      text,
      style: const TextStyle(
        fontSize: 17,
        fontWeight: FontWeight.w800,
        color: Color(0xFF241C15),
        letterSpacing: -0.2,
      ),
    );
  }

  Widget _buildRecent(AppStrings s) {
    final recents = _recentStops;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _sectionTitle(s.recent),
        const SizedBox(height: 10),
        SizedBox(
          height: 76,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: recents.length,
            separatorBuilder: (context, index) => const SizedBox(width: 10),
            itemBuilder: (context, index) {
              final stop = recents[index];
              final color = _tileColors[index % _tileColors.length];
              return SizedBox(
                width: 150,
                child: Material(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(16),
                    onTap: () => _selectDestination(stop),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFECE6DF)),
                      ),
                      child: Row(
                        children: [
                          PhotoTile(
                            imageUrl: stop.imageUrl,
                            size: 38,
                            borderRadius: 11,
                            fallbackIcon:
                                _tileIcons[index % _tileIcons.length],
                            fallbackColor: color,
                          ),
                          const SizedBox(width: 9),
                          Expanded(
                            child: Text(
                              stop.name,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFF241C15),
                                height: 1.2,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildLoadingStops(AppStrings s) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const SizedBox(
            width: 20,
            height: 20,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
          const SizedBox(width: 12),
          Text(
            s.loadingStops,
            style: const TextStyle(
              color: Color(0xFF8A8178),
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStopsError(AppStrings s) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFF0D3D3)),
      ),
      child: Column(
        children: [
          const Icon(
            Icons.cloud_off_rounded,
            color: Color(0xFFD64545),
            size: 34,
          ),
          const SizedBox(height: 8),
          Text(
            s.errorLoadingStops,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: Color(0xFF6B4A3A),
            ),
          ),
          const SizedBox(height: 12),
          FilledButton.icon(
            onPressed: _loadStops,
            icon: const Icon(Icons.refresh_rounded, size: 18),
            label: Text(s.retry),
          ),
        ],
      ),
    );
  }

  Widget _buildStopGrid() {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: _stops.length,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 1.12,
      ),
      itemBuilder: (context, index) {
        final stop = _stops[index];
        final color = _tileColors[index % _tileColors.length];
        final icon = _tileIcons[index % _tileIcons.length];
        final selected = _destination?.id == stop.id;

        return Material(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          child: InkWell(
            borderRadius: BorderRadius.circular(18),
            onTap: () => _selectDestination(stop),
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(18),
                border: Border.all(
                  color: selected ? color : const Color(0xFFECE6DF),
                  width: selected ? 2 : 1,
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Expanded(
                    child: PhotoTile(
                      imageUrl: stop.imageUrl,
                      width: double.infinity,
                      height: double.infinity,
                      borderRadius: 14,
                      fallbackIcon: icon,
                      fallbackColor: color,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    stop.name,
                    textAlign: TextAlign.center,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 14.5,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF241C15),
                      height: 1.15,
                    ),
                  ),
                  if (stop.area != null && stop.area!.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(
                      stop.area!,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 10.5,
                        color: Color(0xFF9A9086),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _buildSearching(AppStrings s) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          const SizedBox(
            width: 22,
            height: 22,
            child: CircularProgressIndicator(strokeWidth: 2.2),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Text(
              s.searchingRoutes,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: Color(0xFF6B5B4E),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSearchError(AppStrings s) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFF0D3D3)),
      ),
      child: Column(
        children: [
          const Icon(
            Icons.wifi_off_rounded,
            color: Color(0xFFD64545),
            size: 32,
          ),
          const SizedBox(height: 8),
          Text(
            s.searchFailed,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 13.5,
              fontWeight: FontWeight.w600,
              color: Color(0xFF6B4A3A),
              height: 1.4,
            ),
          ),
          const SizedBox(height: 12),
          FilledButton.icon(
            onPressed: _searchRoutes,
            icon: const Icon(Icons.refresh_rounded, size: 18),
            label: Text(s.retry),
          ),
        ],
      ),
    );
  }

  Widget _buildNoRoutes(AppStrings s) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        children: [
          Container(
            width: 54,
            height: 54,
            decoration: BoxDecoration(
              color: const Color(0xFFE65100).withValues(alpha: 0.10),
              borderRadius: BorderRadius.circular(18),
            ),
            child: const Icon(
              Icons.search_off_rounded,
              color: Color(0xFFE65100),
              size: 28,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            s.noRoutes,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Color(0xFF241C15),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            s.noRoutesHint,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 13,
              color: Color(0xFF8A8178),
              height: 1.4,
            ),
          ),
          const SizedBox(height: 10),
          TextButton.icon(
            onPressed: () => TtsService.instance.speak(
              '${s.noRoutes}. ${s.noRoutesHint}',
              language: _settings.language,
            ),
            icon: const Icon(Icons.volume_up_rounded, size: 18),
            label: Text(s.listen),
          ),
        ],
      ),
    );
  }

  Widget _buildResults(AppStrings s) {
    final destinationName = _destination?.name ?? '';
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(child: _sectionTitle(s.routesTo(destinationName, _routes.length))),
            IconButton(
              tooltip: s.listen,
              onPressed: _announceResults,
              icon: const Icon(Icons.volume_up_rounded),
              color: const Color(0xFFE65100),
            ),
          ],
        ),
        const SizedBox(height: 4),
        ..._routes.map((route) => _buildRouteCard(route, s)),
      ],
    );
  }

  Widget _buildRouteCard(trotro.BusRoute route, AppStrings s) {
    final fare = route.fares != null && route.fares!.isNotEmpty
        ? route.fares!.first
        : null;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.fromLTRB(14, 12, 8, 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 14,
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
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: const Color(0xFFE65100).withValues(alpha: 0.10),
                  borderRadius: BorderRadius.circular(13),
                ),
                child: const Icon(
                  Icons.directions_bus_rounded,
                  color: Color(0xFFE65100),
                  size: 22,
                ),
              ),
              const SizedBox(width: 11),
              Expanded(
                child: Text(
                  route.routeName,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 15.5,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF241C15),
                    height: 1.2,
                  ),
                ),
              ),
              IconButton(
                tooltip: s.listen,
                onPressed: () => _speakRoute(route),
                icon: const Icon(Icons.volume_up_rounded),
                color: const Color(0xFFE65100),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _stopPill(
                Icons.trip_origin_rounded,
                route.origin?.name ?? _origin?.name ?? '',
              ),
              _stopPill(
                Icons.place_rounded,
                route.destination?.name ?? _destination?.name ?? '',
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              if (route.estimatedDuration != null)
                _infoChip(
                  icon: Icons.schedule_rounded,
                  text: s.minutes(route.estimatedDuration!),
                ),
              if (route.estimatedDuration != null && fare != null)
                const SizedBox(width: 8),
              if (fare != null)
                _infoChip(
                  icon: Icons.payments_outlined,
                  text: 'GHS ${fare.amount.toStringAsFixed(2)}',
                  strong: true,
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _stopPill(IconData icon, String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: const Color(0xFFF7F4F0),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: const Color(0xFF8A8178)),
          const SizedBox(width: 6),
          Text(
            text,
            style: const TextStyle(
              fontSize: 12.5,
              fontWeight: FontWeight.w600,
              color: Color(0xFF6B5B4E),
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoChip({
    required IconData icon,
    required String text,
    bool strong = false,
  }) {
    final color = strong ? const Color(0xFF1B8A4C) : const Color(0xFF6B5B4E);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: strong
            ? const Color(0xFF1B8A4C).withValues(alpha: 0.10)
            : const Color(0xFFF7F4F0),
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
}
