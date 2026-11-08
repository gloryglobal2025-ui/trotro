@extends('admin.layout')

@section('content')
<div class="p-6 lg:p-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Promotions</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Adverts</h1>
            <p class="mt-1 text-sm text-slate-500">Banners shown in the rider app, the driver dashboard and the web build.</p>
        </div>
        <a href="{{ route('admin.adverts.create') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            New advert
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-[11px] uppercase tracking-[0.14em] text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Banner</th>
                        <th class="px-4 py-3 font-semibold">Placement</th>
                        <th class="px-4 py-3 font-semibold">Window</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($adverts as $advert)
                        <tr class="align-middle hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-14 w-24 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                        @if($advert->image_url)
                                            <img src="{{ $advert->image_url }}" alt="{{ $advert->title }}" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $advert->title }}</p>
                                        @if($advert->subtitle)
                                            <p class="truncate text-xs text-slate-500">{{ $advert->subtitle }}</p>
                                        @endif
                                        @if($advert->link_url)
                                            <p class="mt-0.5 truncate text-[11px] text-orange-600">{{ $advert->link_url }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    {{ \App\Models\Advertisement::PLACEMENTS[$advert->placement] ?? $advert->placement }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                @if($advert->starts_at || $advert->ends_at)
                                    {{ $advert->starts_at?->format('M j, Y H:i') ?? 'Now' }}
                                    &rarr;
                                    {{ $advert->ends_at?->format('M j, Y H:i') ?? 'No end' }}
                                @else
                                    Always
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($advert->isLive())
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Live
                                    </span>
                                @elseif($advert->is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Scheduled / expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Paused
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.adverts.toggle', $advert) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                                            {{ $advert->is_active ? 'Pause' : 'Activate' }}
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.adverts.edit', $advert) }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">Edit</a>
                                    <form method="POST" action="{{ route('admin.adverts.destroy', $advert) }}" onsubmit="return confirm('Delete this advert?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-rose-200 px-2.5 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-14 text-center">
                                <p class="text-sm font-semibold text-slate-700">No adverts yet</p>
                                <p class="mt-1 text-sm text-slate-500">Create a banner to promote a business, a route or an offer in the app.</p>
                                <a href="{{ route('admin.adverts.create') }}" class="mt-4 inline-flex rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Create the first advert</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $adverts->links() }}</div>
</div>
@endsection
