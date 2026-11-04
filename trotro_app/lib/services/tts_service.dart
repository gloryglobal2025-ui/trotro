import 'package:flutter_tts/flutter_tts.dart';

import '../l10n/app_strings.dart';

class TtsService {
  TtsService._();

  static final TtsService instance = TtsService._();

  final FlutterTts _tts = FlutterTts();
  AppLanguage _language = AppLanguage.english;

  Future<void> configure(AppLanguage language) async {
    _language = language;
    try {
      await _tts.setSpeechRate(0.48);
      await _tts.setPitch(1.0);
      await _tts.setVolume(1.0);

      final preferred = language == AppLanguage.twi ? 'tw-GH' : 'en-GH';
      final available = await _tts.isLanguageAvailable(preferred);
      if (available == true) {
        await _tts.setLanguage(preferred);
      } else {
        await _tts.setLanguage('en-GH');
      }
    } catch (_) {
      // Some devices expose no matching voice; keep the platform default.
    }
  }

  Future<void> speak(String text, {AppLanguage? language}) async {
    if (text.trim().isEmpty) return;
    await configure(language ?? _language);
    try {
      await _tts.stop();
      await _tts.speak(text);
    } catch (_) {
      // Speech is best-effort; never block the UI on it.
    }
  }

  Future<void> stop() async {
    try {
      await _tts.stop();
    } catch (_) {}
  }
}
