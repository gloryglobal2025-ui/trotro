<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLevel;
use App\Models\Wallet;
use App\Models\WalletBonus;
use App\Models\WalletDeposit;
use App\Models\Withdrawal;
use App\Services\WalletService;
use App\Support\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function __construct(private WalletService $wallets) {}

    public function index()
    {
        $users = User::with(['userLevel', 'wallet'])->latest()->paginate(20);
        $levels = UserLevel::orderBy('order')->get();
        $pendingVerifications = User::where('role', 'driver')
            ->where('verification_status', 'pending')
            ->count();

        return view('admin.users.index', compact('users', 'levels', 'pendingVerifications'));
    }

    public function create()
    {
        $levels = UserLevel::orderBy('order')->get();

        return view('admin.users.create', compact('levels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:20',
            'role' => 'required|in:passenger,driver,admin',
            'user_level_id' => 'nullable|exists:user_levels,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'user_level_id' => $validated['user_level_id'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $levels = UserLevel::orderBy('order')->get();

        return view('admin.users.edit', compact('user', 'levels'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'required|string|max:20',
            'role' => 'required|in:passenger,driver,admin',
            'user_level_id' => 'nullable|exists:user_levels,id',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->password);
        }

        $image = $request->file('profile_image');
        unset($validated['profile_image']);

        if ($image) {
            Image::delete($user->profile_image);
            $validated['profile_image'] = Image::store($image, 'profiles');
        }

        $user->update($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        Image::delete($user->profile_image);
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    // Driver Verification
    public function verificationRequests()
    {
        $drivers = User::where('role', 'driver')
            ->where('verification_status', 'pending')
            ->with(['vehicle', 'verifiedBy'])
            ->orderByDesc('verification_submitted_at')
            ->paginate(20);

        $counts = [
            'pending' => User::where('role', 'driver')->where('verification_status', 'pending')->count(),
            'approved' => User::where('role', 'driver')->where('verification_status', 'approved')->count(),
            'rejected' => User::where('role', 'driver')->where('verification_status', 'rejected')->count(),
            'unverified' => User::where('role', 'driver')->where('verification_status', 'unverified')->count(),
        ];

        return view('admin.users.verification-requests', compact('drivers', 'counts'));
    }

    public function approveVerification(User $user)
    {
        abort_unless($user->isDriver(), 404);

        $user->update([
            'verification_status' => 'approved',
            'verification_notes' => null,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.users.verification-requests')
            ->with('success', "{$user->name} is now a verified driver.");
    }

    public function rejectVerification(Request $request, User $user)
    {
        abort_unless($user->isDriver(), 404);

        $validated = $request->validate(['verification_notes' => 'nullable|string']);

        $user->update([
            'verification_status' => 'rejected',
            'verification_notes' => $validated['verification_notes'],
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.users.verification-requests')
            ->with('success', "{$user->name}'s verification was rejected.");
    }

    // Driver Levels
    public function driverLevels()
    {
        $levels = UserLevel::byType('driver')->orderBy('order')->get();

        return view('admin.users.driver-levels', compact('levels'));
    }

    public function storeDriverLevel(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:user_levels,slug',
            'commission_rate' => 'required|numeric|between:0,100',
            'fare_discount' => 'required|numeric|between:0,100',
            'daily_rides_limit' => 'nullable|integer',
            'min_rating' => 'required|numeric|between:1,5',
            'order' => 'required|integer',
        ]);

        UserLevel::create([
            ...$validated,
            'user_type' => 'driver',
        ]);

        return redirect()
            ->route('admin.users.driver-levels')
            ->with('success', 'Driver level created successfully.');
    }

    // Customer Levels
    public function customerLevels()
    {
        $levels = UserLevel::byType('customer')->orderBy('order')->get();

        return view('admin.users.customer-levels', compact('levels'));
    }

    public function storeCustomerLevel(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:user_levels,slug',
            'fare_discount' => 'required|numeric|between:0,100',
            'order' => 'required|integer',
        ]);

        UserLevel::create([
            ...$validated,
            'user_type' => 'customer',
            'commission_rate' => 0,
            'daily_rides_limit' => null,
            'min_rating' => 4.0,
        ]);

        return redirect()
            ->route('admin.users.customer-levels')
            ->with('success', 'Customer level created successfully.');
    }

    // Withdrawals
    public function withdrawals()
    {
        $withdrawals = Withdrawal::with(['user', 'processedBy'])->latest()->paginate(20);

        return view('admin.users.withdrawals', compact('withdrawals'));
    }

    public function approveWithdrawal(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return redirect()
                ->route('admin.users.withdrawals')
                ->with('error', 'Only pending withdrawals can be approved.');
        }

        DB::transaction(function () use ($withdrawal) {
            $this->wallets->settleWithdrawal($withdrawal);

            $withdrawal->update([
                'status' => 'approved',
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('admin.users.withdrawals')
            ->with('success', 'Withdrawal approved and paid out.');
    }

    public function rejectWithdrawal(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return redirect()
                ->route('admin.users.withdrawals')
                ->with('error', 'Only pending withdrawals can be rejected.');
        }

        $validated = $request->validate(['notes' => 'nullable|string']);

        DB::transaction(function () use ($withdrawal, $validated) {
            $this->wallets->releaseWithdrawal($withdrawal);

            $withdrawal->update([
                'status' => 'rejected',
                'notes' => $validated['notes'],
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('admin.users.withdrawals')
            ->with('success', 'Withdrawal rejected and funds returned to the wallet.');
    }

    public function deposits()
    {
        $deposits = WalletDeposit::with(['user', 'processedBy'])->latest()->paginate(20);

        return view('admin.users.wallet-deposits', compact('deposits'));
    }

    public function approveDeposit(WalletDeposit $deposit)
    {
        if ($deposit->status !== 'pending') {
            return redirect()
                ->route('admin.users.deposits')
                ->with('error', 'Only pending deposits can be approved.');
        }

        DB::transaction(function () use ($deposit) {
            $this->wallets->credit(
                $deposit->user,
                (float) $deposit->amount,
                'deposit',
                "Deposit #{$deposit->id} approved",
                'wallet_deposit',
                $deposit->id
            );

            $deposit->update([
                'status' => 'approved',
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('admin.users.deposits')
            ->with('success', 'Deposit approved and wallet credited.');
    }

    public function rejectDeposit(Request $request, WalletDeposit $deposit)
    {
        if ($deposit->status !== 'pending') {
            return redirect()
                ->route('admin.users.deposits')
                ->with('error', 'Only pending deposits can be rejected.');
        }

        $validated = $request->validate(['notes' => 'nullable|string']);

        $deposit->update([
            'status' => 'rejected',
            'notes' => $validated['notes'],
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.users.deposits')
            ->with('success', 'Deposit request rejected.');
    }

    // Wallet Management
    public function customerWallets()
    {
        $wallets = Wallet::with('user')->latest()->paginate(20);

        return view('admin.users.customer-wallets', compact('wallets'));
    }

    public function updateWallet(Request $request, Wallet $wallet)
    {
        $validated = $request->validate([
            'balance' => 'required|numeric|min:0',
            'bonus_balance' => 'required|numeric|min:0',
        ]);

        $this->wallets->adjust(
            $wallet->user,
            (float) $validated['balance'],
            (float) $validated['bonus_balance'],
            auth()->user()
        );

        return redirect()
            ->route('admin.users.customer-wallets')
            ->with('success', 'Wallet updated successfully.');
    }

    // Wallet Bonuses
    public function walletBonuses()
    {
        $bonuses = WalletBonus::latest()->paginate(20);
        $users = User::orderBy('name')->get();

        return view('admin.users.wallet-bonuses', compact('bonuses', 'users'));
    }

    public function storeWalletBonus(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:wallet_bonuses,code',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:signup,referral,promotional,special',
            'min_deposit' => 'nullable|integer|min:0',
            'min_wallet_balance' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        WalletBonus::create($validated);

        return redirect()
            ->route('admin.users.wallet-bonuses')
            ->with('success', 'Wallet bonus created successfully.');
    }

    public function grantBonus(Request $request)
    {
        $validated = $request->validate([
            'wallet_bonus_id' => 'required|exists:wallet_bonuses,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $bonus = WalletBonus::findOrFail($validated['wallet_bonus_id']);

        if ($bonus->usage_limit !== null && $bonus->used_count >= $bonus->usage_limit) {
            throw ValidationException::withMessages([
                'wallet_bonus_id' => 'This bonus has reached its usage limit.',
            ]);
        }

        $user = User::findOrFail($validated['user_id']);
        $wallet = $this->wallets->walletFor($user);

        DB::transaction(function () use ($bonus, $wallet, $user) {
            $this->wallets->creditBonus(
                $user,
                (float) $bonus->amount,
                "{$bonus->name} granted",
                $bonus->id
            );

            $wallet->bonuses()->attach($bonus->id, [
                'amount' => $bonus->amount,
                'granted_at' => now(),
                'expires_at' => $bonus->valid_until,
            ]);

            $bonus->increment('used_count');
        });

        return redirect()
            ->route('admin.users.customer-wallets')
            ->with('success', 'Bonus granted successfully.');
    }
}
