<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $appName = \App\Helpers\SettingHelper::getAppName();
        $pageTitle = $content['meta_title'] ?: ($appName . ' — ' . $content['hero_title']);
        $pageDescription = $content['meta_description'] ?: \Illuminate\Support\Str::limit($content['hero_subtitle'], 155);
        $canonical = url('/');
        $shareImage = $content['share_image_url'] ?: ($content['hero_image_url'] ?: asset('favicon.ico'));
        $cssFile = public_path('css/website.css');
        $cssVersion = file_exists($cssFile) ? filemtime($cssFile) : '1';
        $appSchemaId = url('/web') . '#app';
        $orgSchemaId = $canonical . '#organization';
        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $orgSchemaId,
                    'name' => $appName,
                    'url' => $canonical,
                    'logo' => asset('favicon.ico'),
                    'description' => $pageDescription,
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $canonical . '#website',
                    'url' => $canonical,
                    'name' => $appName,
                    'inLanguage' => 'en-GH',
                    'publisher' => ['@id' => $orgSchemaId],
                ],
                [
                    '@type' => 'MobileApplication',
                    '@id' => $appSchemaId,
                    'name' => $appName,
                    'url' => url('/web'),
                    'applicationCategory' => 'TravelApplication',
                    'operatingSystem' => 'Android, Web',
                    'inLanguage' => 'en-GH',
                    'description' => $pageDescription,
                    'publisher' => ['@id' => $orgSchemaId],
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '0',
                        'priceCurrency' => 'GHS',
                    ],
                ],
            ],
        ];
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <meta name="theme-color" content="#EA580C">
    <meta name="geo.region" content="GH-AA">
    <meta name="geo.placename" content="Accra, Ghana">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:locale" content="en_GH">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $shareImage }}">
    <meta property="og:image:alt" content="{{ $appName }} — {{ $content['hero_title'] }}">
    <meta name="twitter:card" content="{{ $content['share_image_url'] || $content['hero_image_url'] ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $shareImage }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/website.css') }}?v={{ $cssVersion }}">
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</head>
<body class="bg-[#FBF8F4] font-sans text-stone-900 antialiased">

@php
    $appUrl = url('/web');
    $driverUrl = url('/web') . '?role=driver';
    $phoneDigits = $content['contact_phone'] ? preg_replace('/[^0-9+]/', '', $content['contact_phone']) : '';
    $downloadUrl = $content['play_store_url'] ?: ($content['apk_url'] ?: $appUrl);
@endphp

<header class="sticky top-0 z-40 border-b border-stone-200/70 bg-[#FBF8F4]/85 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 text-white shadow-lg shadow-orange-900/20">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                </svg>
            </span>
            <span class="text-lg font-extrabold tracking-tight text-stone-900">{{ \App\Helpers\SettingHelper::getAppName() }}</span>
        </a>

        <nav class="hidden items-center gap-7 text-sm font-semibold text-stone-600 md:flex">
            <a href="#how-it-works" class="transition hover:text-orange-600">How it works</a>
            @if($content['show_routes'] && $routes->isNotEmpty())
                <a href="#routes" class="transition hover:text-orange-600">Routes</a>
            @endif
            <a href="#drivers" class="transition hover:text-orange-600">Drivers</a>
            <a href="#contact" class="transition hover:text-orange-600">Contact</a>
        </nav>

        <a href="{{ $appUrl }}"
           class="rounded-xl bg-stone-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-stone-800">
            {{ $content['hero_cta'] }}
        </a>
    </div>
</header>

<main id="top">
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute -right-40 -top-40 h-[420px] w-[420px] rounded-full bg-orange-200/50 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-32 top-40 h-72 w-72 rounded-full bg-amber-100/70 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:py-24">
            <div>
                @if($content['hero_badge'])
                    <p class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white/70 px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-orange-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>
                        {{ $content['hero_badge'] }}
                    </p>
                @endif

                <h1 class="mt-5 text-4xl font-black leading-[1.05] tracking-tight text-stone-900 sm:text-5xl lg:text-6xl">
                    {{ $content['hero_title'] }}
                </h1>

                <p class="mt-5 max-w-xl text-base leading-relaxed text-stone-600 sm:text-lg">
                    {{ $content['hero_subtitle'] }}
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ $downloadUrl }}" @if($content['play_store_url'] || $content['apk_url']) target="_blank" rel="noopener" @endif
                       class="rounded-2xl bg-orange-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-orange-900/20 transition hover:bg-orange-700">
                        {{ $content['hero_cta'] }}
                    </a>
                    <a href="{{ $driverUrl }}"
                       class="rounded-2xl border border-stone-300 bg-white px-6 py-3.5 text-sm font-bold text-stone-700 shadow-sm transition hover:border-stone-400 hover:bg-stone-50">
                        {{ $content['hero_secondary_cta'] }}
                    </a>
                    @if($content['apk_url'])
                        <a href="{{ $content['apk_url'] }}" class="text-sm font-semibold text-stone-500 underline decoration-stone-300 underline-offset-4 transition hover:text-orange-600">
                            Download APK
                        </a>
                    @endif
                </div>

                <dl class="mt-10 grid max-w-md grid-cols-3 gap-4 border-t border-stone-200 pt-6 text-sm">
                    <div>
                        <dt class="font-bold text-stone-900">{{ $content['hero_point_1_title'] }}</dt>
                        <dd class="mt-0.5 text-stone-500">{{ $content['hero_point_1_body'] }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-stone-900">{{ $content['hero_point_2_title'] }}</dt>
                        <dd class="mt-0.5 text-stone-500">{{ $content['hero_point_2_body'] }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-stone-900">{{ $content['hero_point_3_title'] }}</dt>
                        <dd class="mt-0.5 text-stone-500">{{ $content['hero_point_3_body'] }}</dd>
                    </div>
                </dl>
            </div>

            <div class="relative">
                @if($content['hero_image_url'])
                    <div class="overflow-hidden rounded-[2rem] border border-white/60 shadow-2xl shadow-stone-900/20">
                        <img src="{{ $content['hero_image_url'] }}" alt="{{ \App\Helpers\SettingHelper::getAppName() }} on the road" class="aspect-[4/5] w-full object-cover" fetchpriority="high" decoding="async">
                    </div>
                @else
                    <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-stone-900 via-stone-900 to-orange-950 p-8 shadow-2xl shadow-stone-900/30 sm:p-10">
                        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-orange-500/30 blur-2xl"></div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-300">Today&rsquo;s journey</p>
                        <div class="mt-6 space-y-3">
                            <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-500 text-white">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-xs text-stone-300">From</p>
                                    <p class="text-base font-bold text-white">Circle</p>
                                </div>
                            </div>
                            <div class="ml-5 h-6 w-px bg-orange-400/60"></div>
                            <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-stone-900">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-xs text-stone-300">To</p>
                                    <p class="text-base font-bold text-white">Kejetia</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex items-end justify-between rounded-2xl bg-orange-500 p-5 text-white">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-orange-100">Fare</p>
                                <p class="mt-1 text-3xl font-black">GH&cent;&nbsp;4.50</p>
                            </div>
                            <p class="text-xs font-semibold text-orange-100">Paid with wallet</p>
                        </div>
                    </div>
                @endif

                <div class="absolute -bottom-6 -left-6 hidden rounded-2xl border border-stone-200 bg-white px-5 py-4 shadow-xl shadow-stone-900/10 sm:block">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400">Driver</p>
                    <p class="mt-1 flex items-center gap-2 text-sm font-bold text-stone-900">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        Verified before you board
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Sponsored website adverts --}}
    @if($adverts->isNotEmpty())
        <section class="border-y border-stone-200/70 bg-white/60 py-10">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400">Sponsored</p>
                <div class="mt-4 grid gap-5 sm:grid-cols-2 {{ $adverts->count() > 2 ? 'lg:grid-cols-3' : '' }}">
                    @foreach($adverts as $advert)
                        @php $tag = $advert->link_url ? 'a' : 'div'; @endphp
                        <{{ $tag }} @if($advert->link_url) href="{{ $advert->link_url }}" target="_blank" rel="noopener sponsored" @endif
                           class="group relative block overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm transition hover:shadow-lg">
                            @if($advert->image_url)
                                <img src="{{ $advert->image_url }}" alt="{{ $advert->title }}" loading="lazy" decoding="async" class="aspect-[12/5] w-full object-cover transition duration-500 group-hover:scale-[1.02]">
                            @else
                                <div class="flex aspect-[12/5] w-full items-center justify-center bg-gradient-to-br from-orange-500 to-amber-600 px-4 text-center">
                                    <p class="text-sm font-bold text-white">{{ $advert->title }}</p>
                                </div>
                            @endif
                            @if($advert->subtitle)
                                <p class="px-4 py-3 text-sm font-semibold text-stone-700">{{ $advert->subtitle }}</p>
                            @endif
                        </{{ $tag }}>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- How it works --}}
    <section id="how-it-works" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24">
        <div class="max-w-2xl">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-600">{{ $content['about_eyebrow'] }}</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-stone-900 sm:text-4xl">{{ $content['about_title'] }}</h2>
            <p class="mt-4 text-base leading-relaxed text-stone-600">{{ $content['about_body'] }}</p>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-100 text-orange-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                </span>
                <h3 class="mt-4 text-base font-bold text-stone-900">{{ $content['step_1_title'] }}</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-stone-500">{{ $content['step_1_body'] }}</p>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10" />
                    </svg>
                </span>
                <h3 class="mt-4 text-base font-bold text-stone-900">{{ $content['step_2_title'] }}</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-stone-500">{{ $content['step_2_body'] }}</p>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                </span>
                <h3 class="mt-4 text-base font-bold text-stone-900">{{ $content['step_3_title'] }}</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-stone-500">{{ $content['step_3_body'] }}</p>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-stone-200 text-stone-800">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                </span>
                <h3 class="mt-4 text-base font-bold text-stone-900">{{ $content['step_4_title'] }}</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-stone-500">{{ $content['step_4_body'] }}</p>
            </div>
        </div>
    </section>

    {{-- Routes --}}
    @if($content['show_routes'] && $routes->isNotEmpty())
        <section id="routes" class="border-y border-stone-200/70 bg-white/60 py-16 lg:py-24">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-600">{{ $content['routes_eyebrow'] }}</p>
                        <h2 class="mt-3 text-3xl font-black tracking-tight text-stone-900">{{ $content['routes_title'] }}</h2>
                    </div>
                    <a href="{{ $appUrl }}" class="text-sm font-semibold text-stone-600 underline decoration-stone-300 underline-offset-4 transition hover:text-orange-600">
                        {{ $content['routes_link_label'] }}
                    </a>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($routes as $route)
                        <div class="rounded-2xl border border-stone-200 bg-[#FBF8F4] p-5">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-stone-900 text-white">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-stone-900">
                                        {{ $route->origin?->name ?? 'Origin' }}
                                        <span class="mx-1 text-orange-500">&rarr;</span>
                                        {{ $route->destination?->name ?? 'Destination' }}
                                    </p>
                                    @if($route->route_name)
                                        <p class="truncate text-xs text-stone-500">{{ $route->route_name }}</p>
                                    @endif
                                </div>
                            </div>
                            @if($route->estimated_duration)
                                <p class="mt-4 flex items-center gap-2 text-xs font-semibold text-stone-500">
                                    <svg class="h-4 w-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    About {{ $route->estimated_duration }} minutes
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Stops gallery --}}
    @if($stops->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-600">{{ $content['stops_eyebrow'] }}</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-stone-900">{{ $content['stops_title'] }}</h2>
            <p class="mt-3 max-w-2xl text-base leading-relaxed text-stone-600">{{ $content['stops_body'] }}</p>

            <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3">
                @foreach($stops as $stop)
                    <figure class="group overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                        <img src="{{ $stop->image_url }}" alt="{{ $stop->name }}" loading="lazy" decoding="async" class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                        <figcaption class="px-4 py-3">
                            <p class="text-sm font-bold text-stone-900">{{ $stop->name }}</p>
                            @if($stop->area)
                                <p class="text-xs text-stone-500">{{ $stop->area }}</p>
                            @endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Driver band --}}
    <section id="drivers" class="relative overflow-hidden bg-stone-900">
        <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-orange-600/30 blur-3xl"></div>
        <div class="relative mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:py-24">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-400">{{ $content['drivers_eyebrow'] }}</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ $content['drivers_title'] }}</h2>
                <p class="mt-4 max-w-xl text-base leading-relaxed text-stone-300">
                    {{ $content['drivers_body'] }}
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ $driverUrl }}" class="rounded-2xl bg-orange-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-orange-950/40 transition hover:bg-orange-700">
                        {{ $content['hero_secondary_cta'] }}
                    </a>
                    <a href="#contact" class="rounded-2xl border border-white/20 px-6 py-3.5 text-sm font-bold text-white transition hover:bg-white/10">
                        {{ $content['drivers_secondary_cta'] }}
                    </a>
                </div>
            </div>

            <ul class="space-y-3">
                <li class="flex items-start gap-3 rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-white">{{ $content['driver_point_1_title'] }}</p>
                        <p class="mt-1 text-sm text-stone-400">{{ $content['driver_point_1_body'] }}</p>
                    </div>
                </li>
                <li class="flex items-start gap-3 rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-white">{{ $content['driver_point_2_title'] }}</p>
                        <p class="mt-1 text-sm text-stone-400">{{ $content['driver_point_2_body'] }}</p>
                    </div>
                </li>
                <li class="flex items-start gap-3 rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-white">{{ $content['driver_point_3_title'] }}</p>
                        <p class="mt-1 text-sm text-stone-400">{{ $content['driver_point_3_body'] }}</p>
                    </div>
                </li>
            </ul>
        </div>
    </section>

    {{-- Contact --}}
    <section id="contact" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-24">
        <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-600">{{ $content['contact_eyebrow'] }}</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-stone-900">{{ $content['contact_title'] }}</h2>
                <p class="mt-4 max-w-xl text-base leading-relaxed text-stone-600">
                    {{ $content['contact_body'] }}
                </p>

                <div class="mt-8 space-y-3">
                    @if($phoneDigits)
                        <a href="tel:{{ $phoneDigits }}" class="flex items-center gap-4 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm transition hover:border-orange-300">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-100 text-orange-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-stone-400">Call us</p>
                                <p class="text-base font-bold text-stone-900">{{ $content['contact_phone'] }}</p>
                            </div>
                        </a>
                    @endif

                    @if($content['contact_email'])
                        <a href="mailto:{{ $content['contact_email'] }}" class="flex items-center gap-4 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm transition hover:border-orange-300">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-stone-400">Email</p>
                                <p class="break-all text-base font-bold text-stone-900">{{ $content['contact_email'] }}</p>
                            </div>
                        </a>
                    @endif

                    @if($content['contact_address'])
                        <div class="flex items-center gap-4 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-stone-200 text-stone-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-stone-400">Find us</p>
                                <p class="text-base font-bold text-stone-900">{{ $content['contact_address'] }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-[2rem] bg-gradient-to-br from-orange-500 to-amber-600 p-8 text-white shadow-2xl shadow-orange-900/20 sm:p-10">
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-orange-100">{{ $content['cta_eyebrow'] }}</p>
                <p class="mt-3 text-2xl font-black leading-tight sm:text-3xl">{{ $content['cta_title'] }}</p>
                <p class="mt-3 text-sm leading-relaxed text-orange-50">{{ $content['cta_body'] }}</p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $appUrl }}" class="rounded-2xl bg-white px-6 py-3.5 text-center text-sm font-bold text-orange-700 shadow-sm transition hover:bg-orange-50">
                        {{ $content['hero_cta'] }}
                    </a>
                    @if($content['play_store_url'])
                        <a href="{{ $content['play_store_url'] }}" target="_blank" rel="noopener" class="rounded-2xl border border-white/40 px-6 py-3.5 text-center text-sm font-bold text-white transition hover:bg-white/10">
                            Get it on Google Play
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="border-t border-stone-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-10 sm:px-6 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                </svg>
            </span>
            <div>
                <p class="text-sm font-bold text-stone-900">{{ \App\Helpers\SettingHelper::getAppName() }}</p>
                <p class="text-xs text-stone-500">{{ $content['footer_text'] }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-semibold text-stone-500">
            <a href="#how-it-works" class="transition hover:text-orange-600">How it works</a>
            <a href="#drivers" class="transition hover:text-orange-600">Drivers</a>
            <a href="#contact" class="transition hover:text-orange-600">Contact</a>
            <a href="{{ $appUrl }}" class="transition hover:text-orange-600">Open app</a>
        </div>

        <p class="text-xs text-stone-400">&copy; {{ now()->year }} {{ \App\Helpers\SettingHelper::getAppName() }}. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
