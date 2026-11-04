<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - {{ \App\Helpers\SettingHelper::getAppName() }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#FFF7ED',
                            100: '#FFEDD5',
                            500: '#F97316',
                            600: '#EA580C',
                            700: '#C2410C',
                        },
                        forest: {
                            900: '#0B3B24',
                            700: '#0E5A34',
                            500: '#22A05E',
                        },
                    },
                },
            },
        };
    </script>
</head>
<body class="min-h-screen bg-slate-100 font-sans antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[1.05fr_1fr]">
        <aside class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-forest-900 via-forest-700 to-forest-500 px-14 py-12 text-white lg:flex">
            <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-20 h-[28rem] w-[28rem] rounded-full bg-emerald-300/10 blur-3xl"></div>
            <div class="pointer-events-none absolute bottom-24 right-16 h-40 w-40 rounded-full border border-white/10"></div>

            <div class="relative flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-forest-700 shadow-lg shadow-black/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <path d="M8 6v6"></path>
                        <path d="M15 6v6"></path>
                        <path d="M2 12h19.6"></path>
                        <path d="M18 18h3s.5-1.7.8-2.8c.1-.4.2-.8.2-1.2 0-.4-.1-.8-.2-1.2l-1.4-5C20.1 6.8 19.1 6 18 6H4a2 2 0 0 0-2 2v10h3"></path>
                        <circle cx="7" cy="18" r="2"></circle>
                        <path d="M9 18h5"></path>
                        <circle cx="16" cy="18" r="2"></circle>
                    </svg>
                </div>
                <div>
                    <p class="text-lg font-extrabold tracking-tight">{{ \App\Helpers\SettingHelper::getAppName() }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-white/60">Operations console</p>
                </div>
            </div>

            <div class="relative max-w-md">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-white/80">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                    Admin access
                </span>
                <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight">
                    Run the network from one calm dashboard.
                </h1>
                <p class="mt-4 text-sm leading-relaxed text-white/70">
                    Approve wallet requests, keep an eye on every trip, and manage riders, drivers and vehicles without the guesswork.
                </p>

                <ul class="mt-8 space-y-4 text-sm font-medium text-white/85">
                    <li class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10 text-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        Review top-ups and withdrawals in real time
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10 text-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        Monitor rides, drivers and vehicles
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/10 text-emerald-200">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        Keep fares and app settings up to date
                    </li>
                </ul>
            </div>

            <div class="relative flex items-center gap-2 text-xs font-medium text-white/50">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Protected area — authorized administrators only
            </div>
        </aside>

        <main class="relative flex min-h-screen items-center justify-center bg-[radial-gradient(circle_at_top,_#fff7ed,_#f1f5f9_55%)] px-4 py-10 sm:px-8">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-forest-700 to-forest-500 text-white shadow-lg shadow-emerald-900/20">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M8 6v6"></path>
                            <path d="M15 6v6"></path>
                            <path d="M2 12h19.6"></path>
                            <path d="M18 18h3s.5-1.7.8-2.8c.1-.4.2-.8.2-1.2 0-.4-.1-.8-.2-1.2l-1.4-5C20.1 6.8 19.1 6 18 6H4a2 2 0 0 0-2 2v10h3"></path>
                            <circle cx="7" cy="18" r="2"></circle>
                            <path d="M9 18h5"></path>
                            <circle cx="16" cy="18" r="2"></circle>
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-extrabold tracking-tight text-slate-900">{{ \App\Helpers\SettingHelper::getAppName() }}</p>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Admin console</p>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-7 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 sm:p-10">
                    <div class="mb-8">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600">Administrator</p>
                        <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Welcome back</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500">
                            Sign in to manage the {{ \App\Helpers\SettingHelper::getAppName() }} network.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div role="alert" class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-5 w-5 shrink-0 text-red-500">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <p class="text-sm font-medium leading-relaxed text-red-700">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <form id="loginForm" method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700">Email address</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                    </svg>
                                </span>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                       placeholder="admin@trotro.app"
                                       class="block w-full rounded-xl border border-slate-200 bg-slate-50 py-3.5 pl-11 pr-4 text-sm font-medium text-slate-900 placeholder-slate-400 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </span>
                                <input type="password" name="password" id="password" required autocomplete="current-password"
                                       placeholder="Enter your password"
                                       class="block w-full rounded-xl border border-slate-200 bg-slate-50 py-3.5 pl-11 pr-12 text-sm font-medium text-slate-900 placeholder-slate-400 transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10">
                                <button type="button" id="togglePassword" aria-label="Show password" aria-pressed="false"
                                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition hover:text-slate-600 focus:outline-none focus-visible:text-brand-600">
                                    <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <svg id="eyeOff" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden h-5 w-5">
                                        <path d="M10.7 5.1A10.6 10.6 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.2 4.1"></path>
                                        <path d="M6.6 6.6A18 18 0 0 0 2 12s3.5 7 10 7a10.5 10.5 0 0 0 5.4-1.4"></path>
                                        <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>
                                        <line x1="2" y1="2" x2="22" y2="22"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <label class="flex cursor-pointer items-center gap-2.5 text-sm font-medium text-slate-600">
                                <input type="checkbox" name="remember" value="1"
                                       class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/40">
                                Keep me signed in
                            </label>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-500">Restricted</span>
                        </div>

                        <button type="submit" id="submitButton"
                                class="group relative mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-orange-500/25 transition hover:shadow-orange-500/35 focus:outline-none focus:ring-4 focus:ring-orange-500/30 disabled:cursor-wait disabled:opacity-80">
                            <span id="submitLabel">Sign in to console</span>
                            <svg id="submitArrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform group-hover:translate-x-0.5">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                            <svg id="submitSpinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="hidden h-5 w-5 animate-spin">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"></circle>
                                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                            </svg>
                        </button>
                    </form>

                    <p class="mt-8 flex items-center justify-center gap-2 border-t border-slate-100 pt-6 text-xs font-medium text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path>
                        </svg>
                        Encrypted session · {{ \App\Helpers\SettingHelper::getAppName() }} admin
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script>
        (function () {
            var password = document.getElementById('password');
            var toggle = document.getElementById('togglePassword');
            var eyeOpen = document.getElementById('eyeOpen');
            var eyeOff = document.getElementById('eyeOff');

            toggle.addEventListener('click', function () {
                var reveal = password.type === 'password';
                password.type = reveal ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
                toggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
                eyeOpen.classList.toggle('hidden', reveal);
                eyeOff.classList.toggle('hidden', !reveal);
            });

            var form = document.getElementById('loginForm');
            var button = document.getElementById('submitButton');
            var label = document.getElementById('submitLabel');
            var arrow = document.getElementById('submitArrow');
            var spinner = document.getElementById('submitSpinner');

            form.addEventListener('submit', function () {
                if (button.disabled) return;
                button.disabled = true;
                label.textContent = 'Signing in…';
                arrow.classList.add('hidden');
                spinner.classList.remove('hidden');
            });
        })();
    </script>
</body>
</html>
