import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../app_scope.dart';
import '../l10n/app_strings.dart';
import '../services/api_service.dart';
import '../services/app_settings.dart';
import '../services/tts_service.dart';
import '../widgets/advert_banner.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  static const _passengerGradient = <Color>[
    Color(0xFFB33A0B),
    Color(0xFFE65100),
    Color(0xFFFB8C00),
  ];
  static const _driverGradient = <Color>[
    Color(0xFF07351F),
    Color(0xFF0E5A34),
    Color(0xFF2E9E5B),
  ];

  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();

  String _selectedRole = 'passenger';
  bool _isRegistering = false;
  bool _obscurePassword = true;
  bool _isLoading = false;

  AppSettings get _settings => AppSettings.instance;
  AppStrings get _s => _settings.strings;

  bool get _isDriver => _selectedRole == 'driver';
  Color get _accent =>
      _isDriver ? const Color(0xFF1B8A4C) : const Color(0xFFE65100);
  List<Color> get _headerGradient =>
      _isDriver ? _driverGradient : _passengerGradient;

  String get _headline {
    if (_isRegistering) {
      return _isDriver ? _s.headlineDrive : _s.headlineRide;
    }
    return _s.welcomeBack;
  }

  String get _subtitle {
    if (_isRegistering) {
      return _isDriver
          ? _s.subtitleRegisterDriver
          : _s.subtitleRegisterPassenger;
    }
    return _isDriver ? _s.subtitleLoginDriver : _s.subtitleLoginPassenger;
  }

  String get _formTitle {
    if (_isRegistering) {
      return _isDriver ? _s.createDriverAccount : _s.createPassengerAccount;
    }
    return _isDriver ? _s.signInToDrive : _s.signInToRide;
  }

  String get _formSubtitle =>
      _isRegistering ? _s.formSubtitleRegister : _s.formSubtitleSignIn;

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _selectRole(String role) {
    if (_isLoading || role == _selectedRole) return;
    setState(() => _selectedRole = role);
  }

  void _setMode(bool registering) {
    if (_isLoading || registering == _isRegistering) return;
    setState(() => _isRegistering = registering);
  }

  Future<void> _toggleLanguage() async {
    final next = _settings.language.next;
    await _settings.setLanguage(next);
    if (_settings.voiceEnabled) {
      await TtsService.instance.speak(
        AppStrings.of(next).languageChanged,
        language: next,
      );
    }
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;
    TextInput.finishAutofillContext();

    setState(() => _isLoading = true);

    try {
      final Map<String, dynamic> data;
      if (_isRegistering) {
        data = await ApiService.register(
          name: _nameController.text.trim(),
          email: _emailController.text.trim(),
          password: _passwordController.text,
          phone: _phoneController.text.trim(),
          role: _selectedRole,
        );
      } else {
        data = await ApiService.login(
          email: _emailController.text.trim(),
          password: _passwordController.text,
        );
      }

      if (!mounted) return;

      final user = data['user'] as Map<String, dynamic>?;
      final role = user?['role']?.toString() ?? _selectedRole;

      if (role != _selectedRole) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            behavior: SnackBarBehavior.floating,
            content: Text(role == 'driver'
                ? _s.signedInAsDriver
                : _s.signedInAsPassenger),
          ),
        );
      }

      Navigator.pushReplacementNamed(
        context,
        role == 'driver' ? '/driver-dashboard' : '/home',
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          behavior: SnackBarBehavior.floating,
          backgroundColor: const Color(0xFF3D2B1F),
          content: Row(
            children: [
              const Icon(Icons.error_outline_rounded,
                  color: Colors.white, size: 18),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  _messageFor(error),
                  style: const TextStyle(color: Colors.white),
                ),
              ),
            ],
          ),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  String _messageFor(Object error) {
    const prefix = 'Exception: ';
    final message = error.toString();
    return message.startsWith(prefix) ? message.substring(prefix.length) : message;
  }

  String? _validateName(String? value) {
    if (value == null || value.trim().isEmpty) return _s.enterFullName;
    return null;
  }

  String? _validatePhone(String? value) {
    final digits = (value ?? '').replaceAll(RegExp(r'[^0-9]'), '');
    if (digits.isEmpty) return _s.enterPhone;
    if (digits.length < 9) return _s.validPhone;
    return null;
  }

  String? _validateEmail(String? value) {
    final email = value?.trim() ?? '';
    if (email.isEmpty) return _s.enterEmail;
    if (!RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(email)) {
      return _s.validEmail;
    }
    return null;
  }

  String? _validatePassword(String? value) {
    final password = value ?? '';
    if (password.isEmpty) return _s.enterPassword;
    if (_isRegistering && password.length < 8) return _s.minPasswordHint;
    return null;
  }

  @override
  Widget build(BuildContext context) {
    AppScope.of(context);
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: const Color(0xFFF7F4F0),
        body: SingleChildScrollView(
          child: Column(
            children: [
              _buildHeader(),
              Padding(
                padding: const EdgeInsets.fromLTRB(18, 0, 18, 24),
                child: Transform.translate(
                  offset: const Offset(0, -26),
                  child: _buildCard(),
                ),
              ),
              const Padding(
                padding: EdgeInsets.fromLTRB(18, 4, 18, 8),
                child: AdvertBanner(
                  placement: 'home',
                  height: 136,
                  margin: EdgeInsets.zero,
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(24, 2, 24, 32),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.shield_outlined,
                        size: 14, color: Color(0xFF9A9086)),
                    const SizedBox(width: 6),
                    Text(
                      _s.secureFooter,
                      style: const TextStyle(
                          fontSize: 12, color: Color(0xFF9A9086)),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 450),
      curve: Curves.easeOutCubic,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: _headerGradient,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Stack(
        children: [
          Positioned(top: -70, right: -50, child: _decorCircle(200, 0.10)),
          Positioned(top: 92, right: 46, child: _decorCircle(66, 0.07)),
          Positioned(bottom: -60, left: -40, child: _decorCircle(170, 0.08)),
          SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(24, 20, 24, 52),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildBrandRow(),
                  const SizedBox(height: 30),
                  _buildRoleSwitch(),
                  const SizedBox(height: 26),
                  AnimatedSwitcher(
                    duration: const Duration(milliseconds: 250),
                    child: Column(
                      key: ValueKey('$_selectedRole-$_isRegistering-${_s.language.code}'),
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _headline,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 30,
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.6,
                            height: 1.1,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          _subtitle,
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.82),
                            fontSize: 14.5,
                            fontWeight: FontWeight.w500,
                            height: 1.4,
                          ),
                        ),
                      ],
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

  Widget _buildBrandRow() {
    return Row(
      children: [
        Container(
          width: 46,
          height: 46,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(15),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.12),
                blurRadius: 12,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Icon(Icons.directions_bus_rounded, color: _accent, size: 26),
        ),
        const SizedBox(width: 12),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              _s.appName,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 22,
                fontWeight: FontWeight.w800,
                letterSpacing: -0.4,
              ),
            ),
            Text(
              _isDriver ? _s.driverTag : _s.passengerTag,
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.72),
                fontSize: 10,
                fontWeight: FontWeight.w700,
                letterSpacing: 1.6,
              ),
            ),
          ],
        ),
        const Spacer(),
        _buildLanguageChip(),
      ],
    );
  }

  Widget _buildLanguageChip() {
    return Semantics(
      button: true,
      label: '${_s.languageLabel}: ${_settings.language.shortLabel}',
      child: ExcludeSemantics(
        child: GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: _toggleLanguage,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.16),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: Colors.white.withValues(alpha: 0.24)),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.translate_rounded,
                    size: 16, color: Colors.white),
                const SizedBox(width: 6),
                Text(
                  _settings.language.shortLabel,
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

  Widget _buildRoleSwitch() {
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withValues(alpha: 0.18)),
      ),
      child: Row(
        children: [
          _rolePill('passenger', Icons.person_rounded, _s.passenger),
          _rolePill('driver', Icons.directions_bus_rounded, _s.driver),
        ],
      ),
    );
  }

  Widget _rolePill(String role, IconData icon, String label) {
    final selected = _selectedRole == role;
    return Expanded(
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () => _selectRole(role),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeOut,
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: selected ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(12),
            boxShadow: selected
                ? [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.10),
                      blurRadius: 8,
                      offset: const Offset(0, 3),
                    ),
                  ]
                : null,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                icon,
                size: 18,
                color: selected ? _accent : Colors.white.withValues(alpha: 0.8),
              ),
              const SizedBox(width: 8),
              Text(
                label,
                style: TextStyle(
                  color:
                      selected ? _accent : Colors.white.withValues(alpha: 0.9),
                  fontSize: 14,
                  fontWeight: selected ? FontWeight.w700 : FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCard() {
    return Container(
      padding: const EdgeInsets.fromLTRB(22, 22, 22, 24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF3D2B1F).withValues(alpha: 0.12),
            blurRadius: 36,
            offset: const Offset(0, 18),
          ),
        ],
      ),
      child: Form(
        key: _formKey,
        child: AutofillGroup(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _buildModeSwitch(),
              const SizedBox(height: 20),
              Text(
                _formTitle,
                style: const TextStyle(
                  fontSize: 21,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF241C15),
                  letterSpacing: -0.3,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                _formSubtitle,
                style: const TextStyle(
                  fontSize: 13.5,
                  color: Color(0xFF8A8178),
                  height: 1.4,
                ),
              ),
              const SizedBox(height: 20),
              if (_isRegistering) ...[
                _buildField(
                  controller: _nameController,
                  label: _s.fullName,
                  icon: Icons.person_outline_rounded,
                  autofillHints: const [AutofillHints.name],
                  validator: _validateName,
                ),
                const SizedBox(height: 14),
                _buildField(
                  controller: _phoneController,
                  label: _s.phoneNumber,
                  icon: Icons.phone_outlined,
                  keyboardType: TextInputType.phone,
                  autofillHints: const [AutofillHints.telephoneNumber],
                  validator: _validatePhone,
                ),
                const SizedBox(height: 14),
              ],
              _buildField(
                controller: _emailController,
                label: _s.email,
                icon: Icons.mail_outline_rounded,
                keyboardType: TextInputType.emailAddress,
                autofillHints: const [AutofillHints.email],
                validator: _validateEmail,
              ),
              const SizedBox(height: 14),
              _buildField(
                controller: _passwordController,
                label: _s.password,
                icon: Icons.lock_outline_rounded,
                obscureText: _obscurePassword,
                textInputAction: TextInputAction.done,
                autofillHints: [
                  _isRegistering
                      ? AutofillHints.newPassword
                      : AutofillHints.password,
                ],
                validator: _validatePassword,
                onFieldSubmitted: (_) => _submit(),
                suffixIcon: IconButton(
                  onPressed: () =>
                      setState(() => _obscurePassword = !_obscurePassword),
                  tooltip:
                      _obscurePassword ? _s.showPassword : _s.hidePassword,
                  icon: Icon(
                    _obscurePassword
                        ? Icons.visibility_off_outlined
                        : Icons.visibility_outlined,
                    size: 20,
                    color: const Color(0xFF9A9086),
                  ),
                ),
              ),
              if (_isRegistering)
                Padding(
                  padding: const EdgeInsets.only(top: 8, left: 4),
                  child: Row(
                    children: [
                      const Icon(Icons.lock_outline_rounded,
                          size: 13, color: Color(0xFF9A9086)),
                      const SizedBox(width: 6),
                      Text(
                        _s.passwordHint,
                        style: const TextStyle(
                            fontSize: 11.5, color: Color(0xFF9A9086)),
                      ),
                    ],
                  ),
                ),
              const SizedBox(height: 22),
              _buildSubmitButton(),
              if (_isDriver && _isRegistering) ...[
                const SizedBox(height: 16),
                _buildDriverNote(),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildModeSwitch() {
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: const Color(0xFFF4F0EA),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: [
          _modeTab(_s.signIn, false),
          _modeTab(_s.createAccount, true),
        ],
      ),
    );
  }

  Widget _modeTab(String label, bool registering) {
    final selected = _isRegistering == registering;
    return Expanded(
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () => _setMode(registering),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 11),
          decoration: BoxDecoration(
            color: selected ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(11),
            boxShadow: selected
                ? [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.07),
                      blurRadius: 6,
                      offset: const Offset(0, 2),
                    ),
                  ]
                : null,
          ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 13.5,
              fontWeight: selected ? FontWeight.w700 : FontWeight.w600,
              color: selected
                  ? const Color(0xFF241C15)
                  : const Color(0xFF9A9086),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    TextInputType? keyboardType,
    TextInputAction textInputAction = TextInputAction.next,
    bool obscureText = false,
    Widget? suffixIcon,
    Iterable<String>? autofillHints,
    String? Function(String?)? validator,
    void Function(String)? onFieldSubmitted,
  }) {
    final border = OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: BorderSide.none,
    );

    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      textInputAction: textInputAction,
      obscureText: obscureText,
      autofillHints: autofillHints,
      validator: validator,
      onFieldSubmitted: onFieldSubmitted,
      style: const TextStyle(
        fontSize: 15,
        fontWeight: FontWeight.w600,
        color: Color(0xFF241C15),
      ),
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, size: 20, color: const Color(0xFF9A9086)),
        suffixIcon: suffixIcon,
        filled: true,
        fillColor: const Color(0xFFF7F4F0),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 17),
        labelStyle: const TextStyle(fontSize: 14, color: Color(0xFF9A9086)),
        floatingLabelStyle: TextStyle(
          fontSize: 13,
          color: _accent,
          fontWeight: FontWeight.w600,
        ),
        border: border,
        enabledBorder: border,
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: _accent, width: 1.6),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: Color(0xFFD64545), width: 1.2),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: Color(0xFFD64545), width: 1.6),
        ),
        errorStyle: const TextStyle(fontSize: 12, color: Color(0xFFD64545)),
      ),
    );
  }

  Widget _buildSubmitButton() {
    final colors = _isDriver
        ? const [Color(0xFF0E5A34), Color(0xFF2E9E5B)]
        : const [Color(0xFFD84315), Color(0xFFFB8C00)];

    return SizedBox(
      height: 54,
      child: DecoratedBox(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: colors,
            begin: Alignment.centerLeft,
            end: Alignment.centerRight,
          ),
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: _accent.withValues(alpha: 0.30),
              blurRadius: 18,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: _isLoading ? null : _submit,
            child: Center(
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 200),
                child: _isLoading
                    ? const SizedBox(
                        key: ValueKey('loading'),
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(
                          strokeWidth: 2.4,
                          color: Colors.white,
                        ),
                      )
                    : Row(
                        key: const ValueKey('label'),
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            _isRegistering
                                ? _s.createAccount
                                : _s.signIn,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 16,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(width: 8),
                          const Icon(Icons.arrow_forward_rounded,
                              color: Colors.white, size: 18),
                        ],
                      ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDriverNote() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: _accent.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _accent.withValues(alpha: 0.18)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info_outline_rounded, size: 18, color: _accent),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              _s.driverNote,
              style: TextStyle(
                fontSize: 12.5,
                height: 1.45,
                color: _accent,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
