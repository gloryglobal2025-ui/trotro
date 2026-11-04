import 'dart:async';
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../models/ride.dart';

class RealtimeService {
  static Timer? _pollingTimer;
  static Function(Ride)? _onRideUpdate;
  static String? _currentRideId;

  static void startPolling({
    required Function(Ride) onRideUpdate,
    Duration interval = const Duration(seconds: 5),
  }) {
    _onRideUpdate = onRideUpdate;
    _pollingTimer?.cancel();
    _pollingTimer = Timer.periodic(interval, (timer) async {
      await _checkForUpdates();
    });
  }

  static void stopPolling() {
    _pollingTimer?.cancel();
    _pollingTimer = null;
    _onRideUpdate = null;
    _currentRideId = null;
  }

  static void setCurrentRideId(String? rideId) {
    _currentRideId = rideId;
  }

  static Future<void> _checkForUpdates() async {
    if (_currentRideId == null) return;

    try {
      final rides = await ApiService.getRides();
      final index = rides.indexWhere((r) => r.id.toString() == _currentRideId);
      if (index == -1) return;
      _onRideUpdate?.call(rides[index]);
    } catch (_) {
      // Transient failures are retried on the next poll.
    }
  }
}

mixin RealtimeMixin<T extends StatefulWidget> on State<T> {
  void startRealtimeUpdates({
    required String rideId,
    required Function(Ride) onUpdate,
  }) {
    RealtimeService.setCurrentRideId(rideId);
    RealtimeService.startPolling(
      onRideUpdate: (ride) {
        if (mounted) {
          onUpdate(ride);
        }
      },
    );
  }

  void stopRealtimeUpdates() {
    RealtimeService.stopPolling();
  }

  @override
  void dispose() {
    stopRealtimeUpdates();
    super.dispose();
  }
}
