import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../app_scope.dart';
import '../l10n/app_strings.dart';
import '../models/pass.dart';
import '../services/api_service.dart';
import '../services/app_settings.dart';
import '../services/tts_service.dart';

class QrPassScreen extends StatefulWidget {
  const QrPassScreen({super.key});

  @override
  State<QrPassScreen> createState() => _QrPassScreenState();
}

class _QrPassScreenState extends State<QrPassScreen> {
  final _codeController = TextEditingController();
  MobileScannerController? _scanner;

  bool _cameraMode = !kIsWeb;
  bool _lookingUp = false;
  bool _charging = false;
  String? _error;
  PassLookupResult? _data;
  PassChargeResult? _receipt;
  double _lastFare = 0;
  String _lastDestination = '';

  AppSettings get _settings => AppSettings.instance;
  AppStrings get _s => _settings.strings;

  @override
  void initState() {
    super.initState();
    _scanner = MobileScannerController(
      formats: const [BarcodeFormat.qrCode],
      detectionSpeed: DetectionSpeed.noDuplicates,
    );
  }

  @override
  void dispose() {
    _scanner?.dispose();
    _codeController.dispose();
    super.dispose();
  }

  void _setCameraMode(bool camera) {
    if (_cameraMode == camera) return;
    setState(() => _cameraMode = camera);
    if (camera) {
      _scanner?.start();
    } else {
      _scanner?.stop();
    }
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_lookingUp || _data != null || _receipt != null) return;

    String? value;
    for (final barcode in capture.barcodes) {
      final raw = barcode.rawValue;
      if (raw != null && raw.trim().isNotEmpty) {
        value = raw.trim();
        break;
      }
    }

    if (value == null) return;

    await _scanner?.stop();
    await _lookup(value);
  }

  Future<void> _lookup(String rawCode) async {
    final code = rawCode.trim();
    if (code.isEmpty || _lookingUp) return;

    FocusScope.of(context).unfocus();
    setState(() {
      _lookingUp = true;
      _error = null;
      _receipt = null;
    });

    try {
      final result = await ApiService.lookupPass(code);
      if (!mounted) return;

      setState(() {
        _data = result;
        _lookingUp = false;
        _codeController.text = result.pass.code;
      });

      if (!result.pass.isActive) {
        if (_settings.voiceEnabled) {
          await TtsService.instance.speak(_s.passSuspended);
        }
      } else if (_settings.voiceEnabled) {
        await TtsService.instance.speak(
          _s.passGreeting(result.pass.name, result.pass.balance),
        );
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _lookingUp = false;
        _error = _cleanError(e);
      });
    }
  }

  Future<void> _confirmAndCharge(PassRouteOption route) async {
    final data = _data;
    final fare = route.fare;

    if (data == null || fare == null) return;

    if (!data.pass.isActive) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(_s.passSuspended)),
      );
      return;
    }

    if (data.pass.pinLocked) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(_s.pinLockedMessage)),
      );
      return;
    }

    if (data.pass.usesPhoneApproval) {
      await _remoteApproval(route, fare);
      return;
    }

    if (data.pass.pinRequired) {
      await _openPinSheet(route, fare);
      return;
    }

    final confirmed = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) => _ConfirmChargeSheet(
        passengerName: data.pass.name,
        destination: route.label,
        fare: fare,
        balance: data.pass.balance,
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _charging = true);

    try {
      final result = await ApiService.chargePass(
        passId: data.pass.id,
        routeId: route.id,
        fareId: route.fareId,
      );

      if (!mounted) return;
      await _applyReceipt(result, fare, route.label);
    } catch (e) {
      if (!mounted) return;
      setState(() => _charging = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_cleanError(e)),
          backgroundColor: Colors.red.shade700,
        ),
      );
    }
  }

  Future<void> _remoteApproval(PassRouteOption route, double fare) async {
    final data = _data;
    if (data == null) return;

    final outcome = await showModalBottomSheet<_RemoteApprovalOutcome>(
      context: context,
      isScrollControlled: true,
      isDismissible: false,
      enableDrag: false,
      builder: (context) => _RemoteApprovalSheet(
        data: data,
        route: route,
        onCreate: () => ApiService.createPassChargeRequest(
          passId: data.pass.id,
          routeId: route.id,
          fareId: route.fareId,
        ),
        onPoll: (id) => ApiService.getPassChargeRequest(id),
        onCancel: (id) => ApiService.cancelPassChargeRequest(id),
      ),
    );

    if (outcome == null || !mounted) return;

    if (outcome.useDriverPin) {
      await _openPinSheet(route, fare);
      return;
    }

    if (outcome.result != null) {
      await _applyReceipt(outcome.result!, fare, route.label);
    }
  }

  Future<void> _openPinSheet(PassRouteOption route, double fare) async {
    final data = _data;
    if (data == null) return;

    final result = await showModalBottomSheet<PassChargeResult>(
      context: context,
      isScrollControlled: true,
      isDismissible: false,
      enableDrag: false,
      builder: (context) => _PinApprovalSheet(
        data: data,
        route: route,
        onApprove: (pin) => ApiService.chargePass(
          passId: data.pass.id,
          routeId: route.id,
          fareId: route.fareId,
          pin: pin,
        ),
      ),
    );

    if (result == null || !mounted) return;
    await _applyReceipt(result, fare, route.label);
  }

  Future<void> _applyReceipt(
    PassChargeResult result,
    double fare,
    String destination,
  ) async {
    setState(() {
      _charging = false;
      _receipt = result;
      _lastFare = fare;
      _lastDestination = destination;
    });

    if (_settings.voiceEnabled) {
      await TtsService.instance.speak(
        _s.passChargeSpeech(result.pass.name, fare, result.pass.balance),
      );
    }
  }

  void _reset() {
    setState(() {
      _data = null;
      _receipt = null;
      _error = null;
      _codeController.clear();
      _lookingUp = false;
      _charging = false;
    });

    if (_cameraMode) {
      _scanner?.start();
    }
  }

  String _cleanError(Object error) => _cleanErrorText(error);

  @override
  Widget build(BuildContext context) {
    AppScope.of(context);
    final s = _s;

    return Scaffold(
      appBar: AppBar(
        title: Text(s.passTitle),
        backgroundColor: const Color(0xFFE65100),
        foregroundColor: Colors.white,
        actions: [
          if (_data != null || _receipt != null)
            IconButton(
              tooltip: s.scanAgain,
              onPressed: _reset,
              icon: const Icon(Icons.qr_code_scanner),
            ),
        ],
      ),
      body: Column(
        children: [
          if (_charging) const LinearProgressIndicator(minHeight: 3),
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(
                  s.passSubtitle,
                  style: TextStyle(
                    fontSize: 15,
                    color: Colors.grey.shade700,
                  ),
                ),
                const SizedBox(height: 16),
                if (_receipt != null)
                  _buildReceipt(s)
                else if (_data != null)
                  _buildPass(s, _data!)
                else
                  _buildLookup(s),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLookup(AppStrings s) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        SegmentedButton<bool>(
          segments: [
            ButtonSegment(
              value: true,
              icon: const Icon(Icons.qr_code_scanner),
              label: Text(s.scanCard),
            ),
            ButtonSegment(
              value: false,
              icon: const Icon(Icons.keyboard),
              label: Text(s.typeCode),
            ),
          ],
          selected: {_cameraMode},
          onSelectionChanged: (selection) => _setCameraMode(selection.first),
        ),
        const SizedBox(height: 16),
        if (_cameraMode)
          _buildScanner(s)
        else
          _buildCodeForm(s),
        if (_cameraMode) ...[
          const SizedBox(height: 16),
          _buildCodeForm(s, compact: true),
        ],
        if (_error != null) ...[
          const SizedBox(height: 16),
          _ErrorBanner(message: _error!),
        ],
      ],
    );
  }

  Widget _buildScanner(AppStrings s) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(20),
      child: SizedBox(
        height: 260,
        child: Stack(
          fit: StackFit.expand,
          children: [
            MobileScanner(
              controller: _scanner,
              onDetect: _onDetect,
              errorBuilder: (context, error) {
                return Container(
                  color: Colors.black87,
                  padding: const EdgeInsets.all(20),
                  alignment: Alignment.center,
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.no_photography,
                          color: Colors.white70, size: 36),
                      const SizedBox(height: 12),
                      Text(
                        s.passCameraUnavailable,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
            IgnorePointer(
              child: Center(
                child: Container(
                  width: 190,
                  height: 190,
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.white, width: 3),
                    borderRadius: BorderRadius.circular(18),
                  ),
                ),
              ),
            ),
            if (_lookingUp)
              Container(
                color: Colors.black54,
                alignment: Alignment.center,
                child: const CircularProgressIndicator(color: Colors.white),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildCodeForm(AppStrings s, {bool compact = false}) {
    return Container(
      padding: compact
          ? const EdgeInsets.symmetric(horizontal: 12, vertical: 8)
          : EdgeInsets.zero,
      decoration: compact
          ? BoxDecoration(
              color: Colors.grey.shade100,
              borderRadius: BorderRadius.circular(16),
            )
          : null,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (!compact)
            Text(
              s.passCodeLabel,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
          if (!compact) const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _codeController,
                  textCapitalization: TextCapitalization.characters,
                  autocorrect: false,
                  enabled: !_lookingUp,
                  style: TextStyle(
                    fontSize: compact ? 16 : 20,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 1.5,
                  ),
                  decoration: InputDecoration(
                    hintText: s.passCodeHint,
                    filled: true,
                    fillColor: compact ? Colors.white : Colors.grey.shade100,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: BorderSide.none,
                    ),
                    prefixIcon: compact
                        ? null
                        : const Icon(Icons.confirmation_number_outlined),
                  ),
                  onSubmitted: _lookup,
                ),
              ),
              const SizedBox(width: 8),
              SizedBox(
                height: compact ? 52 : 56,
                child: FilledButton(
                  onPressed: _lookingUp
                      ? null
                      : () => _lookup(_codeController.text),
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFFE65100),
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                  ),
                  child: _lookingUp
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : compact
                          ? const Icon(Icons.search)
                          : Text(s.findPass),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildPass(AppStrings s, PassLookupResult data) {
    final pass = data.pass;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFFE65100), Color(0xFFF59E0B)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(22),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 24,
                    backgroundColor: Colors.white24,
                    child: Text(
                      pass.name.isEmpty ? '?' : pass.name[0].toUpperCase(),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          pass.name,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 22,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Text(
                          pass.code,
                          style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 14,
                            letterSpacing: 1.2,
                            fontFamily: 'monospace',
                          ),
                        ),
                      ],
                    ),
                  ),
                  _StatusChip(active: pass.isActive),
                ],
              ),
              const SizedBox(height: 18),
              Text(
                s.passBalance,
                style: const TextStyle(color: Colors.white70, fontSize: 13),
              ),
              const SizedBox(height: 4),
              FittedBox(
                fit: BoxFit.scaleDown,
                alignment: Alignment.centerLeft,
                child: Text(
                  'GHS ${pass.balance.toStringAsFixed(2)}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 36,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const SizedBox(height: 6),
              Text(
                '${pass.rideCount} trips paid with this pass',
                style: const TextStyle(color: Colors.white70, fontSize: 13),
              ),
              if (pass.pinRequired) ...[
                const SizedBox(height: 10),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        pass.usesPhoneApproval
                            ? Icons.phone_iphone
                            : Icons.lock,
                        color: Colors.white,
                        size: 14,
                      ),
                      const SizedBox(width: 5),
                      Text(
                        pass.usesPhoneApproval
                            ? s.phoneApproval
                            : s.pinProtected,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
        if (pass.pinLocked) ...[
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.orange.shade50,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.orange.shade200),
            ),
            child: Row(
              children: [
                Icon(Icons.lock_clock, color: Colors.orange.shade800),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    s.pinLockedMessage,
                    style: TextStyle(
                      color: Colors.orange.shade900,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
        if (!pass.isActive) ...[
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.red.shade50,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.red.shade200),
            ),
            child: Row(
              children: [
                Icon(Icons.block, color: Colors.red.shade700),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    s.passSuspended,
                    style: TextStyle(
                      color: Colors.red.shade800,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 20),
        Text(
          s.chooseDestination,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 10),
        if (data.routes.isEmpty)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Text(
                s.noRoutesForPass,
                style: TextStyle(color: Colors.grey.shade700),
              ),
            ),
          )
        else
          ...data.routes.map(
            (route) => _RouteChargeTile(
              route: route,
              enabled: pass.isActive && !_charging && !pass.pinLocked,
              fareLabel: route.fare == null
                  ? null
                  : 'GHS ${route.fare!.toStringAsFixed(2)}',
              onTap: () => _confirmAndCharge(route),
            ),
          ),
        if (data.recentRides.isNotEmpty) ...[
          const SizedBox(height: 20),
          Text(
            s.passRecentTrips,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: Colors.grey.shade700,
            ),
          ),
          const SizedBox(height: 6),
          ...data.recentRides.take(2).map(
                (ride) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 3),
                  child: Row(
                    children: [
                      Icon(Icons.history,
                          size: 16, color: Colors.grey.shade500),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          ride.destination ?? 'Trip #${ride.id}',
                          style: TextStyle(color: Colors.grey.shade700),
                        ),
                      ),
                      Text(
                        'GHS ${ride.fare.toStringAsFixed(2)}',
                        style: TextStyle(color: Colors.grey.shade700),
                      ),
                    ],
                  ),
                ),
              ),
        ],
        const SizedBox(height: 24),
        OutlinedButton.icon(
          onPressed: _charging ? null : _reset,
          icon: const Icon(Icons.qr_code_scanner),
          label: Text(s.scanAgain),
          style: OutlinedButton.styleFrom(
            padding: const EdgeInsets.symmetric(vertical: 14),
          ),
        ),
      ],
    );
  }

  Widget _buildReceipt(AppStrings s) {
    final result = _receipt!;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const SizedBox(height: 20),
        Center(
          child: Container(
            width: 96,
            height: 96,
            decoration: BoxDecoration(
              color: Colors.green.shade50,
              shape: BoxShape.circle,
            ),
            child: Icon(
              Icons.check_circle,
              color: Colors.green.shade600,
              size: 64,
            ),
          ),
        ),
        const SizedBox(height: 16),
        Text(
          s.passCharged,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 26, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 6),
        Text(
          'GHS ${_lastFare.toStringAsFixed(2)} · $_lastDestination',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 16, color: Colors.grey.shade700),
        ),
        const SizedBox(height: 24),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                Text(
                  result.pass.name,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  s.remainingOnPass,
                  style: TextStyle(color: Colors.grey.shade600),
                ),
                const SizedBox(height: 4),
                Text(
                  'GHS ${result.pass.balance.toStringAsFixed(2)}',
                  style: const TextStyle(
                    fontSize: 32,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF1B8A4C),
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 24),
        FilledButton.icon(
          onPressed: _reset,
          icon: const Icon(Icons.qr_code_scanner),
          label: Text(s.scanAgain),
          style: FilledButton.styleFrom(
            backgroundColor: const Color(0xFFE65100),
            padding: const EdgeInsets.symmetric(vertical: 16),
          ),
        ),
        const SizedBox(height: 8),
        OutlinedButton(
          onPressed: () => Navigator.of(context).pop(),
          style: OutlinedButton.styleFrom(
            padding: const EdgeInsets.symmetric(vertical: 14),
          ),
          child: Text(s.done),
        ),
      ],
    );
  }
}

class _RemoteApprovalOutcome {
  const _RemoteApprovalOutcome({this.result, this.useDriverPin = false});

  final PassChargeResult? result;
  final bool useDriverPin;
}

class _RemoteApprovalSheet extends StatefulWidget {
  const _RemoteApprovalSheet({
    required this.data,
    required this.route,
    required this.onCreate,
    required this.onPoll,
    required this.onCancel,
  });

  final PassLookupResult data;
  final PassRouteOption route;
  final Future<PassChargeRequestStatus> Function() onCreate;
  final Future<PassChargeRequestStatus> Function(int id) onPoll;
  final Future<PassChargeRequestStatus> Function(int id) onCancel;

  @override
  State<_RemoteApprovalSheet> createState() => _RemoteApprovalSheetState();
}

class _RemoteApprovalSheetState extends State<_RemoteApprovalSheet> {
  Timer? _ticker;
  Timer? _pollTimer;
  PassChargeRequestStatus? _status;
  bool _creating = true;
  bool _polling = false;
  bool _busy = false;
  bool _completed = false;
  String? _error;
  int _secondsLeft = 180;

  AppStrings get _s => AppStrings.of(AppSettings.instance.language);

  PassInfo get _pass => _status?.pass ?? widget.data.pass;

  PassChargeRequestInfo? get _request => _status?.request;

  @override
  void initState() {
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((_) => _start());
  }

  @override
  void dispose() {
    _stopTimers();
    super.dispose();
  }

  void _stopTimers() {
    _ticker?.cancel();
    _ticker = null;
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  void _startTimers() {
    _stopTimers();
    _ticker = Timer.periodic(const Duration(seconds: 1), (_) {
      if (!mounted || _secondsLeft <= 0) return;
      setState(() => _secondsLeft--);
    });
    _pollTimer = Timer.periodic(
      const Duration(seconds: 2),
      (_) => _poll(),
    );
  }

  Future<void> _start() async {
    _stopTimers();

    setState(() {
      _creating = true;
      _error = null;
      _status = null;
      _completed = false;
      _secondsLeft = 180;
    });

    try {
      final status = await widget.onCreate();
      if (!mounted) return;

      setState(() {
        _status = status;
        _secondsLeft = status.request.secondsLeft;
        _creating = false;
      });

      if (status.request.isCompleted) {
        _complete(status);
        return;
      }

      _startTimers();

      if (AppSettings.instance.voiceEnabled) {
        await TtsService.instance.speak(
          _s.remoteApprovalSpeech(widget.data.pass.name),
        );
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _creating = false;
        _error = _cleanErrorText(e);
      });
    }
  }

  Future<void> _poll() async {
    if (!mounted || _creating || _busy || _polling) return;

    final request = _request;
    if (request == null || request.isFinished) return;

    _polling = true;

    try {
      final status = await widget.onPoll(request.id);
      if (!mounted) return;

      setState(() {
        _status = status;
        _secondsLeft = status.request.secondsLeft;
      });

      if (status.request.isCompleted) {
        _complete(status);
      } else if (status.request.isFinished) {
        _stopTimers();
        await _speakTerminal(status.request);
      }
    } catch (_) {
      // A dropped poll is harmless; the next tick retries.
    } finally {
      _polling = false;
    }
  }

  void _complete(PassChargeRequestStatus status) {
    if (_completed) return;

    _completed = true;
    _stopTimers();

    final pass = status.pass ?? widget.data.pass;

    Navigator.of(context).pop(
      _RemoteApprovalOutcome(
        result: PassChargeResult(
          message: status.message.isEmpty ? _s.passCharged : status.message,
          pass: pass,
          driverCredited: true,
          rideId: status.request.rideId,
        ),
      ),
    );
  }

  Future<void> _speakTerminal(PassChargeRequestInfo request) async {
    if (!AppSettings.instance.voiceEnabled) return;

    await TtsService.instance.speak(_terminalMessage(request));
  }

  Future<void> _cancel() async {
    if (_busy) return;

    setState(() => _busy = true);

    final request = _request;
    if (request != null && request.isPending) {
      try {
        await widget.onCancel(request.id);
      } catch (_) {
        // The request will expire on its own if the cancel call fails.
      }
    }

    if (!mounted) return;
    Navigator.of(context).pop();
  }

  Future<void> _fallback() async {
    if (_busy) return;

    setState(() => _busy = true);

    final request = _request;
    if (request != null && request.isPending) {
      try {
        await widget.onCancel(request.id);
      } catch (_) {
        // Fall back to the PIN keypad even if the cancel call fails.
      }
    }

    if (!mounted) return;
    Navigator.of(context).pop(const _RemoteApprovalOutcome(useDriverPin: true));
  }

  String _terminalMessage(PassChargeRequestInfo request) {
    if (request.isDeclined) return _s.remoteApprovalDeclined;
    if (request.isExpired) return _s.remoteApprovalExpired;
    if (request.isCancelled) return _s.remoteApprovalCancelled;
    if (request.isFailed) {
      return request.failureReason ?? _s.remoteApprovalFailed;
    }
    return _s.remoteApprovalFailed;
  }

  String _clock(int seconds) {
    final value = seconds.clamp(0, 9999);
    final minutes = value ~/ 60;
    final rest = (value % 60).toString().padLeft(2, '0');

    return '$minutes:$rest';
  }

  @override
  Widget build(BuildContext context) {
    final s = _s;
    final pass = _pass;
    final fare = widget.route.fare ?? 0;
    final remaining =
        (pass.balance - fare).clamp(0.0, double.infinity).toDouble();
    final request = _request;
    final phone = request?.customerPhone ?? _maskPhone(pass.phone);
    final finished = request != null && request.isFinished;

    return PopScope(
      canPop: !_busy,
      child: SafeArea(
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            12,
            20,
            20 + MediaQuery.of(context).viewInsets.bottom,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    CircleAvatar(
                      radius: 22,
                      backgroundColor:
                          const Color(0xFFE65100).withValues(alpha: 0.12),
                      child: Text(
                        pass.name.isEmpty ? '?' : pass.name[0].toUpperCase(),
                        style: const TextStyle(
                          color: Color(0xFFE65100),
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            pass.name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 19,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          if (phone.isNotEmpty)
                            Text(
                              phone,
                              style: TextStyle(
                                fontSize: 13,
                                color: Colors.grey.shade600,
                                letterSpacing: 0.4,
                              ),
                            ),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFFE65100).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(
                            Icons.phone_iphone,
                            size: 13,
                            color: Color(0xFFE65100),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            s.phoneApproval,
                            style: const TextStyle(
                              color: Color(0xFFE65100),
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                _summary(s, fare, remaining),
                const SizedBox(height: 18),
                if (_creating)
                  _statusPanel(
                    icon: const CircularProgressIndicator(),
                    title: s.remoteApprovalSending,
                    body: s.remoteApprovalNoSmartphone,
                  )
                else if (_error != null)
                  _terminalPanel(
                    icon: Icons.error_outline,
                    color: Colors.red,
                    title: s.remoteApprovalFailed,
                    body: _error!,
                  )
                else if (request != null && request.isFinished)
                  _terminalPanel(
                    icon: request.isDeclined
                        ? Icons.block
                        : request.isExpired
                            ? Icons.timer_off_outlined
                            : Icons.error_outline,
                    color: Colors.orange,
                    title: _terminalMessage(request),
                  )
                else
                  _waitingPanel(
                    s,
                    pass,
                    phone,
                  ),
                const SizedBox(height: 18),
                if (finished || _error != null) ...[
                  FilledButton.icon(
                    onPressed: _busy ? null : _start,
                    icon: const Icon(Icons.refresh),
                    label: Text(s.remoteApprovalRetry),
                    style: FilledButton.styleFrom(
                      backgroundColor: const Color(0xFFE65100),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                  ),
                  const SizedBox(height: 8),
                ],
                OutlinedButton.icon(
                  onPressed: _busy ? null : _fallback,
                  icon: const Icon(Icons.dialpad),
                  label: Text(s.remoteApprovalFallback),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                ),
                const SizedBox(height: 4),
                TextButton(
                  onPressed: _busy ? null : _cancel,
                  child: Text(s.cancel),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _summary(AppStrings s, double fare, double remaining) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.grey.shade100,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  widget.route.label,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Text(
                'GHS ${fare.toStringAsFixed(2)}',
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                s.remainingOnPass,
                style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
              ),
              Text(
                'GHS ${remaining.toStringAsFixed(2)}',
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _waitingPanel(
    AppStrings s,
    PassInfo pass,
    String phone,
  ) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFFE65100).withValues(alpha: 0.07),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: const Color(0xFFE65100).withValues(alpha: 0.2),
        ),
      ),
      child: Column(
        children: [
          Container(
            width: 76,
            height: 76,
            decoration: const BoxDecoration(
              color: Colors.white,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.sms_outlined,
              color: Color(0xFFE65100),
              size: 38,
            ),
          ),
          const SizedBox(height: 14),
          Text(
            s.remoteApprovalTitle(pass.name),
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            s.remoteApprovalSent(phone),
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey.shade700,
              height: 1.35,
            ),
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
            decoration: BoxDecoration(
              color: const Color(0xFF1F2937),
              borderRadius: BorderRadius.circular(24),
            ),
            child: Text(
              _clock(_secondsLeft),
              style: const TextStyle(
                color: Colors.white,
                fontSize: 26,
                fontWeight: FontWeight.w800,
                fontFeatures: [FontFeature.tabularFigures()],
              ),
            ),
          ),
          const SizedBox(height: 12),
          Text(
            s.remoteApprovalNoSmartphone,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
          ),
          if (pass.pinAttemptsLeft > 0 && pass.pinAttemptsLeft < 3) ...[
            const SizedBox(height: 4),
            Text(
              s.remoteApprovalAttemptsLeft(pass.pinAttemptsLeft),
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: Colors.orange.shade800,
              ),
            ),
          ],
          const SizedBox(height: 4),
          Text(
            s.remoteApprovalCancelHint,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
          ),
        ],
      ),
    );
  }

  Widget _terminalPanel({
    required IconData icon,
    required MaterialColor color,
    required String title,
    String? body,
  }) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: color.shade50,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: color.shade200),
      ),
      child: Column(
        children: [
          Icon(icon, color: color.shade700, size: 40),
          const SizedBox(height: 10),
          Text(
            title,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: color.shade900,
            ),
          ),
          if (body != null && body.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              body,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 13, color: color.shade800),
            ),
          ],
        ],
      ),
    );
  }

  Widget _statusPanel({
    required Widget icon,
    required String title,
    required String body,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 24),
      child: Column(
        children: [
          icon,
          const SizedBox(height: 14),
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 6),
          Text(
            body,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
          ),
        ],
      ),
    );
  }
}

class _PinApprovalSheet extends StatefulWidget {
  const _PinApprovalSheet({
    required this.data,
    required this.route,
    required this.onApprove,
  });

  final PassLookupResult data;
  final PassRouteOption route;
  final Future<PassChargeResult> Function(String pin) onApprove;

  @override
  State<_PinApprovalSheet> createState() => _PinApprovalSheetState();
}

class _PinApprovalSheetState extends State<_PinApprovalSheet> {
  String _pin = '';
  bool _busy = false;
  bool _locked = false;
  String? _error;
  int _attemptsLeft = 0;

  AppStrings get _s => AppStrings.of(AppSettings.instance.language);

  @override
  void initState() {
    super.initState();
    _locked = widget.data.pass.pinLocked;
    _attemptsLeft = widget.data.pass.pinAttemptsLeft;

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      if (!mounted || !AppSettings.instance.voiceEnabled) return;
      await TtsService.instance.speak(
        _locked
            ? _s.pinLockedMessage
            : _s.pinApprovalSpeech(widget.data.pass.name),
      );
    });
  }

  void _addDigit(String digit) {
    if (_busy || _locked || _pin.length >= 4) return;

    setState(() {
      _pin += digit;
      _error = null;
    });

    if (_pin.length == 4) {
      _submit();
    }
  }

  void _backspace() {
    if (_busy || _locked || _pin.isEmpty) return;
    setState(() => _pin = _pin.substring(0, _pin.length - 1));
  }

  Future<void> _submit() async {
    if (_busy || _locked || _pin.length != 4) return;

    setState(() {
      _busy = true;
      _error = null;
    });

    try {
      final result = await widget.onApprove(_pin);
      if (!mounted) return;
      Navigator.of(context).pop(result);
    } on PassChargeException catch (e) {
      if (!mounted) return;

      if (e.isLocked) {
        setState(() {
          _busy = false;
          _locked = true;
          _pin = '';
          _error = e.message;
        });

        if (AppSettings.instance.voiceEnabled) {
          await TtsService.instance.speak(_s.pinLockedMessage);
        }
        return;
      }

      setState(() {
        _busy = false;
        _pin = '';
        if (e.attemptsLeft != null) {
          _attemptsLeft = e.attemptsLeft!;
        }
        _error = e.isWrongPin && e.attemptsLeft != null
            ? _s.pinWrong(e.attemptsLeft!)
            : e.message;
      });

      if (AppSettings.instance.voiceEnabled) {
        await TtsService.instance.speak(_s.pinWrongSpeech);
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _pin = '';
        _error = _cleanErrorText(e);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = _s;
    final pass = widget.data.pass;
    final fare = widget.route.fare ?? 0;
    final remaining = (pass.balance - fare).clamp(0, double.infinity);
    final phone = _maskPhone(pass.phone);

    return PopScope(
      canPop: !_busy,
      child: SafeArea(
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            12,
            20,
            20 + MediaQuery.of(context).viewInsets.bottom,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    CircleAvatar(
                      radius: 22,
                      backgroundColor:
                          const Color(0xFFE65100).withValues(alpha: 0.12),
                      child: Text(
                        pass.name.isEmpty ? '?' : pass.name[0].toUpperCase(),
                        style: const TextStyle(
                          color: Color(0xFFE65100),
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            pass.name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 19,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          if (phone.isNotEmpty)
                            Text(
                              phone,
                              style: TextStyle(
                                fontSize: 13,
                                color: Colors.grey.shade600,
                                letterSpacing: 0.4,
                              ),
                            ),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFFE65100).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(
                            Icons.lock,
                            size: 13,
                            color: Color(0xFFE65100),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            s.pinProtected,
                            style: const TextStyle(
                              color: Color(0xFFE65100),
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              widget.route.label,
                              style: const TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                          Text(
                            'GHS ${fare.toStringAsFixed(2)}',
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            s.remainingOnPass,
                            style: TextStyle(
                              fontSize: 13,
                              color: Colors.grey.shade600,
                            ),
                          ),
                          Text(
                            'GHS ${remaining.toStringAsFixed(2)}',
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 18),
                Text(
                  s.pinApprovalPrompt(pass.name),
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  s.pinEntryHint,
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
                ),
                const SizedBox(height: 14),
                if (_locked)
                  _LockedNotice(message: _error ?? s.pinLockedMessage)
                else ...[
                  _PinDots(filled: _pin.length),
                  const SizedBox(height: 10),
                  SizedBox(
                    height: 24,
                    child: Center(
                      child: _busy
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(strokeWidth: 3),
                            )
                          : _error != null
                              ? Text(
                                  _error!,
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                    color: Colors.red.shade700,
                                  ),
                                )
                              : Text(
                                  '$_attemptsLeft ${_attemptsLeft == 1 ? 'try' : 'tries'} left',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey.shade500,
                                  ),
                                ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  _buildKeypad(),
                  const SizedBox(height: 10),
                  Text(
                    s.pinApprovalNote,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade500,
                    ),
                  ),
                ],
                const SizedBox(height: 6),
                TextButton(
                  onPressed: _busy ? null : () => Navigator.of(context).pop(),
                  child: Text(s.cancel),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildKeypad() {
    const rows = [
      ['1', '2', '3'],
      ['4', '5', '6'],
      ['7', '8', '9'],
    ];

    return Column(
      children: [
        for (final row in rows)
          Row(
            children: [
              for (final digit in row) _digitButton(digit),
            ],
          ),
        Row(
          children: [
            const Expanded(child: SizedBox(height: 58)),
            _digitButton('0'),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(4),
                child: SizedBox(
                  height: 58,
                  child: TextButton(
                    onPressed:
                        _busy || _pin.isEmpty ? null : _backspace,
                    child: const Icon(Icons.backspace_outlined, size: 24),
                  ),
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _digitButton(String digit) {
    return Expanded(
      child: Padding(
        padding: const EdgeInsets.all(4),
        child: SizedBox(
          height: 58,
          child: OutlinedButton(
            onPressed: _busy || _locked ? null : () => _addDigit(digit),
            style: OutlinedButton.styleFrom(
              foregroundColor: const Color(0xFF1F2937),
              side: BorderSide(color: Colors.grey.shade300),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
              textStyle: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w600,
              ),
            ),
            child: Text(digit),
          ),
        ),
      ),
    );
  }
}

class _PinDots extends StatelessWidget {
  const _PinDots({required this.filled});

  final int filled;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: '$filled of 4 digits entered',
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: List.generate(4, (index) {
          final isFilled = index < filled;

          return Container(
            width: 18,
            height: 18,
            margin: const EdgeInsets.symmetric(horizontal: 8),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isFilled ? const Color(0xFFE65100) : Colors.transparent,
              border: Border.all(
                color: isFilled
                    ? const Color(0xFFE65100)
                    : Colors.grey.shade400,
                width: 2,
              ),
            ),
          );
        }),
      ),
    );
  }
}

class _LockedNotice extends StatelessWidget {
  const _LockedNotice({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.orange.shade50,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.orange.shade200),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.lock_clock, color: Colors.orange.shade800),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                color: Colors.orange.shade900,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

String _maskPhone(String? phone) {
  final digits = (phone ?? '').replaceAll(RegExp(r'[^0-9]'), '');

  if (digits.length < 7) {
    return phone?.trim() ?? '';
  }

  final prefix = digits.substring(0, 3);
  final suffix = digits.substring(digits.length - 4);

  return '$prefix ••• $suffix';
}

String _cleanErrorText(Object error) {
  final text = error.toString();
  return text.startsWith('Exception: ')
      ? text.substring('Exception: '.length)
      : text;
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.active});

  final bool active;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: active ? Colors.white : Colors.red.shade700,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        active ? 'ACTIVE' : 'SUSPENDED',
        style: TextStyle(
          color: active ? const Color(0xFFE65100) : Colors.white,
          fontSize: 11,
          fontWeight: FontWeight.w800,
          letterSpacing: 0.6,
        ),
      ),
    );
  }
}

class _RouteChargeTile extends StatelessWidget {
  const _RouteChargeTile({
    required this.route,
    required this.enabled,
    required this.fareLabel,
    required this.onTap,
  });

  final PassRouteOption route;
  final bool enabled;
  final String? fareLabel;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final hasFare = route.fare != null && route.fare! > 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        enabled: enabled && hasFare,
        onTap: enabled && hasFare ? onTap : null,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        leading: CircleAvatar(
          backgroundColor: const Color(0xFFE65100).withValues(alpha: 0.12),
          child: const Icon(Icons.directions_bus, color: Color(0xFFE65100)),
        ),
        title: Text(
          route.label,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
        subtitle: Text(
          hasFare ? route.routeName : 'No fare set',
          style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              fareLabel ?? '—',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: hasFare ? const Color(0xFF1B8A4C) : Colors.grey,
              ),
            ),
            Icon(
              Icons.chevron_right,
              size: 20,
              color: Colors.grey.shade400,
            ),
          ],
        ),
      ),
    );
  }
}

class _ConfirmChargeSheet extends StatelessWidget {
  const _ConfirmChargeSheet({
    required this.passengerName,
    required this.destination,
    required this.fare,
    required this.balance,
  });

  final String passengerName;
  final String destination;
  final double fare;
  final double balance;

  @override
  Widget build(BuildContext context) {
    final strings = AppStrings.of(AppSettings.instance.language);
    final remaining = (balance - fare).clamp(0, double.infinity);

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 20),
            Text(
              strings.chargeConfirmTitle,
              style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 4),
            Text(
              '$passengerName · $destination',
              style: TextStyle(fontSize: 15, color: Colors.grey.shade700),
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                children: [
                  _AmountRow(
                    label: 'Fare',
                    value: 'GHS ${fare.toStringAsFixed(2)}',
                    emphasized: true,
                  ),
                  const SizedBox(height: 8),
                  _AmountRow(
                    label: strings.remainingOnPass,
                    value: 'GHS ${remaining.toStringAsFixed(2)}',
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            Text(
              strings.chargeConfirmBody,
              style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 20),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(true),
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFF1B8A4C),
                padding: const EdgeInsets.symmetric(vertical: 16),
              ),
              child: Text(
                strings.chargeOnce,
                style: const TextStyle(fontSize: 17),
              ),
            ),
            const SizedBox(height: 8),
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: Text(strings.cancel),
            ),
          ],
        ),
      ),
    );
  }
}

class _AmountRow extends StatelessWidget {
  const _AmountRow({
    required this.label,
    required this.value,
    this.emphasized = false,
  });

  final String label;
  final String value;
  final bool emphasized;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: TextStyle(color: Colors.grey.shade700)),
        Text(
          value,
          style: TextStyle(
            fontSize: emphasized ? 20 : 16,
            fontWeight: emphasized ? FontWeight.w800 : FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.red.shade50,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.red.shade200),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.error_outline, color: Colors.red.shade700),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                color: Colors.red.shade800,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
