<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pass;
use App\Models\User;
use App\Services\SmsService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PassManagementController extends Controller
{
    public function __construct(private WalletService $wallets)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $normalized = Pass::normalizeCode($search);

        $passes = Pass::with(['user.wallet', 'issuer'])
            ->when($search !== '', function ($query) use ($search, $normalized) {
                $query->where(function ($inner) use ($search, $normalized) {
                    if ($normalized) {
                        $inner->where('code', $normalized);
                    } else {
                        $inner->where('code', 'like', "%{$search}%");
                    }

                    $inner->orWhereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.passes.index', compact('passes', 'search'));
    }

    public function create()
    {
        return view('admin.passes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'initial_balance' => 'nullable|numeric|min:0|max:10000',
            'notes' => 'nullable|string|max:500',
            'approval_channel' => 'nullable|in:device_pin,customer_phone',
            'pin' => 'nullable|digits:4|confirmed',
        ], [
            'pin.digits' => 'The customer PIN must be exactly 4 numbers.',
            'pin.confirmed' => 'The two PINs do not match.',
        ]);

        $channel = $validated['approval_channel'] ?? Pass::APPROVAL_DEVICE_PIN;

        if ($channel === Pass::APPROVAL_CUSTOMER_PHONE) {
            $request->validate([
                'phone' => 'required|string|max:20',
                'pin' => 'required|digits:4|confirmed',
            ], [
                'phone.required' => 'A phone number is required for customer phone approval.',
                'pin.required' => 'Set a 4-digit PIN for customer phone approval.',
                'pin.digits' => 'The customer PIN must be exactly 4 numbers.',
                'pin.confirmed' => 'The two PINs do not match.',
            ]);

            if (SmsService::normalizePhone($validated['phone'] ?? null) === null) {
                throw ValidationException::withMessages([
                    'phone' => 'Enter a valid Ghana phone number, for example 024 400 0111.',
                ]);
            }
        }

        $initial = round((float) ($validated['initial_balance'] ?? 0), 2);

        $pass = DB::transaction(function () use ($validated, $initial, $request, $channel) {
            $code = Pass::generateCode();

            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower(str_replace('-', '', $code)).'@pass.trotro.local',
                'password' => Str::random(40),
                'phone' => $validated['phone'] ?? null,
                'role' => 'passenger',
                'is_assisted' => true,
                'email_verified_at' => now(),
            ]);

            $pass = Pass::create([
                'user_id' => $user->id,
                'code' => $code,
                'status' => Pass::STATUS_ACTIVE,
                'approval_channel' => $channel,
                'issued_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (! empty($validated['pin'])) {
                $pass->setPin($validated['pin']);
            }

            if ($initial > 0) {
                $this->wallets->credit(
                    $user,
                    $initial,
                    'pass_load',
                    "QR pass initial load ({$code})",
                    'pass',
                    $pass->id,
                    ['admin_id' => $request->user()->id]
                );
            }

            return $pass;
        });

        return redirect()
            ->route('admin.passes.show', $pass)
            ->with('success', 'Pass created. Print the card and hand it to the customer.');
    }

    public function show(Pass $pass)
    {
        $pass->load([
            'user.wallet',
            'issuer',
            'rides' => fn ($query) => $query->latest()->limit(5),
        ]);

        $transactions = $pass->user->walletTransactions()->latest()->limit(10)->get();

        return view('admin.passes.show', compact('pass', 'transactions'));
    }

    public function topUp(Request $request, Pass $pass)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
        ]);

        $this->wallets->credit(
            $pass->user,
            (float) $validated['amount'],
            'pass_topup',
            "QR pass top-up ({$pass->code})",
            'pass',
            $pass->id,
            ['admin_id' => $request->user()->id]
        );

        return redirect()
            ->route('admin.passes.show', $pass)
            ->with('success', 'Wallet topped up by GHS '.number_format((float) $validated['amount'], 2).'.');
    }

    public function setStatus(Request $request, Pass $pass)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,suspended',
        ]);

        $pass->update(['status' => $validated['status']]);

        return redirect()
            ->route('admin.passes.show', $pass)
            ->with('success', $validated['status'] === 'active' ? 'Pass reactivated.' : 'Pass suspended.');
    }

    public function regenerate(Pass $pass)
    {
        $old = $pass->code;
        $pass->update(['code' => Pass::generateCode()]);

        return redirect()
            ->route('admin.passes.show', $pass)
            ->with('success', "New card code issued. Old code {$old} no longer works.");
    }

    public function setPin(Request $request, Pass $pass)
    {
        $validated = $request->validate([
            'pin' => 'required|digits:4|confirmed',
        ], [
            'pin.digits' => 'The customer PIN must be exactly 4 numbers.',
            'pin.confirmed' => 'The two PINs do not match.',
        ]);

        $pass->setPin($validated['pin']);

        return redirect()
            ->route('admin.passes.show', $pass)
            ->with('success', 'Customer PIN saved. Ask the customer to approve every trip with it.');
    }

    public function removePin(Pass $pass)
    {
        $pass->removePin();
        $pass->update(['approval_channel' => Pass::APPROVAL_DEVICE_PIN]);

        return redirect()
            ->route('admin.passes.show', $pass)
            ->with('success', 'PIN removed. This card can be charged without customer approval again.');
    }

    public function updateApproval(Request $request, Pass $pass)
    {
        $validated = $request->validate([
            'approval_channel' => 'required|in:device_pin,customer_phone',
        ]);

        $channel = $validated['approval_channel'];

        if ($channel === Pass::APPROVAL_CUSTOMER_PHONE) {
            if (! $pass->requiresPin()) {
                throw ValidationException::withMessages([
                    'approval_channel' => 'Set a 4-digit PIN before turning on customer phone approval.',
                ]);
            }

            if (SmsService::normalizePhone($pass->user->phone) === null) {
                throw ValidationException::withMessages([
                    'approval_channel' => 'Add a valid phone number before turning on customer phone approval.',
                ]);
            }
        }

        $pass->update(['approval_channel' => $channel]);

        return redirect()
            ->route('admin.passes.show', $pass)
            ->with(
                'success',
                $channel === Pass::APPROVAL_CUSTOMER_PHONE
                    ? 'Customer phone approval is on. The customer approves each fare by SMS or USSD.'
                    : 'Approval is on the driver phone again.'
            );
    }

    public function unlockPin(Pass $pass)
    {
        $pass->unlockPin();

        return redirect()
            ->route('admin.passes.show', $pass)
            ->with('success', 'PIN unlocked. The customer can try again.');
    }
}
