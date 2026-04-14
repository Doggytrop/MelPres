<x-guest-layout>
    <div class="min-h-screen flex bg-[#f0f2f0]">
            <div class="w-full h-screen flex overflow-hidden">
            {{-- Panel izquierdo verde --}}
            <div class="hidden md:flex w-5/12 bg-[#1a4a1c] flex-col justify-between p-10">

                {{-- Branding --}}
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-[#5fcf61] rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="9" stroke="white" stroke-width="1.5"/>
                            <path d="M12 7v1M12 16v1M9.5 10c0-.8.7-1.5 1.5-1.5h2a1.5 1.5 0 0 1 0 3h-2a1.5 1.5 0 0 0 0 3h2.5"
                                  stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="text-[#d4f5d4] font-medium text-base">{{ config('app.name') }}</span>
                </div>

                {{-- Tagline --}}
                <div>
                    <h2 class="text-[#d4f5d4] text-3xl font-medium leading-snug mb-4">
                        Tu dinero,<br>bajo control.
                    </h2>
                    <p class="text-[#6ab96c] text-sm leading-relaxed">
                        Gestiona tus finanzas de forma segura y eficiente desde un solo lugar.
                    </p>
                </div>

                {{-- Indicadores --}}
                <div class="flex gap-1.5 items-center">
                    <div class="w-5 h-1.5 bg-[#5fcf61] rounded-full"></div>
                    <div class="w-1.5 h-1.5 bg-[#2d6b2f] rounded-full"></div>
                    <div class="w-1.5 h-1.5 bg-[#2d6b2f] rounded-full"></div>
                </div>
            </div>

            {{-- Panel derecho blanco --}}
            <div class="flex-1 bg-white flex flex-col justify-center px-10 py-12">

                <h1 class="text-xl font-medium text-[#1a2e1a] mb-1">Iniciar sesión</h1>
                <p class="text-sm text-gray-400 mb-8">Ingresa tus credenciales para continuar</p>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-4">
                        <label for="email" class="block text-[11px] font-medium text-gray-400 uppercase tracking-widest mb-1.5">
                            {{ __('Correo electrónico') }}
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               required autofocus autocomplete="username"
                               placeholder="usuario@ejemplo.com"
                               class="w-full bg-[#fafafa] border border-gray-200 rounded-lg px-4 py-2.5 text-sm text-[#1a2e1a]
                                      placeholder-gray-300 focus:outline-none focus:border-[#3a9a3b] focus:bg-white transition-colors duration-200" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-xs" />
                    </div>

                    {{-- Password --}}
                    <div class="mb-5">
                        <label for="password" class="block text-[11px] font-medium text-gray-400 uppercase tracking-widest mb-1.5">
                            {{ __('Contraseña') }}
                        </label>
                        <input id="password" type="password" name="password"
                               required autocomplete="current-password"
                               placeholder="••••••••"
                               class="w-full bg-[#fafafa] border border-gray-200 rounded-lg px-4 py-2.5 text-sm text-[#1a2e1a]
                                      placeholder-gray-300 focus:outline-none focus:border-[#3a9a3b] focus:bg-white transition-colors duration-200" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-xs" />
                    </div>

                    {{-- Remember Me + Forgot --}}
                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                            <input id="remember_me" type="checkbox" name="remember"
                                   class="w-3.5 h-3.5 rounded border-gray-300 accent-[#3a9a3b]">
                            {{ __('Recordarme') }}
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-sm text-[#3a9a3b] hover:text-[#1f6b21] transition-colors duration-200">
                                {{ __('¿Olvidaste tu contraseña?') }}
                            </a>
                        @endif
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="w-full bg-[#1f6b21] hover:bg-[#256e27] text-white rounded-lg py-3
                                   text-sm font-medium tracking-wide transition-colors duration-200">
                        {{ __('Iniciar sesión') }}
                    </button>
                    {{-- ¿No tienes cuenta? --}}
                        <p class="text-center text-sm text-gray-400 mt-5">
                            ¿No tienes cuenta?
                            <a href="{{ route('register') }}"
                            class="text-[#3a9a3b] hover:text-[#1f6b21] transition-colors duration-200">
                                Regístrate
                            </a>
                        </p>

                </form>
            </div>
        </div>
    </div>
</x-guest-layout>