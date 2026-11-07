import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../app_scope.dart';
import '../l10n/app_strings.dart';
import '../models/user.dart';
import '../services/api_service.dart';
import '../widgets/photo_tile.dart';

class DriverVerificationScreen extends StatefulWidget {
  const DriverVerificationScreen({super.key});

  @override
  State<DriverVerificationScreen> createState() =>
      _DriverVerificationScreenState();
}

class _DriverVerificationScreenState extends State<DriverVerificationScreen> {
  static const Color _accent = Color(0xFFE65100);
  static const int _maxPhotoBytes = 3900 * 1024;

  final _formKey = GlobalKey<FormState>();
  final _licenseController = TextEditingController();
  final _ghanaCardController = TextEditingController();

  User? _user;
  DateTime? _expiry;
  Uint8List? _licenseBytes;
  String _licenseName = 'license.jpg';
  Uint8List? _ghanaCardBytes;
  String _ghanaCardName = 'ghana-card.jpg';
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _user = ApiService.currentUser;
    _licenseController.text = _user?.licenseNumber ?? '';
    _ghanaCardController.text = _user?.ghanaCardNumber ?? '';
    _expiry = _parseDate(_user?.licenseExpiry);
  }

  @override
  void dispose() {
    _licenseController.dispose();
    _ghanaCardController.dispose();
    super.dispose();
  }

  static DateTime? _parseDate(String? value) {
    if (value == null || value.isEmpty) return null;
    return DateTime.tryParse(value);
  }

  String get _status => _user?.verificationStatus ?? 'unverified';

  bool get _canSubmit => _status != 'pending';

  Future<void> _pickExpiry() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _expiry ?? now.add(const Duration(days: 365)),
      firstDate: now.add(const Duration(days: 1)),
      lastDate: DateTime(now.year + 20),
      helpText: AppScope.of(context).strings.licenseExpiry,
    );
    if (picked != null) setState(() => _expiry = picked);
  }

  Future<void> _pickPhoto({
    required bool license,
    required AppStrings s,
  }) async {
    if (_saving) return;

    final XFile? file;
    try {
      file = await ImagePicker().pickImage(
        source: ImageSource.gallery,
        maxWidth: 1600,
        maxHeight: 1600,
        imageQuality: 85,
      );
    } catch (_) {
      _showMessage(s.photoPickFailedRetry);
      return;
    }
    if (file == null) return;

    final bytes = await file.readAsBytes();
    if (bytes.length > _maxPhotoBytes) {
      _showMessage(s.documentTooLarge);
      return;
    }

    final fallbackName = license ? 'license.jpg' : 'ghana-card.jpg';
    final name = file.name.isEmpty ? fallbackName : file.name;

    setState(() {
      if (license) {
        _licenseBytes = bytes;
        _licenseName = name;
      } else {
        _ghanaCardBytes = bytes;
        _ghanaCardName = name;
      }
    });
  }

  Future<void> _submit(AppStrings s) async {
    FocusScope.of(context).unfocus();
    if (_saving || !_formKey.currentState!.validate()) return;

    if (_expiry == null || !_expiry!.isAfter(DateTime.now())) {
      _showMessage(s.licenseExpiredError);
      return;
    }

    final hasLicensePhoto =
        _licenseBytes != null || (_user?.licenseImage ?? '').isNotEmpty;
    final hasGhanaCardPhoto =
        _ghanaCardBytes != null || (_user?.ghanaCardImage ?? '').isNotEmpty;
    if (!hasLicensePhoto || !hasGhanaCardPhoto) {
      _showMessage(s.photoNeeded);
      return;
    }

    setState(() => _saving = true);
    try {
      final user = await ApiService.submitDriverVerification(
        licenseNumber: _licenseController.text.trim(),
        licenseExpiry: _apiDate(_expiry!),
        ghanaCardNumber: _ghanaCardController.text.trim(),
        licenseImage: _licenseBytes,
        licenseFilename: _licenseName,
        ghanaCardImage: _ghanaCardBytes,
        ghanaCardFilename: _ghanaCardName,
      );
      if (!mounted) return;
      setState(() {
        _user = user;
        _licenseBytes = null;
        _ghanaCardBytes = null;
      });
      _showMessage(s.verificationSubmitted);
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      _showMessage(_cleanError(error, s));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  static String _apiDate(DateTime date) {
    final month = date.month.toString().padLeft(2, '0');
    final day = date.day.toString().padLeft(2, '0');
    return '${date.year}-$month-$day';
  }

  String _formatDate(DateTime date) {
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];
    return '${date.day} ${months[date.month - 1]} ${date.year}';
  }

  String _cleanError(Object error, AppStrings s) {
    final message = error.toString().replaceFirst('Exception: ', '');
    return message.isEmpty ? s.verificationSubmitFailed : message;
  }

  void _showMessage(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context).strings;
    final status = _status;

    return Scaffold(
      backgroundColor: const Color(0xFFF7F4F0),
      appBar: AppBar(
        backgroundColor: const Color(0xFFF7F4F0),
        elevation: 0,
        foregroundColor: const Color(0xFF241C15),
        title: Text(
          s.driverVerification,
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
              _buildStatusCard(s, status),
              const SizedBox(height: 16),
              Text(
                s.verificationIntro,
                style: const TextStyle(
                  color: Color(0xFF6F6459),
                  fontSize: 13.5,
                  height: 1.4,
                ),
              ),
              const SizedBox(height: 18),
              _sectionCard(
                title: s.licenseDetails,
                icon: Icons.badge_outlined,
                children: [
                  _buildField(
                    s: s,
                    controller: _licenseController,
                    label: s.licenseNumber,
                    hint: s.enterLicenseNumber,
                    icon: Icons.numbers_rounded,
                    enabled: _canSubmit,
                  ),
                  const SizedBox(height: 12),
                  _buildExpiryTile(s),
                  const SizedBox(height: 14),
                  _buildPhotoField(
                    s: s,
                    label: s.licensePhoto,
                    existingUrl: _user?.licenseImage,
                    picked: _licenseBytes,
                    icon: Icons.badge_outlined,
                    enabled: _canSubmit,
                    onTap: () => _pickPhoto(license: true, s: s),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _sectionCard(
                title: s.ghanaCardDetails,
                icon: Icons.credit_card_rounded,
                children: [
                  _buildField(
                    s: s,
                    controller: _ghanaCardController,
                    label: s.ghanaCardNumber,
                    hint: s.enterGhanaCardNumber,
                    icon: Icons.credit_card_rounded,
                    enabled: _canSubmit,
                  ),
                  const SizedBox(height: 14),
                  _buildPhotoField(
                    s: s,
                    label: s.ghanaCardPhoto,
                    existingUrl: _user?.ghanaCardImage,
                    picked: _ghanaCardBytes,
                    icon: Icons.credit_card_rounded,
                    enabled: _canSubmit,
                    onTap: () => _pickPhoto(license: false, s: s),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              SizedBox(
                height: 54,
                child: FilledButton.icon(
                  onPressed: _saving || !_canSubmit ? null : () => _submit(s),
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
                      : const Icon(Icons.send_rounded),
                  label: Text(
                    _saving ? s.submittingReview : s.submitForReview,
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

  Widget _buildStatusCard(AppStrings s, String status) {
    late final Color color;
    late final IconData icon;
    late final String title;
    String? note;

    switch (status) {
      case 'approved':
        color = const Color(0xFF2E7D32);
        icon = Icons.verified_rounded;
        title = s.verificationApproved;
        note = s.verificationApprovedNote;
        break;
      case 'pending':
        color = const Color(0xFFEF6C00);
        icon = Icons.hourglass_top_rounded;
        title = s.verificationPending;
        note = s.verificationPendingNote;
        break;
      case 'rejected':
        color = const Color(0xFFC62828);
        icon = Icons.error_outline_rounded;
        title = s.verificationRejected;
        note = _user?.verificationNotes;
        break;
      default:
        color = const Color(0xFF6F6459);
        icon = Icons.shield_outlined;
        title = s.verificationUnverified;
    }

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.09),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: color.withValues(alpha: 0.22)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color, size: 26),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    color: color,
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                if (note != null && note.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    status == 'rejected'
                        ? '${s.adminNote}: $note'
                        : note,
                    style: TextStyle(
                      color: color,
                      fontSize: 13,
                      height: 1.35,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionCard({
    required String title,
    required IconData icon,
    required List<Widget> children,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 20, color: _accent),
              const SizedBox(width: 8),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF241C15),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ...children,
        ],
      ),
    );
  }

  Widget _buildExpiryTile(AppStrings s) {
    final label = _expiry == null ? s.enterLicenseExpiry : _formatDate(_expiry!);
    final expired = _expiry != null && !_expiry!.isAfter(DateTime.now());

    return InkWell(
      onTap: _canSubmit ? _pickExpiry : null,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 17),
        decoration: BoxDecoration(
          color: const Color(0xFFF7F4F0),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(
          children: [
            const Icon(
              Icons.event_rounded,
              size: 21,
              color: Color(0xFF9A9086),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    s.licenseExpiry,
                    style: const TextStyle(
                      fontSize: 11.5,
                      color: Color(0xFF9A9086),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 14.5,
                      fontWeight: FontWeight.w600,
                      color: expired
                          ? const Color(0xFFC62828)
                          : const Color(0xFF241C15),
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right_rounded, color: Color(0xFF9A9086)),
          ],
        ),
      ),
    );
  }

  Widget _buildPhotoField({
    required AppStrings s,
    required String label,
    required String? existingUrl,
    required Uint8List? picked,
    required IconData icon,
    required bool enabled,
    required VoidCallback onTap,
  }) {
    final hasPhoto = picked != null || (existingUrl ?? '').isNotEmpty;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: Color(0xFF241C15),
          ),
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            if (picked != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.memory(
                  picked,
                  width: 84,
                  height: 64,
                  fit: BoxFit.cover,
                ),
              )
            else
              PhotoTile(
                imageUrl: existingUrl,
                width: 84,
                height: 64,
                borderRadius: 12,
                fallbackIcon: icon,
                fallbackColor: _accent,
              ),
            const SizedBox(width: 12),
            Expanded(
              child: OutlinedButton.icon(
                onPressed: enabled ? onTap : null,
                icon: Icon(
                  hasPhoto ? Icons.edit_rounded : Icons.add_a_photo_rounded,
                  size: 18,
                ),
                label: Text(hasPhoto ? s.changePhotoShort : s.addPhoto),
                style: OutlinedButton.styleFrom(
                  foregroundColor: _accent,
                  side: const BorderSide(color: Color(0xFFE0D6CC)),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildField({
    required AppStrings s,
    required TextEditingController controller,
    required String label,
    required String hint,
    required IconData icon,
    required bool enabled,
  }) {
    return TextFormField(
      controller: controller,
      enabled: enabled,
      textCapitalization: TextCapitalization.characters,
      style: const TextStyle(
        fontSize: 15,
        fontWeight: FontWeight.w600,
        color: Color(0xFF241C15),
      ),
      validator: (value) {
        final text = value?.trim() ?? '';
        if (text.isEmpty) {
          return s.requiredField;
        }
        return null;
      },
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        hintStyle: const TextStyle(fontSize: 12.5),
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
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: Color(0xFFE0D6CC)),
        ),
        disabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: Color(0xFFE0D6CC)),
        ),
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
