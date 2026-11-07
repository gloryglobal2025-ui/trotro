import 'package:flutter/material.dart';

import 'app_scope.dart';
import 'screens/add_vehicle_screen.dart';
import 'screens/driver_dashboard.dart';
import 'screens/driver_verification_screen.dart';
import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/ride_booking_screen.dart';
import 'screens/wallet_screen.dart';
import 'services/api_service.dart';
import 'services/app_settings.dart';
import 'services/session_store.dart';
import 'services/tts_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Future.wait([
    AppSettings.instance.load(),
    ApiService.restoreSession(),
  ]);
  await TtsService.instance.configure(AppSettings.instance.language);
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return AppScope(
      settings: AppSettings.instance,
      child: MaterialApp(
        title: 'Trotro',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFFE65100)),
          scaffoldBackgroundColor: const Color(0xFFF7F4F0),
          useMaterial3: true,
        ),
        initialRoute: ApiService.hasToken ? '/' : '/login',
        routes: {
          '/': (context) => const StartupGate(),
          '/login': (context) => const LoginScreen(),
          '/home': (context) => const TrotroHomePage(),
          '/ride-booking': (context) => const RideBookingScreen(),
          '/driver-dashboard': (context) => const DriverDashboard(),
          '/wallet': (context) => const WalletScreen(),
          '/profile': (context) => const ProfileScreen(),
          '/driver-verification': (context) =>
              const DriverVerificationScreen(),
          '/driver-vehicle': (context) => const AddVehicleScreen(),
        },
      ),
    );
  }
}

class StartupGate extends StatefulWidget {
  const StartupGate({super.key});

  @override
  State<StartupGate> createState() => _StartupGateState();
}

class _StartupGateState extends State<StartupGate> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _resolve());
  }

  Future<void> _resolve() async {
    if (!mounted) return;
    if (!ApiService.hasToken) {
      Navigator.pushReplacementNamed(context, '/login');
      return;
    }

    try {
      final user = await ApiService.getCurrentUser();
      if (!mounted) return;
      Navigator.pushReplacementNamed(
        context,
        user.role == 'driver' ? '/driver-dashboard' : '/home',
      );
    } catch (_) {
      final role = await SessionStore.readRole();
      if (!mounted) return;
      if (!ApiService.hasToken) {
        Navigator.pushReplacementNamed(context, '/login');
        return;
      }
      Navigator.pushReplacementNamed(
        context,
        role == 'driver' ? '/driver-dashboard' : '/home',
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final strings = AppSettings.instance.strings;
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFFE65100), Color(0xFFB23C00)],
          ),
        ),
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 88,
                height: 88,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(28),
                ),
                child: const Icon(
                  Icons.directions_bus_rounded,
                  color: Colors.white,
                  size: 56,
                ),
              ),
              const SizedBox(height: 20),
              Text(
                strings.appName.toUpperCase(),
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 28,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 4,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                strings.passengerTag,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.85),
                  fontSize: 12,
                  letterSpacing: 2,
                ),
              ),
              const SizedBox(height: 32),
              const SizedBox(
                width: 28,
                height: 28,
                child: CircularProgressIndicator(
                  strokeWidth: 3,
                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                strings.checkingAccount,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.85),
                  fontSize: 13,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
