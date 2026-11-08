import 'dart:async';

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../l10n/app_strings.dart';
import '../models/advert.dart';
import '../services/api_service.dart';
import '../services/app_settings.dart';

class AdvertBanner extends StatefulWidget {
  const AdvertBanner({
    super.key,
    this.placement = 'home',
    this.height = 160,
    this.margin = const EdgeInsets.only(bottom: 16),
  });

  final String placement;
  final double height;
  final EdgeInsetsGeometry margin;

  @override
  State<AdvertBanner> createState() => _AdvertBannerState();
}

class _AdvertBannerState extends State<AdvertBanner> {
  final PageController _controller = PageController(viewportFraction: 0.92);
  List<Advert> _adverts = const [];
  Timer? _timer;
  int _page = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final adverts = await ApiService.getAdverts(placement: widget.placement);
      if (!mounted) return;
      setState(() => _adverts = adverts);
      _startRotation();
    } catch (_) {
      // Adverts are decorative; never block the screen they sit on.
    }
  }

  void _startRotation() {
    _timer?.cancel();
    if (_adverts.length < 2) return;
    _timer = Timer.periodic(const Duration(seconds: 6), (_) {
      if (!mounted || !_controller.hasClients) return;
      final next = (_page + 1) % _adverts.length;
      _controller.animateToPage(
        next,
        duration: const Duration(milliseconds: 500),
        curve: Curves.easeInOut,
      );
    });
  }

  Future<void> _open(String link, AppStrings s) async {
    final uri = Uri.tryParse(link.trim());
    if (uri == null) return;
    try {
      final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!opened && mounted) _showError(s.advertOpenFailed);
    } catch (_) {
      if (mounted) _showError(s.advertOpenFailed);
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.maybeOf(context)?.showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_adverts.isEmpty) return const SizedBox.shrink();
    final s = AppSettings.instance.strings;

    return Padding(
      padding: widget.margin,
      child: SizedBox(
        height: widget.height,
        child: Column(
          children: [
            Expanded(
              child: PageView.builder(
                controller: _controller,
                onPageChanged: (index) => _page = index,
                itemCount: _adverts.length,
                itemBuilder: (context, index) => Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                  child: _card(_adverts[index], s),
                ),
              ),
            ),
            if (_adverts.length > 1)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List.generate(_adverts.length, (index) {
                    final active = index == _page;
                    return AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      margin: const EdgeInsets.symmetric(horizontal: 3),
                      width: active ? 18 : 6,
                      height: 6,
                      decoration: BoxDecoration(
                        color: active
                            ? const Color(0xFFE65100)
                            : const Color(0xFFD6CEC5),
                        borderRadius: BorderRadius.circular(3),
                      ),
                    );
                  }),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _card(Advert advert, AppStrings s) {
    final link = advert.linkUrl?.trim();
    final hasLink = link != null && link.isNotEmpty;

    return Semantics(
      button: hasLink,
      label: '${s.sponsored}: ${advert.title}',
      child: GestureDetector(
        onTap: hasLink ? () => _open(link, s) : null,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(20),
          child: Stack(
            fit: StackFit.expand,
            children: [
              if (advert.imageUrl != null)
                Image.network(
                  advert.imageUrl!,
                  fit: BoxFit.cover,
                  errorBuilder: (_, _, _) => _fallback(),
                )
              else
                _fallback(),
              const DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Colors.transparent, Color(0xCC1A120B)],
                    stops: [0.45, 1],
                  ),
                ),
              ),
              Positioned(
                top: 10,
                left: 10,
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.55),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    s.sponsored.toUpperCase(),
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 9,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.8,
                    ),
                  ),
                ),
              ),
              if (hasLink)
                Positioned(
                  top: 8,
                  right: 8,
                  child: Container(
                    padding: const EdgeInsets.all(5),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.22),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.arrow_outward_rounded,
                      color: Colors.white,
                      size: 14,
                    ),
                  ),
                ),
              Positioned(
                left: 14,
                right: 14,
                bottom: 12,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      advert.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        height: 1.2,
                      ),
                    ),
                    if (advert.subtitle != null &&
                        advert.subtitle!.trim().isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        advert.subtitle!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.85),
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _fallback() {
    return const DecoratedBox(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFFB33A0B), Color(0xFFFB8C00)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Icon(
          Icons.campaign_rounded,
          color: Colors.white24,
          size: 64,
        ),
      ),
    );
  }
}
