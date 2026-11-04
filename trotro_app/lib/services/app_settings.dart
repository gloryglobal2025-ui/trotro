import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../l10n/app_strings.dart';
import 'tts_service.dart';

class AppSettings extends ChangeNotifier {
  AppSettings._();

  static final AppSettings instance = AppSettings._();

  static const _languageKey = 'app_language';
  static const _voiceKey = 'voice_enabled';

  AppLanguage _language = AppLanguage.english;
  bool _voiceEnabled = true;

  AppLanguage get language => _language;
  bool get voiceEnabled => _voiceEnabled;
  AppStrings get strings => AppStrings.of(_language);

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    final code = prefs.getString(_languageKey);
    _language = AppLanguage.values.firstWhere(
      (language) => language.code == code,
      orElse: () => AppLanguage.english,
    );
    _voiceEnabled = prefs.getBool(_voiceKey) ?? true;
    notifyListeners();
  }

  Future<void> setLanguage(AppLanguage language) async {
    if (_language == language) return;
    _language = language;
    notifyListeners();

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_languageKey, language.code);
    await TtsService.instance.configure(language);
  }

  Future<void> setVoiceEnabled(bool enabled) async {
    if (_voiceEnabled == enabled) return;
    _voiceEnabled = enabled;
    notifyListeners();

    if (!enabled) {
      await TtsService.instance.stop();
    }

    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_voiceKey, enabled);
  }
}
