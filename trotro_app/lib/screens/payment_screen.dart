import 'package:flutter/material.dart';

import '../app_scope.dart';
import '../l10n/app_strings.dart';
import '../models/ride.dart';
import '../services/api_service.dart';
import '../services/app_settings.dart';
import '../services/tts_service.dart';

class PaymentScreen extends StatefulWidget {
  final Ride ride;

  const PaymentScreen({super.key, required this.ride});

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  bool _isProcessing = false;

  AppSettings get _settings => AppSettings.instance;
  AppStrings get _s => _settings.strings;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _speakIntro());
  }

  Future<void> _speakIntro() async {
    if (!mounted || !_settings.voiceEnabled) return;
    final fare = widget.ride.fareAmount;
    final parts = <String>[_s.rideSummary];
    if (fare != null) parts.add(_s.rideFareSpeech(fare));
    parts.add(
      widget.ride.paymentMethod == 'wallet' ? _s.payFromWallet : _s.cash,
    );
    await TtsService.instance.speak(
      parts.join('. '),
      language: _settings.language,
    );
  }

  Future<void> _speak(String text) async {
    if (!_settings.voiceEnabled) return;
    await TtsService.instance.speak(text, language: _settings.language);
  }

  Future<void> _processPayment() async {
    setState(() => _isProcessing = true);

    try {
      final response = await ApiService.processPayment(
        rideId: widget.ride.id,
        paymentMethod: widget.ride.paymentMethod ?? 'cash',
      );

      await _speak(_s.paymentCompleted);

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(response['message'] ?? _s.paymentCompleted),
          backgroundColor: const Color(0xFF1B8A4C),
        ),
      );
      Navigator.pop(context, true);
    } catch (_) {
      if (!mounted) return;
      setState(() => _isProcessing = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_s.paymentFailed),
          backgroundColor: const Color(0xFFD64545),
        ),
      );
      await _speak(_s.paymentFailed);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context).strings;
    final ride = widget.ride;
    final isWallet = ride.paymentMethod == 'wallet';
    final color = isWallet ? const Color(0xFFE65100) : const Color(0xFF1B8A4C);

    return Scaffold(
      backgroundColor: const Color(0xFFF7F4F0),
      body: ListView(
        padding: EdgeInsets.zero,
        children: [
          _buildHeader(s, isWallet),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(22),
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
                            width: 46,
                            height: 46,
                            decoration: BoxDecoration(
                              color: color.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: Icon(
                              isWallet
                                  ? Icons.account_balance_wallet_rounded
                                  : Icons.payments_rounded,
                              color: color,
                              size: 24,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              s.rideSummary.toUpperCase(),
                              style: const TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 1.2,
                                color: Color(0xFF9A9086),
                              ),
                            ),
                          ),
                          IconButton(
                            tooltip: s.listen,
                            onPressed: _speakIntro,
                            icon: const Icon(Icons.volume_up_rounded),
                            color: color,
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      _routeRow(Icons.trip_origin_rounded, ride.originAddress),
                      const SizedBox(height: 8),
                      _routeRow(Icons.place_rounded, ride.destinationAddress),
                      const Divider(height: 26),
                      Row(
                        children: [
                          Text(
                            s.totalFare,
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF6B5B4E),
                            ),
                          ),
                          const Spacer(),
                          Text(
                            'GHS ${ride.fareAmount?.toStringAsFixed(2) ?? '0.00'}',
                            style: const TextStyle(
                              fontSize: 28,
                              fontWeight: FontWeight.w900,
                              color: Color(0xFF1B8A4C),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(
                      color: color.withValues(alpha: 0.35),
                      width: 2,
                    ),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: color.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(13),
                        ),
                        child: Icon(
                          isWallet
                              ? Icons.account_balance_wallet_rounded
                              : Icons.payments_rounded,
                          color: color,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isWallet ? s.myWallet : s.cash,
                              style: const TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.w800,
                                color: Color(0xFF241C15),
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              isWallet ? s.walletConfirmNote : s.cashHint,
                              style: const TextStyle(
                                fontSize: 12,
                                color: Color(0xFF8A8178),
                                height: 1.35,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                FilledButton.icon(
                  onPressed: _isProcessing ? null : _processPayment,
                  style: FilledButton.styleFrom(
                    backgroundColor: color,
                    disabledBackgroundColor: color.withValues(alpha: 0.5),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 18),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(18),
                    ),
                  ),
                  icon: _isProcessing
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.4,
                            valueColor:
                                AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      : const Icon(Icons.check_circle_rounded, size: 24),
                  label: Text(
                    _isProcessing
                        ? s.processing
                        : (isWallet ? s.payFromWallet : s.confirmCash),
                    style: const TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                const SizedBox(height: 14),
                Text(
                  isWallet ? s.walletConfirmNote : s.cashConfirmNote,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 12,
                    color: Color(0xFF8A8178),
                    height: 1.4,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader(AppStrings s, bool isWallet) {
    final color = isWallet
        ? const Color(0xFFE65100)
        : const Color(0xFF1B8A4C);
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            Color.lerp(color, Colors.black, 0.22)!,
            color,
            Color.lerp(color, Colors.white, 0.18)!,
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(30)),
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
                      s.paymentTitle,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Padding(
                padding: const EdgeInsets.only(left: 12),
                child: Text(
                  isWallet ? s.walletHint : s.cashHint,
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.88),
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ],
          ),
        ),
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
              height: 1.3,
            ),
          ),
        ),
      ],
    );
  }
}
