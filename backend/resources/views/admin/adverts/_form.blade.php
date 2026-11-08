@php
    $input = 'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-100';
    $label = 'mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500';
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-slate-900">Banner content</h2>
            <p class="mt-0.5 text-xs text-slate-500">Photo-first: riders see the image and a short title, so keep text large and minimal.</p>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="{{ $label }}" for="title">Title</label>
                    <input id="title" name="title" type="text" maxlength="255" required
                           value="{{ old('title', $advert->title) }}" class="{{ $input }}"
                           placeholder="e.g. Free delivery this week">
                    @error('title')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}" for="subtitle">Short subtitle (optional)</label>
                    <input id="subtitle" name="subtitle" type="text" maxlength="255"
                           value="{{ old('subtitle', $advert->subtitle) }}" class="{{ $input }}"
                           placeholder="e.g. At Kejetia Market">
                    @error('subtitle')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}" for="image">Banner photo {{ $advert->exists ? '(leave empty to keep current)' : '' }}</label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
                           class="block w-full cursor-pointer rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3.5 py-3 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:border-orange-400"
                           {{ $advert->exists ? '' : 'required' }}>
                    <p class="mt-1 text-[11px] text-slate-500">Landscape image, up to 4 MB. Recommended 1200 x 500.</p>
                    @error('image')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}" for="link_url">Tap destination (optional)</label>
                    <input id="link_url" name="link_url" type="text" maxlength="2048"
                           value="{{ old('link_url', $advert->link_url) }}" class="{{ $input }}"
                           placeholder="https://example.com or a phone number link">
                    <p class="mt-1 text-[11px] text-slate-500">Opens when a rider taps the banner. Use https:// for a website; a phone number like 0241234567 becomes a call link automatically.</p>
                    @error('link_url')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-slate-900">Where and when</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="placement">Placement</label>
                    <select id="placement" name="placement" class="{{ $input }}">
                        @foreach($placements as $value => $text)
                            <option value="{{ $value }}" @selected(old('placement', $advert->placement) === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                    @error('placement')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}" for="sort_order">Order</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" max="9999"
                           value="{{ old('sort_order', $advert->sort_order ?? 0) }}" class="{{ $input }}">
                    <p class="mt-1 text-[11px] text-slate-500">Lower numbers appear first.</p>
                    @error('sort_order')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}" for="starts_at">Starts (optional)</label>
                    <input id="starts_at" name="starts_at" type="datetime-local"
                           value="{{ old('starts_at', $advert->starts_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
                    @error('starts_at')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="{{ $label }}" for="ends_at">Ends (optional)</label>
                    <input id="ends_at" name="ends_at" type="datetime-local"
                           value="{{ old('ends_at', $advert->ends_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
                    @error('ends_at')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <input type="checkbox" name="is_active" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                       @checked(old('is_active', $advert->is_active ?? true))>
                <span>
                    <span class="block text-sm font-semibold text-slate-800">Active</span>
                    <span class="block text-xs text-slate-500">Uncheck to keep the advert saved but hidden from riders.</span>
                </span>
            </label>
        </div>
    </div>

    <div class="space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-slate-900">Preview</h2>
            <p class="mt-0.5 text-xs text-slate-500">How the photo area will look.</p>
            <div class="mt-4 overflow-hidden rounded-2xl bg-slate-900">
                @if($advert->image_url)
                    <img src="{{ $advert->image_url }}" alt="{{ $advert->title }}" class="aspect-[12/5] w-full object-cover">
                @else
                    <div class="flex aspect-[12/5] w-full items-center justify-center bg-gradient-to-br from-orange-500 to-amber-600 text-center">
                        <p class="px-4 text-sm font-bold text-white">Your banner photo appears here</p>
                    </div>
                @endif
            </div>
            <p class="mt-3 text-xs text-slate-500">Riders who cannot read still recognise the photo, so use a clear product or shop image.</p>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-relaxed text-amber-800">
            <p class="font-semibold">Ad policy</p>
            <p class="mt-1">Only publish adverts you have permission to run, with honest claims. Keep phone numbers and prices readable.</p>
        </div>

        <div class="flex flex-col gap-2">
            <button type="submit" class="w-full rounded-xl bg-orange-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                {{ $advert->exists ? 'Save advert' : 'Publish advert' }}
            </button>
            <a href="{{ route('admin.adverts.index') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</a>
        </div>
    </div>
</div>
