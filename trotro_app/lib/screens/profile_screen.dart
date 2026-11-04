import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../app_scope.dart';
import '../l10n/app_strings.dart';
import '../models/user.dart';
import '../services/api_service.dart';
import '../widgets/photo_tile.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  static const Color _accent = Color(0xFFE65100);
  static const int _maxPhotoBytes = 1900 * 1024;

  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();

  User? _user;
  String? _photoUrl;
  bool _saving = false;
  bool _uploading = false;

  @override
  void initState() {
    super.initState();
    final user = ApiService.currentUser;
    _user = user;
    _photoUrl = user?.profileImage;
    _nameController.text = user?.name ?? '';
    _emailController.text = user?.email ?? '';
    _phoneController.text = user?.phone ?? '';
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _pickPhoto(AppStrings s) async {
    if (_uploading) return;

    final XFile? file;
    try {
      file = await ImagePicker().pickImage(
        source: ImageSource.gallery,
        maxWidth: 1200,
        maxHeight: 1200,
        imageQuality: 85,
      );
    } catch (_) {
      _showMessage(s.photoPickFailed);
      return;
    }
    if (file == null) return;

    final bytes = await file.readAsBytes();
    if (bytes.length > _maxPhotoBytes) {
      _showMessage(s.photoTooLarge);
      return;
    }

    setState(() => _uploading = true);
    try {
      final url = await ApiService.uploadProfilePhoto(
        bytes,
        filename: file.name.isEmpty ? 'profile.jpg' : file.name,
      );
      if (!mounted) return;
      setState(() => _photoUrl = url);
      _showMessage(s.profilePhotoUpdated);
    } catch (error) {
      if (!mounted) return;
      _showMessage(_cleanError(error, s));
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  Future<void> _save(AppStrings s) async {
    FocusScope.of(context).unfocus();
    if (_saving || !_formKey.currentState!.validate()) return;

    setState(() => _saving = true);
    try {
      final phone = _phoneController.text.trim();
      final user = await ApiService.updateProfile(
        name: _nameController.text.trim(),
        email: _emailController.text.trim(),
        phone: phone.isEmpty ? null : phone,
      );
      if (!mounted) return;
      setState(() {
        _user = user;
        _photoUrl = user.profileImage;
      });
      _showMessage(s.profileUpdated);
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      _showMessage(_cleanError(error, s));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  String _cleanError(Object error, AppStrings s) {
    final message = error.toString().replaceFirst('Exception: ', '');
    return message.isEmpty ? s.profileUpdateFailed : message;
  }

  void _showMessage(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final settings = AppScope.of(context);
    final s = settings.strings;
    final user = _user;
    final roleLabel = user?.role == 'driver' ? s.driver : s.passenger;

    return Scaffold(
      backgroundColor: const Color(0xFFF7F4F0),
      appBar: AppBar(
        backgroundColor: const Color(0xFFF7F4F0),
        elevation: 0,
        foregroundColor: const Color(0xFF241C15),
        title: Text(
          s.myProfile,
          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 20),
        ),
      ),
      body: SafeArea(
        top: false,
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
            children: [
              Center(
                child: _buildAvatar(s),
              ),
              const SizedBox(height: 10),
              Text(
                s.profileHint,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Color(0xFF9A9086),
                  fontSize: 12.5,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                    decoration: BoxDecoration(
                      color: _accent.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          user?.role == 'driver'
                              ? Icons.directions_bus_rounded
                              : Icons.person_rounded,
                          size: 15,
                          color: _accent,
                        ),
                        const SizedBox(width: 6),
                        Text(
                          roleLabel,
                          style: const TextStyle(
                            color: _accent,
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (user != null) ...[
                    const SizedBox(width: 10),
                    const Icon(Icons.star_rounded, size: 18, color: Colors.amber),
                    const SizedBox(width: 3),
                    Text(
                      user.rating.toStringAsFixed(1),
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                      ),
                    ),
                  ],
                ],
              ),
              const SizedBox(height: 24),
              Text(
                s.yourDetails,
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF241C15),
                ),
              ),
              const SizedBox(height: 12),
              _buildField(
                controller: _nameController,
                label: s.fullName,
                icon: Icons.person_outline_rounded,
                textInputAction: TextInputAction.next,
                textCapitalization: TextCapitalization.words,
                validator: (value) =>
                    (value == null || value.trim().isEmpty)
                        ? s.enterFullName
                        : null,
              ),
              const SizedBox(height: 12),
              _buildField(
                controller: _emailController,
                label: s.email,
                helper: s.profileEmailHint,
                icon: Icons.mail_outline_rounded,
                keyboardType: TextInputType.emailAddress,
                textInputAction: TextInputAction.next,
                validator: (value) {
                  final email = value?.trim() ?? '';
                  final valid =
                      RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(email);
                  return valid ? null : s.validEmail;
                },
              ),
              const SizedBox(height: 12),
              _buildField(
                controller: _phoneController,
                label: s.phoneOptional,
                icon: Icons.phone_outlined,
                keyboardType: TextInputType.phone,
                textInputAction: TextInputAction.done,
                validator: (value) {
                  final phone = value?.trim() ?? '';
                  if (phone.isEmpty) return null;
                  return RegExp(r'^\+?[0-9 ()-]{7,20}$').hasMatch(phone)
                      ? null
                      : s.validPhone;
                },
              ),
              const SizedBox(height: 26),
              SizedBox(
                height: 54,
                child: FilledButton.icon(
                  onPressed: _saving ? null : () => _save(s),
                  style: FilledButton.styleFrom(
                    backgroundColor: _accent,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                  icon: _saving
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.4,
                            valueColor:
                                AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      : const Icon(Icons.check_rounded),
                  label: Text(
                    _saving ? s.saving : s.saveChanges,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildAvatar(AppStrings s) {
    return Tooltip(
      message: s.changePhoto,
      child: Semantics(
        button: true,
        label: s.changePhoto,
        child: GestureDetector(
          onTap: () => _pickPhoto(s),
          child: Stack(
            children: [
              Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.08),
                      blurRadius: 14,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: PhotoTile(
                  imageUrl: _photoUrl,
                  size: 112,
                  circle: true,
                  fallbackIcon: Icons.person_rounded,
                  fallbackColor: _accent,
                  fallbackBackground: const Color(0xFFFDEBDD),
                ),
              ),
              Positioned(
                right: 2,
                bottom: 2,
                child: Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: _accent,
                    border: Border.all(color: Colors.white, width: 2.5),
                  ),
                  child: _uploading
                      ? const Padding(
                          padding: EdgeInsets.all(9),
                          child: CircularProgressIndicator(
                            strokeWidth: 2.2,
                            valueColor:
                                AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      : const Icon(
                          Icons.photo_camera_rounded,
                          size: 18,
                          color: Colors.white,
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    String? helper,
    TextInputType? keyboardType,
    TextInputAction? textInputAction,
    TextCapitalization textCapitalization = TextCapitalization.none,
    String? Function(String?)? validator,
  }) {
    final border = OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: BorderSide.none,
    );

    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      textInputAction: textInputAction,
      textCapitalization: textCapitalization,
      validator: validator,
      style: const TextStyle(
        fontSize: 15,
        fontWeight: FontWeight.w600,
        color: Color(0xFF241C15),
      ),
      decoration: InputDecoration(
        labelText: label,
        helperText: helper,
        helperStyle: const TextStyle(fontSize: 11.5, color: Color(0xFF9A9086)),
        prefixIcon: Icon(icon, size: 21, color: const Color(0xFF9A9086)),
        filled: true,
        fillColor: Colors.white,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 17),
        labelStyle: const TextStyle(fontSize: 14, color: Color(0xFF9A9086)),
        floatingLabelStyle: const TextStyle(
          fontSize: 13,
          color: _accent,
          fontWeight: FontWeight.w600,
        ),
        border: border,
        enabledBorder: border,
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: _accent, width: 1.6),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: Color(0xFFC62828), width: 1.2),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: Color(0xFFC62828), width: 1.6),
        ),
      ),
    );
  }
}
