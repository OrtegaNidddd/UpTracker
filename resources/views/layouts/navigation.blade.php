<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                        <x-application-logo class="w-8 h-8 group-hover:scale-105 transition-transform" />
                        <span class="font-bold text-lg tracking-tight text-brand-dark hidden sm:inline-block">UpTracker</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="gap-1.5">
                        <x-reicon name="chart-square" class="w-4 h-4 text-brand-primary" />
                        <span>{{ __('Dashboard & SLA') }}</span>
                    </x-nav-link>
                    <x-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')" class="gap-1.5">
                        <x-reicon name="server" class="w-4 h-4 text-slate-400" />
                        <span>{{ __('Servicios') }}</span>
                    </x-nav-link>
                    <x-nav-link :href="route('notification-channels.index')" :active="request()->routeIs('notification-channels.*')" class="gap-1.5">
                        <x-reicon name="bell" class="w-4 h-4 text-slate-400" />
                        <span>{{ __('Canales de Alerta') }}</span>
                    </x-nav-link>
                    <x-nav-link :href="route('status.public')" target="_blank" class="gap-1.5">
                        <x-reicon name="globe" class="w-4 h-4 text-emerald-500" />
                        <span>{{ __('Página de Estado') }}</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 ml-0.5 inline-block"></span>
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-1.5 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <x-reicon name="user-circle" class="w-4 h-4 text-slate-400" />
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <x-reicon name="chevron-down" class="h-4 w-4 text-slate-400" />
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')" class="flex items-center gap-2">
                            <x-reicon name="user" class="w-4 h-4 text-slate-400" />
                            <span>{{ __('Perfil') }}</span>
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    class="flex items-center gap-2"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                <x-reicon name="logout" class="w-4 h-4 text-rose-500" />
                                <span>{{ __('Cerrar Sesión') }}</span>
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <x-reicon name="menu" x-show="! open" class="h-6 w-6" />
                    <x-reicon name="close-circle" x-show="open" x-cloak class="h-6 w-6" />
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="flex items-center gap-2">
                <x-reicon name="chart-square" class="w-4 h-4 text-brand-primary" />
                <span>{{ __('Dashboard & SLA') }}</span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')" class="flex items-center gap-2">
                <x-reicon name="server" class="w-4 h-4 text-slate-500" />
                <span>{{ __('Servicios') }}</span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('notification-channels.index')" :active="request()->routeIs('notification-channels.*')" class="flex items-center gap-2">
                <x-reicon name="bell" class="w-4 h-4 text-slate-500" />
                <span>{{ __('Canales de Alerta') }}</span>
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('status.public')" target="_blank" class="flex items-center gap-2">
                <x-reicon name="globe" class="w-4 h-4 text-emerald-500" />
                <span>{{ __('Página de Estado') }}</span>
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar Sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
