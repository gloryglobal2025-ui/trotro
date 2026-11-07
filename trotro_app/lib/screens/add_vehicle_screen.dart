import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';

import '../app_scope.dart';
import '../l10n/app_strings.dart';
import '../models/vehicle.dart';
import '../services/api_service.dart';
import '../widgets/photo_tile.dart';

class AddVehicleScreen extends StatefulWidget {
  const AddVehicleScreen({super.key, this.vehicle});

  final Vehicle? vehicle;

  @override
  State<AddVehicleScreen> createState() => _AddVehicleScreenState();
}

class _AddVehicleScreenState extends State<AddVehicleScreen> {
  static const Color _accent = Color(0xFFE65100);
  static const int _maxPhotoBytes = 3900 * 1024;

  final _formKey = GlobalKey<FormState>();
  final _makeController = TextEditingController();
  final _modelController = TextEditingController();
  final _yearController = TextEditingController();
  final _plateController = TextEditingController();
  final _colorController = TextEditingController();
  final _capacityController = TextEditingController(text: '14');

  String _type = 'minivan';
  Uint8List? _vehicleBytes;
  String _vehicleName = 'vehicle.jpg';
  Uint8List? _registrationBytes;
  String _registrationName = 'registration.jpg';
  Uint8List? _roadworthyBytes;
  String _roadworthyName = 'roadworthy.jpg';
  bool _saving = false;

  bool get _isEditing => widget.vehicle != null;

  bool get _locked => widget.vehicle?.requestStatus == 'pending';

  @override
  void initState() {
    super.initState();
    final vehicle = widget.vehicle;
    if (vehicle == null) return;
    _makeController.text = vehicle.make;
    _modelController.text = vehicle.model;
    _yearController.text = vehicle.year;
    _plateController.text = vehicle.licensePlate;
    _colorController.text = vehicle.color;
    _capacityController.text = vehicle.capacity.toString();
    _type = vehicle.vehicleType;
  }

  @override
  void dispose() {
    _makeController.dispose();
    _modelController.dispose();
    _yearController.dispose();
    _plateController.dispose();
    _colorController.dispose();
    _capacityController.dispose();
    super.dispose();
  }

  Future<void> _pickPhoto({
    required String kind,
    required AppStrings s,
  }) async {
    if (_saving || _locked) return;

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

    final name = file.name.isEmpty ? '$kind.jpg' : file.name;
    setState(() {
      switch (kind) {
        case 'vehicle':
          _vehicleBytes = bytes;
          _vehicleName = name;
          break;
        case 'registration':
          _registrationBytes = bytes;
          _registrationName = name;
          break;
        default:
          _roadworthyBytes = bytes;
          _roadworthyName = name;
      }
    });
  }

  Future<void> _submit(AppStrings s) async {
    FocusScope.of(context).unfocus();
    if (_saving || !_formKey.currentState!.validate()) return;

    final vehicle = widget.vehicle;
    final hasVehiclePhoto =
        _vehicleBytes != null || (vehicle?.vehicleImage ?? '').isNotEmpty;
    final hasRegistrationPhoto =
        _registrationBytes != null || (vehicle?.registrationImage ?? '').isNotEmpty;
    final hasRoadworthyPhoto =
        _roadworthyBytes != null || (vehicle?.roadworthyImage ?? '').isNotEmpty;
    if (!hasVehiclePhoto || !hasRegistrationPhoto || !hasRoadworthyPhoto) {
      _showMessage(s.photoNeeded);
      return;
    }

    final make = _makeController.text.trim();
    final model = _modelController.text.trim();
    final plate = _plateController.text.trim().toUpperCase();
    final color = _colorController.text.trim();
    final year = int.parse(_yearController.text.trim());
    final capacity = int.parse(_capacityController.text.trim());

    setState(() => _saving = true);
    try {
      if (_isEditing) {
        await ApiService.updateVehicle(
          id: vehicle!.id,
          make: make,
          model: model,
          year: year,
          licensePlate: plate,
          color: color,
          capacity: capacity,
          vehicleType: _type,
          vehicleImage: _vehicleBytes,
          vehicleImageName: _vehicleName,
          registrationImage: _registrationBytes,
          registrationImageName: _registrationName,
          roadworthyImage: _roadworthyBytes,
          roadworthyImageName: _roadworthyName,
        );
      } else {
        await ApiService.createVehicle(
          make: make,
          model: model,
          year: year,
          licensePlate: plate,
          color: color,
          capacity: capacity,
          vehicleType: _type,
          vehicleImage: _vehicleBytes!,
          vehicleImageName: _vehicleName,
          registrationImage: _registrationBytes!,
          registrationImageName: _registrationName,
          roadworthyImage: _roadworthyBytes!,
          roadworthyImageName: _roadworthyName,
        );
      }
      if (!mounted) return;
      _showMessage(s.vehicleSubmitted);
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
    return message.isEmpty ? s.vehicleSubmitFailed : message;
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
    final status = widget.vehicle?.requestStatus;

    return Scaffold(
      backgroundColor: const Color(0xFFF7F4F0),
      appBar: AppBar(
        backgroundColor: const Color(0xFFF7F4F0),
        elevation: 0,
        foregroundColor: const Color(0xFF241C15),
        title: Text(
          _isEditing ? s.editVehicle : s.addVehicle,
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
              if (status != null) ...[
                _buildStatusCard(s, status),
                const SizedBox(height: 16),
              ],
              Text(
                s.vehicleIntro,
                style: const TextStyle(
                  color: Color(0xFF6F6459),
                  fontSize: 13.5,
                  height: 1.4,
                ),
              ),
              const SizedBox(height: 18),
              _sectionCard(
                title: s.myVehicle,
                icon: Icons.directions_bus_rounded,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: _buildField(
                          s: s,
                          controller: _makeController,
                          label: s.vehicleMake,
                          icon: Icons.factory_outlined,
                          enabled: !_locked,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildField(
                          s: s,
                          controller: _modelController,
                          label: s.vehicleModel,
                          icon: Icons.directions_bus_outlined,
                          enabled: !_locked,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _buildField(
                          s: s,
                          controller: _yearController,
                          label: s.vehicleYear,
                          icon: Icons.event_outlined,
                          enabled: !_locked,
                          keyboardType: TextInputType.number,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                          ],
                          validator: (value) {
                            final year = int.tryParse(value?.trim() ?? '');
                            final maxYear = DateTime.now().year;
                            if (year == null || year < 2000 || year > maxYear) {
                              return s.requiredField;
                            }
                            return null;
                          },
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildField(
                          s: s,
                          controller: _capacityController,
                          label: s.vehicleCapacity,
                          icon: Icons.event_seat_outlined,
                          enabled: !_locked,
                          keyboardType: TextInputType.number,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                          ],
                          validator: (value) {
                            final seats = int.tryParse(value?.trim() ?? '');
                            if (seats == null || seats < 1 || seats > 20) {
                              return s.requiredField;
                            }
                            return null;
                          },
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _buildField(
                    s: s,
                    controller: _plateController,
                    label: s.vehiclePlate,
                    icon: Icons.pin_outlined,
                    enabled: !_locked,
                  ),
                  const SizedBox(height: 12),
                  _buildField(
                    s: s,
                    controller: _colorController,
                    label: s.vehicleColor,
                    icon: Icons.palette_outlined,
                    enabled: !_locked,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    s.vehicleType,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF241C15),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final type in const [
                        'sedan',
                        'suv',
                        'minivan',
                        'bus',
                      ])
                        ChoiceChip(
                          label: Text(
                            s.vehicleTypeLabel(type),
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 12.5,
                              color: _type == type
                                  ? Colors.white
                                  : const Color(0xFF6F6459),
                            ),
                          ),
                          selected: _type == type,
                          selectedColor: _accent,
                          backgroundColor: const Color(0xFFF7F4F0),
                          side: BorderSide(
                            color: _type == type
                                ? _accent
                                : const Color(0xFFE0D6CC),
                          ),
                          onSelected: _locked
                              ? null
                              : (_) => setState(() => _type = type),
                        ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _sectionCard(
                title: s.vehicleDocuments,
                icon: Icons.photo_library_outlined,
                children: [
                  _buildPhotoField(
                    s: s,
                    label: s.vehiclePhoto,
                    existingUrl: widget.vehicle?.vehicleImage,
                    picked: _vehicleBytes,
                    icon: Icons.directions_bus_rounded,
                    onTap: () => _pickPhoto(kind: 'vehicle', s: s),
                  ),
                  const SizedBox(height: 14),
                  _buildPhotoField(
                    s: s,
                    label: s.registrationPhoto,
                    existingUrl: widget.vehicle?.registrationImage,
                    picked: _registrationBytes,
                    icon: Icons.description_outlined,
                    onTap: () => _pickPhoto(kind: 'registration', s: s),
                  ),
                  const SizedBox(height: 14),
                  _buildPhotoField(
                    s: s,
                    label: s.roadworthyPhoto,
                    existingUrl: widget.vehicle?.roadworthyImage,
                    picked: _roadworthyBytes,
                    icon: Icons.verified_outlined,
                    onTap: () => _pickPhoto(kind: 'roadworthy', s: s),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              SizedBox(
                height: 54,
                child: FilledButton.icon(
                  onPressed:
                      _saving || _locked ? null : () => _submit(s),
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
        title = s.vehicleStatusApproved;
        note = s.vehicleApprovedNote;
        break;
      case 'pending':
        color = const Color(0xFFEF6C00);
        icon = Icons.hourglass_top_rounded;
        title = s.vehicleStatusPending;
        note = s.vehiclePendingNote;
        break;
      case 'rejected':
        color = const Color(0xFFC62828);
        icon = Icons.error_outline_rounded;
        title = s.vehicleStatusRejected;
        note = widget.vehicle?.rejectionReason;
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

  Widget _buildPhotoField({
    required AppStrings s,
    required String label,
    required String? existingUrl,
    required Uint8List? picked,
    required IconData icon,
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
                onPressed: _saving || _locked ? null : onTap,
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
    required IconData icon,
    required bool enabled,
    TextInputType? keyboardType,
    List<TextInputFormatter>? inputFormatters,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      enabled: enabled,
      keyboardType: keyboardType,
      inputFormatters: inputFormatters,
      textCapitalization: TextCapitalization.words,
      style: const TextStyle(
        fontSize: 15,
        fontWeight: FontWeight.w600,
        color: Color(0xFF241C15),
      ),
      validator: validator ??
          (value) => (value?.trim().isEmpty ?? true) ? s.requiredField : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: Colors.white,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 17),
        prefixIcon: Icon(icon, size: 20, color: const Color(0xFF9A9086)),
        labelStyle: const TextStyle(fontSize: 13.5, color: Color(0xFF9A9086)),
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
