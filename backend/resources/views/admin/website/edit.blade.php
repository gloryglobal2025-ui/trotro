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
                    <h2 class="text-sm font-bold text-slate-900">Hero trust points</h2>
                    <p class="mt-0.5 text-xs text-slate-500">The three short promises under the hero buttons.</p>

                    <div class="mt-4 space-y-4">
                        @foreach([1, 2, 3] as $n)
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="{{ $label }}" for="hero_point_{{ $n }}_title">Point {{ $n }} title</label>
                                    <input id="hero_point_{{ $n }}_title" name="hero_point_{{ $n }}_title" type="text" maxlength="60"
                                           value="{{ $field('hero_point_'.$n.'_title') }}" class="{{ $input }}">
                                    @error('hero_point_'.$n.'_title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $label }}" for="hero_point_{{ $n }}_body">Point {{ $n }} text</label>
                                    <input id="hero_point_{{ $n }}_body" name="hero_point_{{ $n }}_body" type="text" maxlength="120"
                                           value="{{ $field('hero_point_'.$n.'_body') }}" class="{{ $input }}">
                                    @error('hero_point_'.$n.'_body')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">About section</h2>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="{{ $label }}" for="about_eyebrow">Eyebrow label</label>
                            <input id="about_eyebrow" name="about_eyebrow" type="text" maxlength="60"
                                   value="{{ $field('about_eyebrow') }}" class="{{ $input }}" placeholder="How it works">
                            @error('about_eyebrow')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

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

                        <div class="border-t border-slate-100 pt-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Four steps</p>
                            <div class="mt-3 space-y-3">
                                @foreach([1, 2, 3, 4] as $n)
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="{{ $label }}" for="step_{{ $n }}_title">Step {{ $n }} title</label>
                                            <input id="step_{{ $n }}_title" name="step_{{ $n }}_title" type="text" maxlength="80"
                                                   value="{{ $field('step_'.$n.'_title') }}" class="{{ $input }}">
                                            @error('step_'.$n.'_title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label class="{{ $label }}" for="step_{{ $n }}_body">Step {{ $n }} text</label>
                                            <input id="step_{{ $n }}_body" name="step_{{ $n }}_body" type="text" maxlength="300"
                                                   value="{{ $field('step_'.$n.'_body') }}" class="{{ $input }}">
                                            @error('step_'.$n.'_body')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Popular routes and stops</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Route cards and stop photos come from your catalogue. This is the copy around them.</p>

                    <div class="mt-4 space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="{{ $label }}" for="routes_eyebrow">Routes eyebrow</label>
                                <input id="routes_eyebrow" name="routes_eyebrow" type="text" maxlength="60"
                                       value="{{ $field('routes_eyebrow') }}" class="{{ $input }}">
                                @error('routes_eyebrow')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $label }}" for="routes_link_label">Routes link label</label>
                                <input id="routes_link_label" name="routes_link_label" type="text" maxlength="60"
                                       value="{{ $field('routes_link_label') }}" class="{{ $input }}">
                                @error('routes_link_label')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="routes_title">Routes heading</label>
                            <input id="routes_title" name="routes_title" type="text" maxlength="120"
                                   value="{{ $field('routes_title') }}" class="{{ $input }}">
                            @error('routes_title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
                            <div>
                                <label class="{{ $label }}" for="stops_eyebrow">Stops eyebrow</label>
                                <input id="stops_eyebrow" name="stops_eyebrow" type="text" maxlength="60"
                                       value="{{ $field('stops_eyebrow') }}" class="{{ $input }}">
                                @error('stops_eyebrow')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $label }}" for="stops_title">Stops heading</label>
                                <input id="stops_title" name="stops_title" type="text" maxlength="120"
                                       value="{{ $field('stops_title') }}" class="{{ $input }}">
                                @error('stops_title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="{{ $label }}" for="stops_body">Stops paragraph</label>
                                <textarea id="stops_body" name="stops_body" rows="2" maxlength="400"
                                          class="{{ $input }}">{{ $field('stops_body') }}</textarea>
                                @error('stops_body')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Drivers section</h2>

                    <div class="mt-4 space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="{{ $label }}" for="drivers_eyebrow">Eyebrow label</label>
                                <input id="drivers_eyebrow" name="drivers_eyebrow" type="text" maxlength="60"
                                       value="{{ $field('drivers_eyebrow') }}" class="{{ $input }}">
                                @error('drivers_eyebrow')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $label }}" for="drivers_secondary_cta">Second button</label>
                                <input id="drivers_secondary_cta" name="drivers_secondary_cta" type="text" maxlength="40"
                                       value="{{ $field('drivers_secondary_cta') }}" class="{{ $input }}">
                                @error('drivers_secondary_cta')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="drivers_title">Heading</label>
                            <input id="drivers_title" name="drivers_title" type="text" maxlength="120"
                                   value="{{ $field('drivers_title') }}" class="{{ $input }}">
                            @error('drivers_title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="drivers_body">Paragraph</label>
                            <textarea id="drivers_body" name="drivers_body" rows="3" maxlength="400"
                                      class="{{ $input }}">{{ $field('drivers_body') }}</textarea>
                            @error('drivers_body')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        @foreach([1, 2, 3] as $n)
                            <div class="grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
                                <div>
                                    <label class="{{ $label }}" for="driver_point_{{ $n }}_title">Point {{ $n }} title</label>
                                    <input id="driver_point_{{ $n }}_title" name="driver_point_{{ $n }}_title" type="text" maxlength="80"
                                           value="{{ $field('driver_point_'.$n.'_title') }}" class="{{ $input }}">
                                    @error('driver_point_'.$n.'_title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $label }}" for="driver_point_{{ $n }}_body">Point {{ $n }} text</label>
                                    <input id="driver_point_{{ $n }}_body" name="driver_point_{{ $n }}_body" type="text" maxlength="300"
                                           value="{{ $field('driver_point_'.$n.'_body') }}" class="{{ $input }}">
                                    @error('driver_point_'.$n.'_body')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Contact</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Shown in the contact block. Leave a detail empty to hide it.</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="{{ $label }}" for="contact_eyebrow">Eyebrow label</label>
                            <input id="contact_eyebrow" name="contact_eyebrow" type="text" maxlength="60"
                                   value="{{ $field('contact_eyebrow') }}" class="{{ $input }}">
                            @error('contact_eyebrow')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="contact_title">Heading</label>
                            <input id="contact_title" name="contact_title" type="text" maxlength="120"
                                   value="{{ $field('contact_title') }}" class="{{ $input }}">
                            @error('contact_title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="contact_body">Paragraph</label>
                            <textarea id="contact_body" name="contact_body" rows="3" maxlength="400"
                                      class="{{ $input }}">{{ $field('contact_body') }}</textarea>
                            @error('contact_body')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

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
                    <h2 class="text-sm font-bold text-slate-900">Closing panel</h2>
                    <p class="mt-0.5 text-xs text-slate-500">The orange panel above the footer that asks visitors to open the app.</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="{{ $label }}" for="cta_eyebrow">Eyebrow label</label>
                            <input id="cta_eyebrow" name="cta_eyebrow" type="text" maxlength="60"
                                   value="{{ $field('cta_eyebrow') }}" class="{{ $input }}">
                            @error('cta_eyebrow')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="cta_title">Heading</label>
                            <input id="cta_title" name="cta_title" type="text" maxlength="120"
                                   value="{{ $field('cta_title') }}" class="{{ $input }}">
                            @error('cta_title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="cta_body">Paragraph</label>
                            <textarea id="cta_body" name="cta_body" rows="3" maxlength="400"
                                      class="{{ $input }}">{{ $field('cta_body') }}</textarea>
                            @error('cta_body')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Search and sharing</h2>
                    <p class="mt-0.5 text-xs text-slate-500">How the page appears on Google and when someone shares the link.</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="{{ $label }}" for="meta_title">Search title</label>
                            <input id="meta_title" name="meta_title" type="text" maxlength="120"
                                   value="{{ $field('meta_title') }}" class="{{ $input }}"
                                   placeholder="{{ \App\Helpers\SettingHelper::getAppName() }} — {{ $content['hero_title'] }}">
                            <p class="mt-1 text-[11px] text-slate-500">Leave empty to use the app name and hero headline.</p>
                            @error('meta_title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="meta_description">Search description</label>
                            <textarea id="meta_description" name="meta_description" rows="3" maxlength="320"
                                      class="{{ $input }}" placeholder="One sentence shown under the title in search results.">{{ $field('meta_description') }}</textarea>
                            <p class="mt-1 text-[11px] text-slate-500">Leave empty to use the hero paragraph. Around 155 characters works best.</p>
                            @error('meta_description')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $label }}" for="share_image">Social share image</label>
                            @if($content['share_image_url'])
                                <div class="mb-2 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-2">
                                    <img src="{{ $content['share_image_url'] }}" alt="Current share image" class="h-16 w-24 rounded-lg object-cover">
                                    <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                                        <input type="checkbox" name="remove_share_image" value="1"
                                               class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                        Remove image
                                    </label>
                                </div>
                            @endif
                            <input id="share_image" name="share_image" type="file" accept="image/jpeg,image/png,image/webp"
                                   class="block w-full cursor-pointer rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3.5 py-3 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:border-orange-400">
                            <p class="mt-1 text-[11px] text-slate-500">Used when the link is shared. If empty, the hero photo is used. Landscape 1200×630 is ideal.</p>
                            @error('share_image')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
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
