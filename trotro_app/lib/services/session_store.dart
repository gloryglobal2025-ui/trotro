import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

class SessionStore {
  SessionStore._();

  static const _secure = FlutterSecureStorage();
  static const _tokenKey = 'trotro.auth_token';
  static const _fallbackTokenKey = 'trotro.auth_token_fallback';
  static const _roleKey = 'trotro.auth_role';

  static Future<String?> readToken() async {
    try {
      final token = await _secure.read(key: _tokenKey);
      if (token != null && token.isNotEmpty) return token;
    } catch (_) {
      // Fall back to preferences where secure storage is unavailable.
    }

    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_fallbackTokenKey);
  }

  static Future<void> writeToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    try {
      await _secure.write(key: _tokenKey, value: token);
      await prefs.remove(_fallbackTokenKey);
    } catch (_) {
      await prefs.setString(_fallbackTokenKey, token);
    }
  }

  static Future<void> clearToken() async {
    try {
      await _secure.delete(key: _tokenKey);
    } catch (_) {}

    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_fallbackTokenKey);
  }

  static Future<void> writeRole(String role) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_roleKey, role);
  }

  static Future<String?> readRole() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_roleKey);
  }

  static Future<void> clearRole() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_roleKey);
  }
}
