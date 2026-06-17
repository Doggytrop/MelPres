<x-guest-layout>
    <div class="min-h-screen bg-[#f4f6f4] md:bg-white">
        <div class="min-h-screen w-full flex flex-col md:flex-row overflow-hidden">
            <div class="hidden bg-[#1a4a1c] text-[#d4f5d4] md:w-5/12 md:min-h-screen md:flex flex-col justify-between p-10">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-[#5fcf61] rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="9" stroke="white" stroke-width="1.5"/>
                            <path d="M12 7v1M12 16v1M9.5 10c0-.8.7-1.5 1.5-1.5h2a1.5 1.5 0 0 1 0 3h-2a1.5 1.5 0 0 0 0 3h2.5"
                                  stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
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
                        <div class="w-9 h-9 bg-[#1f6b21] rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="9" stroke="white" stroke-width="1.5"/>
                                <path d="M12 7v1M12 16v1M9.5 10c0-.8.7-1.5 1.5-1.5h2a1.5 1.5 0 0 1 0 3h-2a1.5 1.5 0 0 0 0 3h2.5"
                                      stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <span class="text-[#1a2e1a] font-medium text-base">{{ config('app.name') }}</span>
                    </div>

                    <h1 class="text-xl font-medium text-[#1a2e1a] mb-1">Restablecer contraseña</h1>
                    <p class="text-sm text-gray-400 mb-7">
                        Ingresa tu correo electrónico y te enviaremos las instrucciones para recuperar tu acceso.
                    </p>

                    @if (session('status'))
                        <div class="mb-5 rounded-lg border border-[#bfe8c0] bg-[#effaf0] px-4 py-3 text-sm font-medium text-[#1f6b21]">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="block text-[11px] font-medium text-gray-400 uppercase tracking-widest mb-1.5">
                                Correo electrónico
                            </label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                   required autofocus autocomplete="email"
                                   placeholder="correo@ejemplo.com"
                                   class="w-full bg-[#fafafa] border border-gray-200 rounded-lg px-4 py-3 text-sm text-[#1a2e1a]
                                          placeholder-gray-300 focus:outline-none focus:border-[#3a9a3b] focus:bg-white transition-colors duration-200" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-xs" />
                        </div>

                        <button type="submit"
                                class="w-full bg-[#1f6b21] hover:bg-[#256e27] text-white rounded-lg px-5 py-3
                                       text-sm font-medium tracking-wide transition-colors duration-200">
                            Enviar enlace
                        </button>
                    </form>

                    <div class="mt-6">
                        <a href="{{ route('login') }}" class="text-sm text-[#3a9a3b] hover:text-[#1f6b21] transition-colors duration-200">
                            Volver a iniciar sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
