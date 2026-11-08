class Advert {
  final int id;
  final String title;
  final String? subtitle;
  final String? imageUrl;
  final String? linkUrl;
  final String placement;

  Advert({
    required this.id,
    required this.title,
    this.subtitle,
    this.imageUrl,
    this.linkUrl,
    required this.placement,
  });

  factory Advert.fromJson(Map<String, dynamic> json) {
    return Advert(
      id: json['id'],
      title: (json['title'] ?? '').toString(),
      subtitle: json['subtitle']?.toString(),
      imageUrl: json['image_url']?.toString(),
      linkUrl: json['link_url']?.toString(),
      placement: (json['placement'] ?? 'home').toString(),
    );
  }
}
