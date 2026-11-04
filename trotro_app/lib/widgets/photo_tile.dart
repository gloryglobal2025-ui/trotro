import 'package:flutter/material.dart';

/// Renders an uploaded photo with an icon placeholder when there is no image,
/// while it loads, or if it fails. Photos are decorative here because the tile
/// always carries a text label.
class PhotoTile extends StatelessWidget {
  final String? imageUrl;
  final double? size;
  final double? width;
  final double? height;
  final double borderRadius;
  final bool circle;
  final IconData fallbackIcon;
  final Color fallbackColor;
  final Color? fallbackBackground;

  const PhotoTile({
    super.key,
    required this.imageUrl,
    this.size,
    this.width,
    this.height,
    this.borderRadius = 12,
    this.circle = false,
    required this.fallbackIcon,
    required this.fallbackColor,
    this.fallbackBackground,
  });

  bool get _hasImage => imageUrl != null && imageUrl!.trim().isNotEmpty;

  @override
  Widget build(BuildContext context) {
    final w = width ?? size;
    final h = height ?? size;
    final radius = BorderRadius.circular(circle ? 999 : borderRadius);
    final fallback = _Fallback(
      width: w,
      height: h,
      radius: radius,
      icon: fallbackIcon,
      color: fallbackColor,
      background: fallbackBackground,
    );

    if (!_hasImage) return fallback;

    return ClipRRect(
      borderRadius: radius,
      child: Image.network(
        imageUrl!,
        width: w,
        height: h,
        fit: BoxFit.cover,
        excludeFromSemantics: true,
        cacheWidth: _cacheWidth(context, w),
        loadingBuilder: (context, child, progress) =>
            progress == null ? child : fallback,
        errorBuilder: (context, error, stackTrace) => fallback,
      ),
    );
  }

  int? _cacheWidth(BuildContext context, double? size) {
    if (size == null || size > 160) return null;
    final ratio = MediaQuery.devicePixelRatioOf(context);
    return (size * ratio).round();
  }
}

class _Fallback extends StatelessWidget {
  final double? width;
  final double? height;
  final BorderRadius radius;
  final IconData icon;
  final Color color;
  final Color? background;

  const _Fallback({
    required this.width,
    required this.height,
    required this.radius,
    required this.icon,
    required this.color,
    this.background,
  });

  @override
  Widget build(BuildContext context) {
    final basis = width ?? height ?? 42;
    final iconSize = (basis * 0.5).clamp(16.0, 34.0);
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: background ?? color.withValues(alpha: 0.12),
        borderRadius: radius,
      ),
      child: Icon(icon, color: color, size: iconSize),
    );
  }
}
