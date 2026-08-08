<!-- Mobile Dark Overlay Layer -->
<div x-show="mobileOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden print:hidden"
     @click="mobileOpen = false"
     x-cloak></div>

<!-- Left Hand Sidebar Block Architecture Container -->
<aside
    class="print:hidden fixed inset-y-0 left-0 z-50 flex flex-col bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700 transition-all duration-200 ease-in-out h-screen"
    :class="{
        'w-64': !collapsed,
        'w-16': collapsed,
        '-translate-x-full lg:translate-x-0': !mobileOpen,
        'translate-x-0': mobileOpen
    }"
    x-cloak>

    <!-- Branding Header Panel Container -->
    <div class="flex items-center justify-between h-16 px-4 border-b border-gray-100 dark:border-gray-700 shrink-0 overflow-hidden">
        <div class="flex items-center shrink-0 min-w-0">
            <a href="{{ route('dashboard') }}" class="shrink-0">
                <x-application-logo class="block w-auto text-gray-800 fill-current h-9 dark:text-gray-200" />
            </a>
            <span x-show="!collapsed"
                  x-transition:enter="transition-opacity duration-150 delay-75"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition-opacity duration-75"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="ml-3 font-semibold text-lg text-gray-800 dark:text-gray-200 truncate whitespace-nowrap">
                {{ config('app.name', 'AIIT') }}
            </span>
        </div>

        <!-- Mobile Sidebar Dismiss Cross Trigger Button Icon -->
        <button @click="mobileOpen = false" type="button" class="p-1 -mr-1 rounded-md lg:hidden hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span class="sr-only">Close sidebar</span>
            <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Active Content Navigation Link Links Wrapper Stack -->
    <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto overflow-x-hidden">

        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}"
           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors
                  {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100' }}"
           :title="collapsed ? '{{ __('Dashboard') }}' : ''">
            <svg class="shrink-0 w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span x-show="!collapsed"
                  x-transition:enter="transition-opacity duration-150 delay-75"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition-opacity duration-75"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="ml-3 whitespace-nowrap">{{ __('Dashboard') }}</span>
        </a>

        <!-- Companies -->
        <a href="{{ route('admin.companies.index') }}"
           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors
                  {{ request()->routeIs('admin.companies*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100' }}"
           :title="collapsed ? '{{ __('Companies') }}' : ''">
            <svg class="shrink-0 w-5 h-5 {{ request()->routeIs('admin.companies*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span x-show="!collapsed"
                  x-transition:enter="transition-opacity duration-150 delay-75"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition-opacity duration-75"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="ml-3 whitespace-nowrap">{{ __('Companies') }}</span>
        </a>

        <!-- Clients -->
        <a href="{{ route('clients.index') }}"
           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors
                  {{ request()->routeIs('clients.index') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100' }}"
           :title="collapsed ? '{{ __('Clients') }}' : ''">
            <svg class="shrink-0 w-5 h-5 {{ request()->routeIs('clients.index') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span x-show="!collapsed"
                  x-transition:enter="transition-opacity duration-150 delay-75"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition-opacity duration-75"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="ml-3 whitespace-nowrap">{{ __('Clients') }}</span>
        </a>

        <!-- Xero -->
        <a href="{{ route('admin.xero.index') }}"
           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors
                  {{ request()->is('admin/xero*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100' }}"
           :title="collapsed ? '{{ __('Xero') }}' : ''">
            <svg class="shrink-0 w-5 h-5 {{ request()->is('admin/xero*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span x-show="!collapsed"
                  x-transition:enter="transition-opacity duration-150 delay-75"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition-opacity duration-75"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="ml-3 whitespace-nowrap">{{ __('Xero') }}</span>
        </a>

        <!-- Direct Debit Payments -->
        <a href="{{ route('admin.directDebitPayment.index') }}"
           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors
                  {{ request()->is('admin/directDebitPayment*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100' }}"
           :title="collapsed ? '{{ __('Direct Debit Payments') }}' : ''">
            <svg class="shrink-0 w-5 h-5 {{ request()->is('admin/directDebitPayment*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <span x-show="!collapsed"
                  x-transition:enter="transition-opacity duration-150 delay-75"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition-opacity duration-75"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="ml-3 whitespace-nowrap">{{ __('Direct Debit Payments') }}</span>
        </a>

        <!-- Stripe Payouts -->
        <a href="{{ route('admin.payouts.index') }}"
           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors
                  {{ request()->is('admin/payouts*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100' }}"
           :title="collapsed ? '{{ __('Stripe Payouts') }}' : ''">
            <svg class="shrink-0 w-5 h-5 {{ request()->is('admin/payouts*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span x-show="!collapsed"
                  x-transition:enter="transition-opacity duration-150 delay-75"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition-opacity duration-75"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="ml-3 whitespace-nowrap">{{ __('Stripe Payouts') }}</span>
        </a>

        <!-- Categories -->
        <a href="{{ route('admin.categories.index') }}"
           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors
                  {{ request()->is('admin/categories*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100' }}"
           :title="collapsed ? '{{ __('Categories') }}' : ''">
            <svg class="shrink-0 w-5 h-5 {{ request()->is('admin/categories*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <span x-show="!collapsed"
                  x-transition:enter="transition-opacity duration-150 delay-75"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition-opacity duration-75"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="ml-3 whitespace-nowrap">{{ __('Categories') }}</span>
        </a>

        <!-- Products -->
        <a href="{{ route('admin.products.index') }}"
           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors
                  {{ request()->is('admin/products*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100' }}"
           :title="collapsed ? '{{ __('Products') }}' : ''">
            <svg class="shrink-0 w-5 h-5 {{ request()->is('admin/products*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span x-show="!collapsed"
                  x-transition:enter="transition-opacity duration-150 delay-75"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition-opacity duration-75"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="ml-3 whitespace-nowrap">{{ __('Products') }}</span>
        </a>

        <!-- Quote -->
        <a href="{{ route('admin.quotes.index') }}"
           class="group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors
                  {{ request()->is('admin/quotes*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100' }}"
           :title="collapsed ? '{{ __('Quote') }}' : ''">
            <svg class="shrink-0 w-5 h-5 {{ request()->is('admin/quotes*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span x-show="!collapsed"
                  x-transition:enter="transition-opacity duration-150 delay-75"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition-opacity duration-75"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="ml-3 whitespace-nowrap">{{ __('Quote') }}</span>
        </a>

    </nav>

    <!-- Collapse Toggle Button -->
    <div class="hidden lg:flex justify-end px-3 pb-2 shrink-0">
        <button @click="collapsed = !collapsed"
                type="button"
                class="flex items-center gap-1 px-2 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <!-- Collapse icon: < > -->
            <svg x-show="!collapsed" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
            <svg x-show="collapsed" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
            </svg>
            <span x-show="!collapsed"
                  x-transition:enter="transition-opacity duration-150"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition-opacity duration-75"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="whitespace-nowrap">Collapse</span>
        </button>
    </div>

    <!-- Bottom User Settings Configuration Dropdown Panel Layout -->
    @auth
        <div class="p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 shrink-0">
            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
                <button @click="open = !open"
                        class="flex items-center w-full px-2 py-2 text-sm font-medium text-gray-600 rounded-md dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition ease-in-out duration-150"
                        :title="collapsed ? '{{ Auth::user()->name }}' : ''">
                    <!-- User Avatar Icon -->
                    <div class="shrink-0 w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                        <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 uppercase">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </span>
                    </div>
                    <div x-show="!collapsed"
                         x-transition:enter="transition-opacity duration-150 delay-75"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition-opacity duration-75"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="flex-1 text-left truncate ml-2 min-w-0">
                        <div class="font-semibold text-gray-800 dark:text-gray-200 truncate text-sm">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email }}</div>
                    </div>
                    <svg x-show="!collapsed" class="w-4 h-4 ml-2 text-gray-400 fill-current shrink-0" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Dropdown Options Action Container Flyout Area Panel -->
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute bottom-full left-0 z-50 w-48 mb-2 origin-bottom-left rounded-md shadow-lg"
                     style="display: none;">
                    <div class="py-1 bg-white rounded-md ring-1 ring-black ring-opacity-5 dark:bg-gray-700">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- User Action Form Destructive Method Sign Out Link Form -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endauth
</aside>
