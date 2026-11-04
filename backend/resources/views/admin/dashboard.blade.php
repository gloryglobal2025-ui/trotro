@extends('admin.layout')

@section('content')
@php
    $statusStyles = [
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'accepted' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'driver_arrived' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'in_progress' => 'bg-cyan-50 text-cyan-700 ring-cyan-600/20',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
    ];
    $paymentStyles = [
        'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'refunded' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
    ];
    $maxDestinationRides = max(1, (int) $topDestinations->max('rides'));
    $ridesDelta = $stats['rides_delta'];
    $revenueDelta = $stats['revenue_delta'];
@endphp

<div class="px-4 pb-12 pt-8 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Live
                </span>
                <span class="text-xs font-medium text-slate-400">Last 7 days · updated {{ now()->format('D, M j · H:i') }}</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 lg:text-3xl">Operations dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $appName }} — rides, revenue and network health at a glance.</p>
        </div>
        <a href="{{ route('admin.rides.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#171310] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-black">
            All rides
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
        </a>
    </div>

    <!-- KPI cards -->
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-slate-500">Rides today</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                    <svg style="height:18px;width:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['rides_today']) }}</p>
            <div class="mt-3 flex items-center gap-2">
                @if($ridesDelta !== null)
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $ridesDelta >= 0 ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20' }}">
                        <svg class="h-3 w-3 {{ $ridesDelta >= 0 ? '' : 'rotate-180' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                        </svg>
                        {{ $ridesDelta >= 0 ? '+' : '' }}{{ number_format($ridesDelta, 1) }}%
                    </span>
                    <span class="text-xs text-slate-400">vs yesterday</span>
                @else
                    <span class="text-xs text-slate-400">No rides yesterday</span>
                @endif
            </div>
            <p class="mt-2 text-xs text-slate-400">{{ number_format($stats['completed_today']) }} completed today</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-slate-500">Revenue · 7 days</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg style="height:18px;width:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">GHS {{ number_format($stats['revenue_7d'], 2) }}</p>
            <div class="mt-3 flex items-center gap-2">
                @if($revenueDelta !== null)
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $revenueDelta >= 0 ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20' }}">
                        <svg class="h-3 w-3 {{ $revenueDelta >= 0 ? '' : 'rotate-180' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                        </svg>
                        {{ $revenueDelta >= 0 ? '+' : '' }}{{ number_format($revenueDelta, 1) }}%
                    </span>
                    <span class="text-xs text-slate-400">vs previous week</span>
                @else
                    <span class="text-xs text-slate-400">No revenue last week</span>
                @endif
            </div>
            <p class="mt-2 text-xs text-slate-400">Avg fare GHS {{ number_format($stats['avg_fare_7d'], 2) }} · all-time GHS {{ number_format($stats['revenue_total'], 2) }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-slate-500">Open rides</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg style="height:18px;width:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['open_rides']) }}</p>
            <div class="mt-3 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                    {{ number_format($stats['pending_rides']) }} waiting
                </span>
                <span class="text-xs text-slate-400">for a driver</span>
            </div>
            <p class="mt-2 text-xs text-slate-400">{{ number_format($stats['completion_rate'], 1) }}% of all rides completed</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-slate-500">Drivers</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg style="height:18px;width:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['drivers']) }}</p>
            <div class="mt-3 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20">
                    {{ number_format($stats['active_vehicles']) }} active vehicles
                </span>
                @if($stats['pending_vehicles'] > 0)
                    <span class="text-xs font-medium text-orange-600">{{ $stats['pending_vehicles'] }} to approve</span>
                @endif
            </div>
            <p class="mt-2 text-xs text-slate-400">{{ number_format($stats['passengers']) }} passengers registered</p>
        </div>
    </div>

    <!-- Business strip -->
    <div class="mt-4 grid grid-cols-2 divide-slate-100 rounded-2xl border border-slate-200/80 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)] sm:grid-cols-4 sm:divide-x">
        <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Passengers</p>
            <p class="mt-1.5 text-xl font-bold text-slate-900">{{ number_format($stats['passengers']) }}</p>
        </div>
        <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Active QR passes</p>
            <p class="mt-1.5 text-xl font-bold text-slate-900">{{ number_format($stats['passes_active']) }}</p>
            <p class="mt-0.5 text-[11px] text-slate-400">{{ number_format($stats['pass_rides']) }} pass rides</p>
        </div>
        <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Wallet float</p>
            <p class="mt-1.5 text-xl font-bold text-slate-900">GHS {{ number_format($stats['wallet_balance'], 2) }}</p>
            <p class="mt-0.5 text-[11px] text-slate-400">GHS {{ number_format($stats['wallet_pending'], 2) }} pending</p>
        </div>
        <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">All-time rides</p>
            <p class="mt-1.5 text-xl font-bold text-slate-900">{{ number_format($stats['rides_total']) }}</p>
            <p class="mt-0.5 text-[11px] text-slate-400">{{ number_format($stats['completed_rides']) }} completed</p>
        </div>
    </div>

    <!-- Charts -->
    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Rides &amp; revenue</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Daily volume over the last 7 days</p>
                </div>
                <div class="flex items-center gap-4 text-xs font-medium text-slate-500">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-orange-500"></span> Rides
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-sky-500"></span> Revenue
                    </span>
                </div>
            </div>
            <div class="mt-4" style="height:264px">
                <canvas id="ridesChart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Ride status</h2>
                <p class="mt-0.5 text-xs text-slate-400">All rides by current state</p>
            </div>
            <div class="relative mx-auto mt-4" style="height:176px;width:176px">
                <canvas id="statusChart"></canvas>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-2xl font-bold text-slate-900">{{ number_format($stats['rides_total']) }}</span>
                    <span class="text-[11px] font-medium uppercase tracking-wide text-slate-400">rides</span>
                </div>
            </div>
            <ul class="mt-5 space-y-2.5">
                @foreach($charts['status'] as $status)
                    <li class="flex items-center gap-2.5 text-sm">
                        <span class="h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $status['color'] }}"></span>
                        <span class="flex-1 truncate text-slate-600">{{ $status['label'] }}</span>
                        <span class="font-semibold text-slate-900">{{ number_format($status['value']) }}</span>
                        <span class="w-10 text-right text-xs text-slate-400">
                            {{ $stats['rides_total'] > 0 ? number_format($status['value'] / $stats['rides_total'] * 100, 0) : 0 }}%
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Breakdown row -->
    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Payments</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Completed rides by method</p>
                </div>
                <span class="rounded-lg bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500">GHS {{ number_format($stats['revenue_total'], 2) }}</span>
            </div>
            @if(count($charts['payment']) > 0)
                <div class="mt-4 space-y-4">
                    @foreach($charts['payment'] as $payment)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium capitalize text-slate-700">{{ $payment['label'] }}</span>
                                <span class="text-slate-500">{{ number_format($payment['count']) }} rides · {{ $payment['share'] }}%</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $payment['method'] === 'wallet' ? 'bg-sky-500' : 'bg-orange-500' }}" style="width: {{ max(2, $payment['share']) }}%"></div>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400">GHS {{ number_format($payment['revenue'], 2) }} collected</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-6 text-sm text-slate-400">No completed rides yet.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Top destinations</h2>
                <p class="mt-0.5 text-xs text-slate-400">Where riders are heading · 30 days</p>
            </div>
            @if($topDestinations->count() > 0)
                <ul class="mt-4 space-y-3.5">
                    @foreach($topDestinations as $index => $destination)
                        <li>
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-[11px] font-bold text-slate-500">{{ $index + 1 }}</span>
                                <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700" title="{{ $destination->destination_address }}">
                                    {{ $destination->destination_address ?: 'Unknown destination' }}
                                </span>
                                <span class="shrink-0 text-xs font-semibold text-slate-900">{{ number_format($destination->rides) }}</span>
                            </div>
                            <div class="ml-8 mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-orange-400" style="width: {{ max(3, round($destination->rides / $maxDestinationRides * 100)) }}%"></div>
                            </div>
                            <p class="ml-8 mt-1 text-[11px] text-slate-400">GHS {{ number_format((float) $destination->revenue, 2) }} revenue</p>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-6 text-sm text-slate-400">No rides in the last 30 days.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Network</h2>
                <p class="mt-0.5 text-xs text-slate-400">Catalogue coverage</p>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-slate-50 p-3.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Bus stops</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ number_format($stats['bus_stops']) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Routes</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ number_format($stats['routes']) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Fares</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ number_format($stats['fares']) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Zones</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ number_format($stats['zones']) }}</p>
                </div>
            </div>
            @if($zones->count() > 0)
                <div class="mt-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Zones</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach($zones as $zone)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                {{ $zone->name }}
                                <span class="text-slate-400">{{ $zone->bus_stops_count }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent rides -->
    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Recent rides</h2>
                <p class="mt-0.5 text-xs text-slate-400">Latest activity across the network</p>
            </div>
            <a href="{{ route('admin.rides.index') }}" class="text-xs font-semibold text-orange-600 hover:text-orange-700">View all</a>
        </div>
        @if($recentRides->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3">Ride</th>
                            <th class="px-5 py-3">Passenger</th>
                            <th class="px-5 py-3">Driver</th>
                            <th class="px-5 py-3">Trip</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Payment</th>
                            <th class="px-5 py-3 text-right">Fare</th>
                            <th class="px-5 py-3 text-right">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentRides as $ride)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-3.5 font-semibold text-slate-900">#{{ $ride->id }}</td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $ride->passenger->name ?? 'N/A' }}</td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $ride->driver->name ?? 'Unassigned' }}</td>
                                <td class="max-w-[220px] truncate px-5 py-3.5 text-slate-500" title="{{ $ride->origin_address }} → {{ $ride->destination_address }}">
                                    {{ $ride->origin_address ?: '—' }} → {{ $ride->destination_address ?: '—' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $statusStyles[$ride->status] ?? 'bg-slate-100 text-slate-600 ring-slate-500/20' }}">
                                        {{ ucwords(str_replace('_', ' ', $ride->status)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($ride->payment_status)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $paymentStyles[$ride->payment_status] ?? 'bg-slate-100 text-slate-600 ring-slate-500/20' }}">
                                            {{ ucfirst($ride->payment_status) }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right font-medium text-slate-900">
                                    {{ $ride->fare_amount ? 'GHS '.number_format($ride->fare_amount, 2) : '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-right text-xs text-slate-400">{{ $ride->created_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-5 py-12 text-center">
                <p class="text-sm font-medium text-slate-600">No rides yet</p>
                <p class="mt-1 text-xs text-slate-400">Rides will appear here as soon as passengers start booking.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function () {
        if (typeof Chart === 'undefined') {
            return;
        }

        const charts = @json($charts);
        const daily = charts.daily || [];
        const statusRows = charts.status || [];

        const font = { family: 'Inter', size: 11 };
        const tooltip = {
            backgroundColor: '#171310',
            padding: 12,
            cornerRadius: 10,
            titleFont: { family: 'Inter', weight: '600' },
            bodyFont: { family: 'Inter' },
            displayColors: false,
        };

        const ridesCanvas = document.getElementById('ridesChart');

        if (ridesCanvas) {
            const context = ridesCanvas.getContext('2d');
            const fill = context.createLinearGradient(0, 0, 0, 264);
            fill.addColorStop(0, 'rgba(249, 115, 22, 0.22)');
            fill.addColorStop(1, 'rgba(249, 115, 22, 0)');

            new Chart(context, {
                type: 'line',
                data: {
                    labels: daily.map((day) => day.label),
                    datasets: [
                        {
                            label: 'Rides',
                            data: daily.map((day) => day.rides),
                            borderColor: '#F97316',
                            backgroundColor: fill,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            pointHoverBackgroundColor: '#F97316',
                            yAxisID: 'y',
                        },
                        {
                            label: 'Revenue',
                            data: daily.map((day) => day.revenue),
                            borderColor: '#0EA5E9',
                            borderDash: [5, 4],
                            borderWidth: 2,
                            fill: false,
                            tension: 0.35,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            pointHoverBackgroundColor: '#0EA5E9',
                            yAxisID: 'y1',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...tooltip,
                            callbacks: {
                                label: (item) => item.dataset.yAxisID === 'y1'
                                    ? ' Revenue: GHS ' + Number(item.parsed.y).toFixed(2)
                                    : ' Rides: ' + item.parsed.y,
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { color: '#94A3B8', font, maxTicksLimit: window.innerWidth < 680 ? 4 : 7 },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.16)' },
                            border: { display: false },
                            ticks: { color: '#94A3B8', font, precision: 0 },
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                color: '#94A3B8',
                                font,
                                callback: (value) => 'GHS ' + value,
                            },
                        },
                    },
                },
            });
        }

        const statusCanvas = document.getElementById('statusChart');

        if (statusCanvas) {
            new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: statusRows.map((row) => row.label),
                    datasets: [{
                        data: statusRows.map((row) => row.value),
                        backgroundColor: statusRows.map((row) => row.color),
                        borderWidth: 0,
                        hoverOffset: 5,
                        borderRadius: 5,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...tooltip,
                            callbacks: {
                                label: (item) => ' ' + item.label + ': ' + item.parsed + ' rides',
                            },
                        },
                    },
                },
            });
        }
    })();
</script>
@endpush
