<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SettingHelper;
use App\Http\Controllers\Controller;
use App\Models\BusStop;
use App\Models\Fare;
use App\Models\Pass;
use App\Models\Ride;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Models\Zone;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $startOfToday = $now->copy()->startOfDay();
        $startOfYesterday = $startOfToday->copy()->subDay();
        $start7 = $now->copy()->subDays(6)->startOfDay();
        $startPrev7 = $start7->copy()->subDays(7);

        $ridesToday = Ride::where('created_at', '>=', $startOfToday)->count();
        $ridesYesterday = Ride::whereBetween('created_at', [$startOfYesterday, $startOfToday])->count();

        $completed7 = Ride::where('status', 'completed')->where('created_at', '>=', $start7);
        $completed7Count = (clone $completed7)->count();
        $revenue7 = (float) $completed7->sum('fare_amount');
        $revenuePrev7 = (float) Ride::where('status', 'completed')
            ->whereBetween('created_at', [$startPrev7, $start7])
            ->sum('fare_amount');

        $completedTotal = Ride::where('status', 'completed')->count();
        $ridesTotal = Ride::count();

        $stats = [
            'rides_total' => $ridesTotal,
            'rides_today' => $ridesToday,
            'rides_delta' => $this->percentChange($ridesYesterday, $ridesToday),
            'completed_rides' => $completedTotal,
            'completed_today' => Ride::where('status', 'completed')
                ->where('created_at', '>=', $startOfToday)
                ->count(),
            'open_rides' => Ride::whereIn('status', ['pending', 'accepted', 'driver_arrived', 'in_progress'])->count(),
            'pending_rides' => Ride::where('status', 'pending')->count(),
            'cancelled_rides' => Ride::where('status', 'cancelled')->count(),
            'completion_rate' => $ridesTotal > 0 ? round($completedTotal / $ridesTotal * 100, 1) : 0.0,
            'revenue_7d' => $revenue7,
            'revenue_delta' => $this->percentChange($revenuePrev7, $revenue7),
            'revenue_total' => (float) Ride::where('status', 'completed')->sum('fare_amount'),
            'avg_fare_7d' => $completed7Count > 0 ? round($revenue7 / $completed7Count, 2) : 0.0,
            'passengers' => User::where('role', 'passenger')->count(),
            'drivers' => User::where('role', 'driver')->count(),
            'active_vehicles' => Vehicle::where('is_active', true)->count(),
            'pending_vehicles' => Vehicle::where('request_status', 'pending')->count(),
            'passes_active' => Pass::where('status', Pass::STATUS_ACTIVE)->count(),
            'pass_rides' => (int) Pass::sum('ride_count'),
            'wallet_balance' => (float) Wallet::sum('balance'),
            'wallet_pending' => (float) Wallet::sum('pending_balance'),
            'bus_stops' => BusStop::count(),
            'routes' => Route::count(),
            'fares' => Fare::count(),
            'zones' => Zone::count(),
        ];

        $charts = [
            'daily' => $this->ridesPerDay($start7),
            'status' => $this->statusBreakdown(),
            'payment' => $this->paymentBreakdown(),
        ];

        $recentRides = Ride::with(['passenger', 'driver'])
            ->latest()
            ->take(6)
            ->get();

        $topDestinations = Ride::query()
            ->selectRaw('destination_address, COUNT(*) as rides, COALESCE(SUM(fare_amount), 0) as revenue')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->groupBy('destination_address')
            ->orderByDesc('rides')
            ->take(5)
            ->get();

        $zones = Zone::withCount('busStops')->latest()->take(6)->get();

        $appName = SettingHelper::getAppName();

        return view('admin.dashboard', compact('stats', 'charts', 'recentRides', 'topDestinations', 'zones', 'appName'));
    }

    private function ridesPerDay(Carbon $start): array
    {
        $rides = Ride::where('created_at', '>=', $start)
            ->get(['created_at', 'status', 'fare_amount']);

        $series = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $day = $start->copy()->addDays($offset);
            $dayRides = $rides->filter(
                fn (Ride $ride) => $ride->created_at->toDateString() === $day->toDateString()
            );

            $series[] = [
                'label' => $day->format('D j'),
                'date' => $day->toDateString(),
                'rides' => $dayRides->count(),
                'revenue' => round($dayRides->where('status', 'completed')->sum('fare_amount'), 2),
            ];
        }

        return $series;
    }

    private function statusBreakdown(): array
    {
        $counts = Ride::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $meta = [
            'pending' => ['label' => 'Waiting for driver', 'color' => '#F59E0B'],
            'accepted' => ['label' => 'Driver assigned', 'color' => '#3B82F6'],
            'driver_arrived' => ['label' => 'Driver arrived', 'color' => '#8B5CF6'],
            'in_progress' => ['label' => 'On trip', 'color' => '#06B6D4'],
            'completed' => ['label' => 'Completed', 'color' => '#10B981'],
            'cancelled' => ['label' => 'Cancelled', 'color' => '#EF4444'],
        ];

        $breakdown = [];

        foreach ($meta as $status => $info) {
            $breakdown[] = [
                'status' => $status,
                'label' => $info['label'],
                'color' => $info['color'],
                'value' => (int) ($counts[$status] ?? 0),
            ];
        }

        return $breakdown;
    }

    private function paymentBreakdown(): array
    {
        $counts = Ride::query()
            ->selectRaw('payment_method, COUNT(*) as total, COALESCE(SUM(fare_amount), 0) as revenue')
            ->where('status', 'completed')
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->get();

        $total = max(1, (int) $counts->sum('total'));

        return $counts
            ->map(fn ($row) => [
                'method' => $row->payment_method,
                'label' => ucfirst($row->payment_method),
                'count' => (int) $row->total,
                'revenue' => (float) $row->revenue,
                'share' => round($row->total / $total * 100),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    private function percentChange(float $previous, float $current): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
