<x-signature-layout>
    <!-- CONTAINER INTEGRATION WORKSPACE -->
    <div class="py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-center">
        <div class="max-w-md w-full bg-white dark:bg-gray-800 shadow-xl rounded-xl p-8 text-center border border-gray-100 dark:border-gray-700">
            <!-- Icon / Status indicator optional -->
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/50 mb-4 text-blue-600 dark:text-blue-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ $title }}</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">{{ $message }}</p>

            <a href="mailto:support@allinit.com.au" class="w-full inline-flex justify-center py-2.5 px-4 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Contact Support
            </a>
        </div>
    </div>
</x-signature-layout>
