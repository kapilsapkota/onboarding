<!-- Mobile Dark Overlay Layer -->
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-gray-900/50 md:hidden"
     @click="sidebarOpen = false"
     x-cloak></div>

<!-- Left Hand Sidebar Block Architecture Container -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700 transform transition-transform duration-200 ease-in-out md:translate-x-0 h-screen"
       x-cloak>

    <!-- Branding Header Panel Container -->
    <div class="flex items-center justify-between h-16 px-6 border-b border-gray-100 dark:border-gray-700 shrink-0">
        <div class="flex items-center shrink-0">
            <a href="{{ route('dashboard') }}">
                <x-application-logo class="block w-auto text-gray-800 fill-current h-9 dark:text-gray-200" />
            </a>
            <span class="ml-3 font-semibold text-lg text-gray-800 dark:text-gray-200">
                {{ config('app.name', 'AIIT') }}
            </span>
        </div>

        <!-- Mobile Sidebar Dismiss Cross Trigger Button Icon -->
        <button @click="sidebarOpen = false" type="button" class="p-1 -mr-1 rounded-md md:hidden hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span class="sr-only">Close sidebar</span>
            <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Active Content Navigation Link Links Wrapper Stack -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">

        <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="flex items-center px-3 py-2 text-sm font-medium rounded-md">
            {{ __('Dashboard') }}
        </x-responsive-nav-link>

        {{-- @role('super-admin') --}}
        <x-responsive-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.index')" class="flex items-center px-3 py-2 text-sm font-medium rounded-md">
            {{ __('Clients') }}
        </x-responsive-nav-link>
        {{-- @endrole --}}

        <x-responsive-nav-link :href="route('admin.xero.index')" :active="request()->is('admin/xero*')" class="flex items-center px-3 py-2 text-sm font-medium rounded-md">
            {{ __('Xero') }}
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('admin.directDebitPayment.index')" :active="request()->is('admin/directDebitPayment*')" class="flex items-center px-3 py-2 text-sm font-medium rounded-md">
            {{ __('Direct Debit Payments') }}
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('admin.payouts.index')" :active="request()->is('admin/payouts*')" class="flex items-center px-3 py-2 text-sm font-medium rounded-md">
            {{ __('Stripe Payouts') }}
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('admin.categories.index')" :active="request()->is('admin/categories*')" class="flex items-center px-3 py-2 text-sm font-medium rounded-md">
            {{ __('Categories') }}
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('admin.products.index')" :active="request()->is('admin/products*')" class="flex items-center px-3 py-2 text-sm font-medium rounded-md">
            {{ __('Products') }}
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('admin.quotes.index')" :active="request()->is('admin/quotes*')" class="flex items-center px-3 py-2 text-sm font-medium rounded-md">
            {{ __('Quote') }}
        </x-responsive-nav-link>

    </nav>

    <!-- Bottom User Settings Configuration Dropdown Panel Layout -->
    @auth
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 shrink-0">
        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
            <button @click="open = !open" class="flex items-center w-full px-3 py-2 text-sm font-medium text-gray-600 rounded-md dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition ease-in-out duration-150">
                <div class="flex-1 text-left truncate">
                    <div class="font-semibold text-gray-800 dark:text-gray-200 truncate">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email }}</div>
                </div>
                <svg class="w-4 h-4 ml-2 text-gray-400 fill-current shrink-0" viewBox="0 0 20 20">
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
                 class="absolute bottom-full left-0 z-50 w-full mb-2 origin-bottom-left rounded-md shadow-lg"
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
