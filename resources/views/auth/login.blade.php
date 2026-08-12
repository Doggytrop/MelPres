<x-guest-layout>
    <div class="min-h-screen bg-[#f4f6f4] md:bg-white">
        <div class="min-h-screen w-full flex flex-col md:flex-row overflow-hidden">
            <div class="hidden bg-[#1a4a1c] text-[#d4f5d4] md:w-5/12 md:min-h-screen md:flex flex-col justify-between p-10">
                <div class="flex items-center gap-3">
                    <x-application-logo class="w-9 h-9 rounded-lg flex-shrink-0 object-contain" />
                    <span class="font-medium text-base">{{ config('app.name') }}</span>
                </div>

                <div class="mt-10 md:mt-0">
                    <h2 class="text-2xl sm:text-3xl font-medium leading-snug mb-3 md:mb-4">
                        Tu dinero,<br>bajo control.
                    </h2>
                    <p class="text-[#8ed28f] text-sm leading-relaxed max-w-sm">
                        Gestiona tus finanzas de forma segura y eficiente desde un solo lugar.
                    </p>
                </div>

                <div class="hidden md:flex gap-1.5 items-center">
                    <div class="w-5 h-1.5 bg-[#5fcf61] rounded-full"></div>
                    <div class="w-1.5 h-1.5 bg-[#2d6b2f] rounded-full"></div>
                    <div class="w-1.5 h-1.5 bg-[#2d6b2f] rounded-full"></div>
                </div>
            </div>

            <div class="flex-1 flex items-center justify-center px-5 py-8 sm:px-8 md:bg-white md:px-10">
                <div class="w-full max-w-md bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 md:border-0 md:shadow-none md:rounded-none md:p-0">
                    <div class="md:hidden flex items-center gap-3 mb-8">
                        <x-application-logo class="w-9 h-9 rounded-lg flex-shrink-0 object-contain" />
                        <span class="text-[#1a2e1a] font-medium text-base">{{ config('app.name') }}</span>
                    </div>

                    <h1 class="text-xl font-medium text-[#1a2e1a] mb-1">Iniciar sesión</h1>
                    <p class="text-sm text-gray-400 mb-7">Ingresa tus credenciales para continuar</p>

                    <x-auth-session-status class="mb-5 rounded-lg border border-[#bfe8c0] bg-[#effaf0] px-4 py-3 text-sm font-medium text-[#1f6b21]" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="login" class="block text-[11px] font-medium text-gray-400 uppercase tracking-widest mb-1.5">
                                Correo electrónico o teléfono
                            </label>
                            <input id="login" type="text" name="login" value="{{ old('login') }}"
                                   required autofocus autocomplete="username"
                                   placeholder="correo@ejemplo.com o 6621234567"
                                   class="w-full bg-[#fafafa] border border-gray-200 rounded-lg px-4 py-3 text-sm text-[#1a2e1a]
                                          placeholder-gray-300 focus:outline-none focus:border-[#3a9a3b] focus:bg-white transition-colors duration-200" />
                            <x-input-error :messages="$errors->get('login')" class="mt-2 text-red-500 text-xs" />
                        </div>

                        <div>
                            <label for="password" class="block text-[11px] font-medium text-gray-400 uppercase tracking-widest mb-1.5">
                                Contraseña
                            </label>
                            <div style="position:relative;">
                                <input id="password" type="password" name="password"
                                       required autocomplete="current-password"
                                       placeholder="••••••••"
                                       style="width:100%; padding: 12px 44px 12px 16px; border: 1px solid #e5e7eb; border-radius: 8px; background:#fafafa; font-size:14px; color:#1a2e1a; outline:none; box-sizing:border-box;"
                                       onfocus="this.style.borderColor='#3a9a3b'; this.style.background='white'"
                                       onblur="this.style.borderColor='#e5e7eb'; this.style.background='#fafafa'" />
                                <button type="button"
                                        onclick="
                                            const i = document.getElementById('password');
                                            const eyeOpen = document.getElementById('eye-open');
                                            const eyeClosed = document.getElementById('eye-closed');
                                            if(i.type === 'password'){
                                                i.type = 'text';
                                                eyeOpen.classList.add('hidden');
                                                eyeClosed.classList.remove('hidden');
                                            } else {
                                                i.type = 'password';
                                                eyeOpen.classList.remove('hidden');
                                                eyeClosed.classList.add('hidden');
                                            }
                                        "
                                        style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#9ca3af; padding:0; display:flex; align-items:center;">
                                    <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="hidden">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-xs" />
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                                <input id="remember_me" type="checkbox" name="remember"
                                       class="w-3.5 h-3.5 rounded border-gray-300 accent-[#3a9a3b]">
                                Recordarme
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}"
                                   class="text-sm text-[#3a9a3b] hover:text-[#1f6b21] transition-colors duration-200">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            @endif
                        </div>

                        <button type="submit"
                                class="w-full bg-[#1f6b21] hover:bg-[#256e27] text-white rounded-lg px-5 py-3
                                       text-sm font-medium tracking-wide transition-colors duration-200">
                            Iniciar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
