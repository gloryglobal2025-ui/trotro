@extends('admin.layout')

@php
    $input = 'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-100';
    $label = 'mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500';
    $field = fn (string $key) => old($key, $content[$key] ?? '');
@endphp

@section('content')
<div class="p-6 lg:p-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Public site</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Website content</h1>
            <p class="mt-1 text-sm text-slate-500">Everything here appears on the public page at the root address. Changes go live immediately after saving.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ url('/') }}" target="_blank" rel="noopener"
               class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                View site &nearr;
            </a>
            <a href="{{ route('admin.adverts.index') }}"
               class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                Website adverts
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-semibold">Please fix the highlighted fields.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.website.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Hero</h2>
                    <p class="mt-0.5 text-xs text-slate-500">The first thing a visitor sees. Keep the headline short and the promises concrete.</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="{{ $label }}" for="hero_badge">Badge</label>
                            <input id="hero_badge" name="hero_badge" type="text" maxlength="100"
                                   value="{{ $field('hero_badge') }}" class="{{ $input }}"
                                   placeholder="SIMPLE · SAFE · LOCAL">
                            @error('hero_badge')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="{{ $label }}" for="hero_title">Headline</label>
                            <input id="hero_title" name="hero_title" type="text" maxlength="120" required
                                   value="{{ $field('hero_title') }}" class="{{ $input }}"
                                   placeholder="Know your route. Know your fare. Go.">
                            @error('hero_title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="{{ $label }}" for="hero_subtitle">Paragraph</label>
                            <textarea id="hero_subtitle" name="hero_subtitle" rows="3" maxlength="300" required
                                      class="{{ $input }}" placeholder="One or two sentences about the app.">{{ $field('hero_subtitle') }}</textarea>
                            @error('hero_subtitle')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="{{ $label }}" for="hero_cta">Main button</label>
                                <input id="hero_cta" name="hero_cta" type="text" maxlength="40" required
                                       value="{{ $field('hero_cta') }}" class="{{ $input }}" placeholder="Open the app">
                                @error('hero_cta')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $label }}" for="hero_secondary_cta">Second button</label>
                                <input id="hero_secondary_cta" name="hero_secondary_cta" type="text" maxlength="40" required
                                       value="{{ $field('hero_secondary_cta') }}" class="{{ $input }}" placeholder="Drive with Trotro">
                                @error('hero_secondary_cta')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="{{ $label }}" for="hero_image">Hero photo</label>
                            @if($content['hero_image_url'])
                                <div class="mb-2 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-2">
                                    <img src="{{ $content['hero_image_url'] }}" alt="Current hero" class="h-16 w-24 rounded-lg object-cover">
                                    <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                                        <input type="checkbox" name="remove_hero_image" value="1"
                                               class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                        Remove photo
                                    </label>
                                </div>
                            @endif
                            <input id="hero_image" name="hero_image" type="file" accept="image/jpeg,image/png,image/webp"
                                   class="block w-full cursor-pointer rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3.5 py-3 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:border-orange-400">
                            <p class="mt-1 text-[11px] text-slate-500">Landscape photo, up to 4 MB. Without a photo the site uses a branded colour panel.</p>
                            @error('hero_image')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">About section</h2>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="{{ $label }}" for="about_title">Heading</label>
                            <input id="about_title" name="about_title" type="text" maxlength="120" required
                                   value="{{ $field('about_title') }}" class="{{ $input }}">
                            @error('about_title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="{{ $label }}" for="about_body">Paragraph</label>
                            <textarea id="about_body" name="about_body" rows="4" maxlength="2000" required
                                      class="{{ $input }}">{{ $field('about_body') }}</textarea>
                            @error('about_body')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Contact</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Shown in the contact block and footer. Leave a field empty to hide it.</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $label }}" for="contact_phone">Phone</label>
                            <input id="contact_phone" name="contact_phone" type="text" maxlength="50"
                                   value="{{ $field('contact_phone') }}" class="{{ $input }}" placeholder="+233 24 000 0000">
                            @error('contact_phone')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="contact_email">Email</label>
                            <input id="contact_email" name="contact_email" type="email" maxlength="255"
                                   value="{{ $field('contact_email') }}" class="{{ $input }}" placeholder="hello@example.com">
                            @error('contact_email')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="{{ $label }}" for="contact_address">Address</label>
                            <input id="contact_address" name="contact_address" type="text" maxlength="255"
                                   value="{{ $field('contact_address') }}" class="{{ $input }}" placeholder="Accra, Ghana">
                            @error('contact_address')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Download links</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Used by the download buttons. Leave empty to point visitors to the web app instead.</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $label }}" for="play_store_url">Play Store link</label>
                            <input id="play_store_url" name="play_store_url" type="url" maxlength="2048"
                                   value="{{ $field('play_store_url') }}" class="{{ $input }}" placeholder="https://play.google.com/store/apps/details?id=...">
                            @error('play_store_url')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="apk_url">Android APK link</label>
                            <input id="apk_url" name="apk_url" type="url" maxlength="2048"
                                   value="{{ $field('apk_url') }}" class="{{ $input }}" placeholder="https://example.com/trotro.apk">
                            @error('apk_url')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Footer and sections</h2>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="{{ $label }}" for="footer_text">Footer line</label>
                            <input id="footer_text" name="footer_text" type="text" maxlength="255"
                                   value="{{ $field('footer_text') }}" class="{{ $input }}">
                            @error('footer_text')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <input type="checkbox" name="show_routes" value="1"
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                                   @checked(old('show_routes', $content['show_routes']))>
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Show the popular routes section</span>
                                <span class="block text-xs text-slate-500">Pulls the newest routes you have added in the catalogue. Hide it if the list is not ready.</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Save</h2>
                    <button type="submit" class="mt-3 w-full rounded-xl bg-orange-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                        Publish changes
                    </button>
                    <a href="{{ url('/') }}" target="_blank" rel="noopener"
                       class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        Preview site
                    </a>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Sponsored banners</h2>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                        Banners with the <strong>Public website</strong> placement appear inside the site. App banners are managed separately on the Adverts page.
                    </p>
                    <a href="{{ route('admin.adverts.create') }}"
                       class="mt-3 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        Add a website advert
                    </a>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-relaxed text-amber-800">
                    <p class="font-semibold">Before you publish</p>
                    <p class="mt-1">Check the phone number and email are ones your team actually answers. Visitors use them to report lost items and ask about fares.</p>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
